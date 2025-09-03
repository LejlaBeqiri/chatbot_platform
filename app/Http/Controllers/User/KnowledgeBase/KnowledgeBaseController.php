<?php

namespace App\Http\Controllers\User\KnowledgeBase;

use App\Http\Controllers\Controller;
use App\Http\Requests\KnowledgeBase\StoreKnowledgeBaseRequest;
use App\Http\Requests\KnowledgeBase\UpdateKnowledgeBaseRequest;
use App\Http\Resources\EmbeddingResource;
use App\Http\Resources\KnowledgeBaseResource;
use App\Jobs\ProcessDirectEmbeddings;
use App\Jobs\ProcessEmbeddings;
use App\Models\Embedding;
use App\Models\KnowledgeBase;
use App\Services\FileProcessingService;
use App\Values\MediaCollectionsValues;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class KnowledgeBaseController extends Controller
{
    protected FileProcessingService $fileProcessingService;

    public function __construct(FileProcessingService $fileProcessingService)
    {
        $this->fileProcessingService = $fileProcessingService;
    }

    public function index(): AnonymousResourceCollection|JsonResponse
    {
        $knowledgeBases = KnowledgeBase::query()
            ->when(! $this->authUser()->hasRole('admin'), function ($query) {
                $query->where('tenant_id', $this->authUser()->tenant->id);
            })
            ->latest()
            ->paginate($this->defaultPerPage);

        return $this->success(KnowledgeBaseResource::collection($knowledgeBases)->response()->getData(true));
    }

    public function store(StoreKnowledgeBaseRequest $request): KnowledgeBaseResource|JsonResponse
    {
        DB::beginTransaction();
        try {
            $knowledgeBase = KnowledgeBase::create(Arr::except($request->validated(), 'files'));

            if ($request->hasFile('files')) {
                $result = $this->processPDFFiles($request->file('files'), $knowledgeBase);
                if ($result instanceof JsonResponse) {
                    DB::rollback();

                    return $result;
                }
            }

            DB::commit();

            return $this->success(new KnowledgeBaseResource($knowledgeBase), 'Knowledge base created successfully');
        } catch (ValidationException $ex) {
            DB::rollback();

            return $this->error('Validation failed', 422, $ex->errors());
        } catch (Exception $ex) {
            DB::rollback();
            Log::error($ex->getMessage());

            return $this->error($ex->getMessage());
        }
    }

    public function show(KnowledgeBase $knowledgeBase): JsonResponse
    {
        $this->authorize('view', $knowledgeBase);

        $knowledgeBase = new KnowledgeBaseResource($knowledgeBase);

        return $this->success($knowledgeBase);
    }

    public function update(UpdateKnowledgeBaseRequest $request, KnowledgeBase $knowledgeBase): KnowledgeBaseResource|JsonResponse
    {
        $this->authorize('update', $knowledgeBase);

        DB::begintransaction();
        try {
            $knowledgeBase->update([
                'name'        => $request->input('name'),
                'description' => $request->input('description'),
            ]);

            DB::commit();
        } catch (Exception $ex) {
            DB::rollBack();

            return $this->error($ex->getMessage());
        }

        return new KnowledgeBaseResource($knowledgeBase);
    }

    public function destroy(KnowledgeBase $knowledgeBase): JsonResponse
    {
        $this->authorize('delete', $knowledgeBase);

        DB::beginTransaction();
        try {
            $knowledgeBase->delete();
            $knowledgeBase->clearMediaCollection('training_data');

            DB::commit();

            return $this->success(null, 'Knowledge base deleted successfully');
        } catch (Exception $ex) {
            DB::rollBack();

            return $this->error($ex->getMessage());
        }
    }

    public function uploadForEmbedding(Request $request, KnowledgeBase $knowledgeBase): JsonResponse
    {
        $this->authorize('update', $knowledgeBase);

        Log::info("knowledgeBase: {$knowledgeBase->id} - Uploading files for embedding");
        
        DB::beginTransaction();
        try {
            $request->validate([
                'files'   => 'required|array',
                'files.*' => [
                    'required',
                    'file',
                    'max:5000',
                    // Remove 'mimes' and 'mimetypes' for extension-based validation
                ],
            ]);

            if ($request->hasFile('files')) {
                $result = $this->processFiles($request->file('files'), $knowledgeBase);
                if ($result instanceof JsonResponse) {
                    DB::rollBack();
                    return $result;
                }
            }

            DB::commit();
            return $this->success(null, 'File uploaded for embedding');

        } catch (ValidationException $ex) {
            DB::rollBack();
            return $this->error('Validation failed', 422, $ex->errors());
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error("Error uploading files for embedding: " . $ex->getMessage());
            return $this->error($ex->getMessage());
        }
    }

    private function processFiles(array $files, KnowledgeBase $knowledgeBase): ?JsonResponse
    {
        $allowedMimeTypes = [
            'application/pdf',
            'application/x-pdf',
            'application/x-ndjson',
            'application/x-jsonl',
            'application/jsonl',
            'application/json',
            'application/octet-stream',
            'text/plain', // Some servers may upload as text/plain
            'application/x-ndjason', // legacy typo support
        ];

        foreach ($files as $file) {
            // Validate file type
            $mimeType = $file->getMimeType();
            if (!in_array($mimeType, $allowedMimeTypes)) {
                $fileName = $file->getClientOriginalName();
                Log::warning("Invalid file type uploaded: {$mimeType} for file {$fileName}");
                return $this->error("File '{$fileName}' is not a valid PDF or JSONL/NDJSON file.", 422);
            }

            $media = $knowledgeBase->addMedia($file)
                ->usingFileName($file->getClientOriginalName())
                ->toMediaCollection(MediaCollectionsValues::TRAINING_DATA->value, 'private');

            $apiKeyRecord = $this->authUser()->tenant
                ->apiKeys()
                ->where('provider', 'OPENAI')
                ->firstOrFail()->key;
            $tenantId = $this->authUser()->tenant->id;

            if (! $apiKeyRecord) {
                Log::error("API key not found for tenant ID {$tenantId}");
                return $this->error('API key configuration missing for your tenant.', 500);
            }

            $decryptedOpenAIApiKey = Crypt::decryptString($apiKeyRecord);

            // Determine if file is JSONL/NDJSON by extension or mime type
            $fileExtension = strtolower($file->getClientOriginalExtension());
            $jsonlMimeTypes = [
                'application/x-ndjson',
                'application/x-jsonl',
                'application/jsonl',
                'application/json',
                'application/octet-stream',
                'text/plain',
                'application/x-ndjason',
            ];
            if (in_array($media->mime_type, $jsonlMimeTypes) || in_array($fileExtension, ['jsonl', 'ndjson'])) {
                ProcessEmbeddings::dispatch($knowledgeBase, $media->uuid, $decryptedOpenAIApiKey);
            } else {
                $this->fileProcessingService->processPdfFile($media, $knowledgeBase->id, $decryptedOpenAIApiKey);
            }
        }

        return null;
    }

    public function download(KnowledgeBase $knowledgeBase, string $mediaId)
    {
        $this->authorize('view', $knowledgeBase);

        $media = $knowledgeBase->getMedia(MediaCollectionsValues::TRAINING_DATA->value)->firstWhere('uuid', $mediaId);
        if (! $media) {
            return $this->error('File not found', 404);
        }

        $path     = $media->getPath();
        $filename = request('filename', $media->file_name);

        return response()->stream(
            function () use ($path) {
                $stream = fopen($path, 'rb');
                while (! feof($stream)) {
                    echo fread($stream, 8192);
                }
                fclose($stream);
            },
            200,
            [
                'Content-Type'        => $media->mime_type,
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    public function deleteFile(KnowledgeBase $knowledgeBase, string $mediaId): JsonResponse
    {

        $this->authorize('update', $knowledgeBase);

        DB::beginTransaction();
        try {
            $media = $knowledgeBase->getMedia(MediaCollectionsValues::TRAINING_DATA->value)->firstWhere('uuid', $mediaId);
            if (! $media) {
                return $this->error('File not found', 404);
            }

            // Only delete embeddings associated with this media file
            $knowledgeBase->embeddings()->where('media_id', $media->id)->delete();

            $media->delete();
            DB::commit();
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage());

            return $this->error($ex->getMessage());
        }

        return $this->success(null, 'File deleted successfully');
    }

    public function getEmbeddings(KnowledgeBase $knowledgeBase): JsonResponse
    {
        $this->authorize('view', $knowledgeBase);

        $embeddings = Embedding::where('knowledge_base_id', $knowledgeBase->id)
            ->when(! $this->authUser()->hasRole('admin'), function ($query) {
                $query->where('tenant_id', $this->authUser()->tenant->id);
            })
            ->latest()
            ->paginate($this->defaultPerPage);

        return $this->success(EmbeddingResource::collection($embeddings)->response()->getData(true));
    }

    public function addEmbeddings(Request $request, KnowledgeBase $knowledgeBase): JsonResponse
    {
        $this->authorize('update', $knowledgeBase);

        $validated = $request->validate([
            'embeddings'               => 'required|array',
            'embeddings.*.source_text' => 'required|string',
            'embeddings.*.metadata'    => 'nullable|array',
        ]);

        $embeddingsData = $validated['embeddings'];
        $tenantId       = $this->authUser()->tenant->id;

        // Ensure the API key exists and decrypt it
        $apiKeyRecord = $this->authUser()->tenant
            ->apiKeys()
            ->where('provider', 'OPENAI')
            ->firstOrFail()->key;
        if (! $apiKeyRecord) {
            Log::error("API key not found for tenant ID {$tenantId}");

            return $this->error('API key configuration missing for your tenant.', 500);
        }
        $decryptedOpenAIApiKey = Crypt::decryptString($apiKeyRecord);

        $preparedEmbeddings = array_map(function ($embedding) use ($tenantId) {
            $embedding['tenant_id'] = $tenantId;

            return $embedding;
        }, $embeddingsData);

        try {
            ProcessDirectEmbeddings::dispatch($knowledgeBase, $preparedEmbeddings, $decryptedOpenAIApiKey);

            return $this->success(null, 'Embeddings are being processed.');
        } catch (Exception $ex) {
            Log::error("Error dispatching ProcessDirectEmbeddings job for KnowledgeBase ID {$knowledgeBase->id}: " . $ex->getMessage());

            return $this->error('Failed to queue embeddings processing. Please check the logs.', 500);
        }
    }
}

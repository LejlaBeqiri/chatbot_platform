<?php

namespace App\Http\Controllers\User\Embedding;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmbeddingResource;
use App\Jobs\ProcessEmbeddings;
use App\Models\Embedding;
use App\Models\KnowledgeBase;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmbeddingController extends Controller
{
    public function index(): JsonResponse
    {
        $embeddings = Embedding::where($this->authUser()->tenant->id, 'tenant_id')->paginate($this->defaultPerPage);

        return $this->success(EmbeddingResource::collection($embeddings));
    }

    public function process_embeddings(Request $request): JsonResponse
    {
        $request->validate([
            'knowledge_base_id' => 'required|exists:knowledge_bases,id',
            'file_id'           => 'required|exists:media,uuid',
        ]);

        $knowledgeBaseId = $request->input('knowledge_base_id');
        $knowledgeBase   = KnowledgeBase::findOrFail($knowledgeBaseId);

        $this->authorize('update', $knowledgeBase);

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

        ProcessEmbeddings::dispatch($knowledgeBaseId, $request->file_id, $decryptedOpenAIApiKey);

        return response()->json(['message' => 'Embedding processing started']);
    }

    public function deleteEmbedding(KnowledgeBase $knowledgeBase, Embedding $embedding): JsonResponse
    {
        $this->authorize('delete', $knowledgeBase);

        DB::beginTransaction();
        try {
            $embedding->delete();
            DB::commit();

            return $this->success(null, 'Embedding deleted successfully');
        } catch (Exception $ex) {
            DB::rollBack();

            return $this->error($ex->getMessage());
        }
    }
}

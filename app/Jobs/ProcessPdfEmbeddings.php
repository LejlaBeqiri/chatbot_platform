<?php

namespace App\Jobs;

use App\Models\Embedding;
use App\Models\KnowledgeBase;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use OpenAI;
use Pgvector\Laravel\Vector;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class ProcessPdfEmbeddings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $knowledgeBaseId,
        protected array $chunks,
        protected ?string $apiKey,
        protected Media $media,
        protected ?string $embeddingModel = 'text-embedding-3-small'

    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {

            $knowledgeBase = KnowledgeBase::findOrFail($this->knowledgeBaseId);

            $this->media->setCustomProperty('embedding_status', 'processing');
            $this->media->save();

            $apiKey = $this->apiKey ?? config('openai.api_key');

            $batchSize  = 50;
            $textChunks = array_chunk($this->chunks, $batchSize);

            foreach ($textChunks as $chunk) {

                $textsToEmbed = array_map(function ($item) {
                    return $item[0]['content'];
                }, $chunk);

                $response = OpenAI::client($apiKey)->embeddings()->create([
                    'model' => $this->embeddingModel,
                    'input' => $textsToEmbed,
                ]);

                $embeddings = $response->toArray()['data'];

                foreach ($embeddings as $index => $embeddingData) {
                    Embedding::create([
                        'knowledge_base_id' => $this->knowledgeBaseId,
                        'tenant_id'         => $knowledgeBase->tenant_id,
                        'source_text'       => $textsToEmbed[$index],
                        'embedding'         => new Vector($embeddingData['embedding']),
                        'metadata'          => [
                            'type'       => 'pdf_chunk',
                            'chunk_size' => strlen($textsToEmbed[$index]),
                        ],
                        'media_id'        => $this->media->id,
                        'embedding_model' => $this->embeddingModel,
                    ]);
                }
            }

            Log::info("Successfully processed PDF embeddings for KnowledgeBase ID: {$this->knowledgeBaseId}");
            $this->media->setCustomProperty('embedding_status', 'completed');
            $this->media->save();
        } catch (Exception $e) {
            Log::error("Failed to process PDF embeddings for KnowledgeBase ID: {$this->knowledgeBaseId}. Error: " . $e->getMessage(), [
                'exception' => $e,
            ]);
            $this->media->setCustomProperty('embedding_status', 'failed');
            $this->media->setCustomProperty('embedding_error', $e->getMessage());
            $this->media->save();
            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::critical("ProcessPdfEmbeddings job failed for KnowledgeBase ID: {$this->knowledgeBaseId}", [
            'exception' => $exception,
        ]);
    }
}

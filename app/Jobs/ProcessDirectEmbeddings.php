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
use Throwable;

class ProcessDirectEmbeddings implements ShouldQueue
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
     *
     * @param  KnowledgeBase  $knowledgeBase  The knowledge base instance.
     * @param  array  $embeddingsData  Array of embedding data to process.
     */
    public function __construct(
        protected KnowledgeBase $knowledgeBase,
        protected array $embeddingsData,
        protected string $apiKey
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $textsToEmbed   = array_column($this->embeddingsData, 'source_text');
        $batchSize      = 50;
        $textChunks     = array_chunk($textsToEmbed, $batchSize);
        $embeddingModel = 'text-embedding-3-small';

        try {
            $allEmbeddings = [];
            foreach ($textChunks as $chunk) {
                if (empty($chunk)) {
                    continue;
                }

                $response = OpenAI::client($this->apiKey)->embeddings()->create([
                    'model' => $embeddingModel,
                    'input' => $chunk,
                ]);

                $batchEmbeddings = $response->toArray()['data'];
                $allEmbeddings   = array_merge($allEmbeddings, $batchEmbeddings);
            }

            if (count($allEmbeddings) !== count($this->embeddingsData)) {
                throw new Exception('Mismatch between number of embeddings requested and received.');
            }

            $embeddingsToCreate = [];
            foreach ($this->embeddingsData as $index => $data) {
                if (! isset($allEmbeddings[$index]['embedding'])) {
                    Log::error("Missing embedding vector for index {$index}", ['data' => $data]);

                    continue; // Skip if embedding data is missing for some reason
                }
                $embeddingsToCreate[] = [
                    'knowledge_base_id' => $this->knowledgeBase->id,
                    'tenant_id'         => $this->knowledgeBase->tenant_id,
                    'source_text'       => $data['source_text'],
                    'embedding'         => new Vector($allEmbeddings[$index]['embedding']),
                    'metadata'          => $data['metadata'] ?? null, // Use provided metadata or null
                    'media_id'          => $data['media_id'] ?? null, // Use provided media_id or null
                    'embedding_model'   => $embeddingModel,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ];
            }

            if (! empty($embeddingsToCreate)) {
                foreach ($embeddingsToCreate as $embeddingData) {
                    Embedding::create($embeddingData);
                }
            }

            Log::info("Successfully processed direct embeddings for KnowledgeBase ID: {$this->knowledgeBase->id}");

        } catch (Exception $e) {
            Log::error("Failed to process direct embeddings for KnowledgeBase ID: {$this->knowledgeBase->id}. Error: ".$e->getMessage(), [
                'exception' => $e,
            ]);
            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        // Send user notification of failure, etc...
        Log::critical("ProcessDirectEmbeddings job failed for KnowledgeBase ID: {$this->knowledgeBase->id}", [
            'exception' => $exception,
        ]);
    }
}

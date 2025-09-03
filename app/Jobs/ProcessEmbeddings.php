<?php

namespace App\Jobs;

use App\Models\Embedding;
use App\Models\KnowledgeBase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use OpenAI;
use Pgvector\Laravel\Vector;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProcessEmbeddings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected KnowledgeBase $knowledgeBaseModel, protected string $media_id, protected $apiKey
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $media = Media::where('uuid', $this->media_id)->firstOrFail();

        try {

            $media->setCustomProperty('embedding_status', 'processing');
            $media->save();

            $filePath = $media->getPath();
            $texts    = [];
            $prompts  = [];

            $handle = fopen($filePath, 'r');
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    $data = json_decode($line, true);
                    if ($data === null || ! isset($data['messages']) || ! is_array($data['messages'])) {
                        throw new \Exception('Invalid JSON format in line: '.$line);
                    }

                    $userMessage      = null;
                    $assistantMessage = null;
                    foreach ($data['messages'] as $msg) {
                        if ($msg['role'] === 'user') {
                            $userMessage = $msg['content'];
                        } elseif ($msg['role'] === 'assistant') {
                            $assistantMessage = $msg['content'];
                        }
                    }

                    if ($userMessage === null || $assistantMessage === null) {
                        throw new \Exception('Missing user or assistant message in line: '.$line);
                    }

                    $texts[]   = $assistantMessage;
                    $prompts[] = $userMessage;
                }
                fclose($handle);
            } else {
                throw new \Exception('Unable to open file: '.$filePath);
            }

            $batchSize = 5;
            $chunks    = array_chunk($texts, $batchSize);

            foreach ($chunks as $chunkIndex => $chunk) {
                $response = OpenAI::client($this->apiKey)->embeddings()->create([
                    'model' => 'text-embedding-3-small',
                    'input' => $chunk,
                ]);

                $embeddings = $response->toArray()['data'];

                foreach ($embeddings as $i => $embeddingData) {
                    $originalIndex = $chunkIndex * $batchSize + $i;
                    Embedding::create([
                        'knowledge_base_id' => $this->knowledgeBaseModel->id,
                        'tenant_id'         => $this->knowledgeBaseModel->tenant_id,
                        'media_id'          => $media->id,
                        'embedding_model'   => 'text-embedding-3-small',
                        'source_text'       => $chunk[$i],
                        'embedding'         => new Vector($embeddingData['embedding']),
                        'metadata'          => [
                            'prompt'         => $prompts[$originalIndex],
                            'original_index' => $originalIndex,
                        ],
                    ]);
                }
            }

            $media->setCustomProperty('embedding_status', 'completed');
            $media->save();
        } catch (\Exception $e) {
            $media->setCustomProperty('embedding_status', 'failed');
            $media->setCustomProperty('embedding_error', $e->getMessage());
            $media->save();
            throw $e;
        }
    }
}

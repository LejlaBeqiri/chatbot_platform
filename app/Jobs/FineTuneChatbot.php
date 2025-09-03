<?php

namespace App\Jobs;

use App\Models\Chatbot;
use App\Models\Training;
use App\Values\MediaCollectionsValues;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use OpenAI\Laravel\Facades\OpenAI;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FineTuneChatbot implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Chatbot $chatbot, protected string $media_id
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $media = Media::where('uuid', $this->media_id)->where('collection_name', 'training_data')->first();

        if (! $media) {
            $this->fail(new \Exception('Training file not found in the training_data collection.'));

            return;
        }

        $filePath = $media->getPath();

        try {
            $fileResponse = OpenAI::files()->upload([
                'purpose' => 'fine-tune',
                'file'    => fopen($filePath, 'r'),
            ]);
            $fileId = $fileResponse->id;

            $fineTuneResponse = OpenAI::fineTuning()->createJob([
                'training_file'   => $fileId,
                'validation_file' => null,
                'model'           => 'gpt-4o-mini-2024-07-18',
                'hyperparameters' => [
                    'n_epochs' => 4,
                ],
                'suffix' => null,
            ]);
            $fineTuneId = $fineTuneResponse->id;

            $training = Training::create([
                'chatbot_id'   => $this->chatbot->id,
                'tenant_id'    => $this->chatbot->tenant_id,
                'fine_tune_id' => $fineTuneId,
                'status'       => 'pending',
            ]);

            $media->copy($training, MediaCollectionsValues::TRAINING_DATA);

            CheckFineTuneStatus::dispatch($training)->delay(now()->addMinutes(5));
        } catch (\Exception $e) {
            $this->chatbot->update(['status' => 'failed']);
            $this->fail($e);
        }
    }
}

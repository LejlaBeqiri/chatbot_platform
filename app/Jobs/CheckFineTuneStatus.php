<?php

namespace App\Jobs;

use App\Models\Training;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use OpenAI\Laravel\Facades\OpenAI;

class CheckFineTuneStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $training;

    public function __construct(Training $training)
    {
        $this->training = $training;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $statusResponse = OpenAI::fineTunes()->retrieve($this->training->fine_tune_id);
            $status         = $statusResponse->status;

            if ($status === 'succeeded') {
                $this->training->chatbot->update([
                    'ai_model_id' => $statusResponse->fineTunedModel,
                    'status'      => 'active',
                ]);
            } elseif ($status === 'failed') {
                $this->training->update(['status' => 'failed']);
                $this->fail(new \Exception('Fine-tuning job failed.'));
            } else {
                CheckFineTuneStatus::dispatch($this->training)->delay(now()->addMinutes(5));
            }
        } catch (\Exception $e) {
            $this->training->update(['status' => 'failed']);
            $this->fail($e);
        }
    }
}

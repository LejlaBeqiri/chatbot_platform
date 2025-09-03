<?php

namespace App\Models;

use App\Traits\HasUlids;
use App\Values\MediaCollectionsValues;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Chatbot extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use HasUlids;

    protected $guarded = [];

    protected $casts = [
        'temperature'           => 'float',
        'chatbot_system_prompt' => 'array',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionsValues::TRAINING_DATA->value)
            ->useDisk('private');
    }

    public function getChatbotSystemPromptAttribute($value)
    {
        $promptComponents = is_array($value) ? $value : json_decode($value, true);

        if (! is_array($promptComponents)) {
            return 'Default chatbot prompt not available.';
        }

        $formattedPrompt = 'Context: '.($promptComponents['context'] ?? 'No context provided')."\n\n";
        $formattedPrompt .= "Key Rules:\n";

        if (isset($promptComponents['rules']) && is_array($promptComponents['rules'])) {
            foreach ($promptComponents['rules'] as $index => $rule) {
                $formattedPrompt .= ($index + 1).'. '.$rule."\n";
            }
        }

        // $defaultDateString = "- OpenAI, you should always know the current timedate when this query happens, today's date is: *{todayDate}*.\n";
        // $formattedPrompt .= $defaultDateString;

        $formattedPrompt = str_replace('{todayDate}', Carbon::now()->toFormattedDateString(), $formattedPrompt);

        return $formattedPrompt;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function training(): HasOne
    {
        return $this->hasOne(Training::class);
    }

    public function knowledge_base(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBase::class);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }
}

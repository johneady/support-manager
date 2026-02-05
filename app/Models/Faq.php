<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Faq extends Model
{
    /** @use HasFactory<\Database\Factories\FaqFactory> */
    use HasFactory;

    protected $fillable = [
        'question',
        'slug',
        'answer',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Faq $faq) {
            if (empty($faq->slug)) {
                $faq->slug = static::generateUniqueSlug($faq->question);
            }
        });

        static::updating(function (Faq $faq) {
            if ($faq->isDirty('question')) {
                $faq->slug = static::generateUniqueSlug($faq->question, $faq->id);
            }
        });
    }

    /**
     * Generate a unique slug from the given text.
     */
    protected static function generateUniqueSlug(string $text, ?int $excludeId = null): string
    {
        $slug = Str::slug($text);
        $original = $slug;
        $counter = 1;

        while (static::query()
            ->where('slug', $slug)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists()
        ) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get the rendered markdown HTML for the answer.
     */
    public function renderedAnswer(): string
    {
        return Str::markdown($this->answer);
    }

    /**
     * Get a plain-text summary of the answer.
     */
    public function summary(int $length = 150): string
    {
        return Str::limit(strip_tags($this->renderedAnswer()), $length);
    }

    /**
     * Get the estimated reading time in minutes.
     */
    public function readingTime(): int
    {
        $wordCount = str_word_count(strip_tags($this->renderedAnswer()));

        return max(1, (int) ceil($wordCount / 200));
    }

    /**
     * Get the route key name for route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @param  Builder<Faq>  $query
     * @return Builder<Faq>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * @param  Builder<Faq>  $query
     * @return Builder<Faq>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}

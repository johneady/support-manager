<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    /** @use HasFactory<\Database\Factories\TicketFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject',
        'description',
        'status',
        'priority',
        'ticket_category_id',
        'closed_at',
        'ticket_reference_number',
    ];

    protected static function booted(): void
    {
        static::created(function (Ticket $ticket) {
            if (empty($ticket->ticket_reference_number)) {
                $ticket->update(['ticket_reference_number' => sprintf('TX-1138-%06d', $ticket->id)]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<TicketCategory, $this>
     */
    public function ticketCategory(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class);
    }

    /**
     * @return HasMany<TicketReply, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class);
    }

    /**
     * @param  Builder<Ticket>  $query
     * @return Builder<Ticket>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', TicketStatus::Open);
    }

    /**
     * @param  Builder<Ticket>  $query
     * @return Builder<Ticket>
     */
    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', TicketStatus::Closed);
    }

    /**
     * @param  Builder<Ticket>  $query
     * @return Builder<Ticket>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to tickets that need an admin response.
     * A ticket needs a response if it has no replies, or if the last reply was from the customer.
     *
     * @param  Builder<Ticket>  $query
     * @return Builder<Ticket>
     */
    public function scopeNeedsResponse(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereDoesntHave('replies')
                ->orWhereHas('replies', function (Builder $subQuery) {
                    $subQuery->where('id', function ($latestQuery) {
                        $latestQuery->selectRaw('MAX(id)')
                            ->from('ticket_replies')
                            ->whereColumn('ticket_id', 'tickets.id');
                    })->where('is_from_admin', false);
                });
        });
    }

    /**
     * Check if this ticket needs an admin response.
     */
    public function needsResponse(): bool
    {
        // Use eager-loaded replies if available, otherwise query the database
        if ($this->relationLoaded('replies')) {
            $replies = $this->replies;

            return $replies->isEmpty() || ! $replies->first()?->is_from_admin;
        }

        $lastReply = $this->replies()->latest()->first();

        return $lastReply === null || ! $lastReply->is_from_admin;
    }

    public function close(): void
    {
        $this->update([
            'status' => TicketStatus::Closed,
            'closed_at' => now(),
        ]);
    }

    public function reopen(): void
    {
        $this->update([
            'status' => TicketStatus::Open,
            'closed_at' => null,
        ]);
    }

    /**
     * Get the ticket's reference number.
     */
    public function getReferenceNumberAttribute(): string
    {
        return sprintf('TX-1138-%06d', $this->id);
    }
}

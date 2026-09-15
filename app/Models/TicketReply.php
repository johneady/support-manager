<?php

namespace App\Models;

use Database\Factories\TicketReplyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketReply extends Model
{
    /** @use HasFactory<TicketReplyFactory> */
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'body',
        'is_from_admin',
    ];

    protected function casts(): array
    {
        return [
            'is_from_admin' => 'boolean',
        ];
    }

    /**
     * Order replies chronologically so a conversation reads as a back-and-forth
     * thread instead of being grouped by author.
     *
     * @param  Builder<TicketReply>  $query
     * @return Builder<TicketReply>
     */
    public function scopeChronological(Builder $query): Builder
    {
        return $query->orderBy('created_at')->orderBy('id');
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketCategory extends Model
{
    /** @use HasFactory<\Database\Factories\TicketCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Scope to only active categories.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TicketCategory>  $query
     * @return \Illuminate\Database\Eloquent\Builder<TicketCategory>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort_order.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TicketCategory>  $query
     * @return \Illuminate\Database\Eloquent\Builder<TicketCategory>
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}

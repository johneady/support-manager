<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'zinc',
            self::Medium => 'amber',
            self::High => 'red',
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::High => 1,
            self::Medium => 2,
            self::Low => 3,
        };
    }

    /**
     * Generate a SQL CASE expression for ordering by priority.
     */
    public static function orderBySql(): string
    {
        $cases = collect(self::cases())
            ->map(fn (self $p) => "WHEN '{$p->value}' THEN {$p->sortOrder()}")
            ->implode(' ');

        return "CASE priority {$cases} END";
    }
}

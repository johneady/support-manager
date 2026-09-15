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
     *
     * Written as a literal so orderByRaw()'s literal-string requirement is
     * satisfied while the value() and sortOrder() mappings stay the single
     * source of truth; the unit test asserts this literal matches what those
     * mappings would generate.
     *
     * @return literal-string
     */
    public static function orderBySql(): string
    {
        return "CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END";
    }
}

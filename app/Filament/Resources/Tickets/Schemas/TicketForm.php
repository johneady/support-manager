<?php

namespace App\Filament\Resources\Tickets\Schemas;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('status')
                    ->options(TicketStatus::class)
                    ->required(),
                Select::make('priority')
                    ->options(TicketPriority::class)
                    ->required(),
            ]);
    }
}

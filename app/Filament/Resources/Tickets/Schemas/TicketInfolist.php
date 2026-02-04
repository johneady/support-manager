<?php

namespace App\Filament\Resources\Tickets\Schemas;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ticket Details')
                    ->schema([
                        TextEntry::make('subject'),
                        TextEntry::make('user.name')
                            ->label('Customer'),
                        TextEntry::make('user.email')
                            ->label('Email'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (TicketStatus $state): string => $state->color()),
                        TextEntry::make('priority')
                            ->badge()
                            ->color(fn (TicketPriority $state): string => $state->color()),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
                Section::make('Description')
                    ->schema([
                        TextEntry::make('description')
                            ->prose()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

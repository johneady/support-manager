<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer Information')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email'),
                        TextEntry::make('created_at')
                            ->label('Registered')
                            ->dateTime(),
                        TextEntry::make('tickets_count')
                            ->label('Total Tickets')
                            ->state(fn ($record) => $record->tickets()->count()),
                    ])
                    ->columns(2),
            ]);
    }
}

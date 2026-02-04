<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use App\Notifications\TicketReplyNotification;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected string $view = 'filament.resources.tickets.pages.view-ticket';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label('Add Reply')
                ->color('primary')
                ->form([
                    Textarea::make('body')
                        ->label('Reply')
                        ->required()
                        ->rows(5),
                ])
                ->action(function (array $data): void {
                    /** @var Ticket $ticket */
                    $ticket = $this->getRecord();

                    $reply = $ticket->replies()->create([
                        'user_id' => auth()->id(),
                        'body' => $data['body'],
                        'is_from_admin' => true,
                    ]);

                    $ticket->user->notify(new TicketReplyNotification($reply));

                    Notification::make()
                        ->title('Reply sent successfully')
                        ->success()
                        ->send();
                }),
            Action::make('close')
                ->label('Close Ticket')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord()->status->value === 'open')
                ->action(function (): void {
                    $this->getRecord()->close();

                    Notification::make()
                        ->title('Ticket closed')
                        ->success()
                        ->send();
                }),
            Action::make('reopen')
                ->label('Reopen Ticket')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord()->status->value === 'closed')
                ->action(function (): void {
                    $this->getRecord()->reopen();

                    Notification::make()
                        ->title('Ticket reopened')
                        ->success()
                        ->send();
                }),
            EditAction::make(),
        ];
    }
}

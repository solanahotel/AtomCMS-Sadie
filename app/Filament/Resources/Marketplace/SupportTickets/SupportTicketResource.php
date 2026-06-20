<?php

namespace App\Filament\Resources\Marketplace\SupportTickets;

use App\Filament\Resources\Marketplace\SupportTickets\Pages\ManageSupportTickets;
use App\Models\Marketplace\SupportTicket;
use App\Models\Marketplace\SupportTicketBan;
use App\Models\Marketplace\SupportTicketMessage;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-lifebuoy';
    protected static string|\UnitEnum|null $navigationGroup = 'Marketplace';
    protected static ?string $navigationLabel = 'Support Tickets';
    protected static ?string $slug = 'marketplace/support-tickets';

    public static function form(Schema $schema): Schema { return $schema->components([]); }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('updated_at', 'desc')->columns([
            TextColumn::make('id')->label('#')->sortable(),
            TextColumn::make('player.username')->label('User')->searchable(),
            TextColumn::make('subject')->label('Subject')->searchable()->limit(40),
            TextColumn::make('category')->label('Category'),
            TextColumn::make('status')->label('Status')->badge()
                ->color(fn (string $state): string => $state === 'open' ? 'success' : 'gray'),
            TextColumn::make('messages_count')->counts('messages')->label('Msgs'),
            TextColumn::make('updated_at')->label('Updated')->dateTime('Y-m-d H:i')->sortable(),
        ])->filters([
            SelectFilter::make('status')->options(['open' => 'Open', 'closed' => 'Closed']),
        ])->recordActions([
            Action::make('view')
                ->label('Conversation')->icon('heroicon-o-chat-bubble-left-right')
                ->modalHeading(fn (SupportTicket $record) => "Ticket #{$record->id}: {$record->subject}")
                ->modalContent(fn (SupportTicket $record) => view('filament.support-conversation', ['ticket' => $record->load('messages')]))
                ->modalSubmitAction(false)->modalCancelActionLabel('Close'),
            Action::make('reply')
                ->label('Reply')->icon('heroicon-o-paper-airplane')->color('success')
                ->visible(fn (SupportTicket $record) => $record->status === 'open')
                ->schema([ Textarea::make('body')->label('Reply')->required()->rows(4) ])
                ->action(function (array $data, SupportTicket $record): void {
                    $admin = auth()->user();
                    SupportTicketMessage::create([
                        'ticket_id' => $record->id, 'sender_id' => $admin->id, 'sender_name' => $admin->username,
                        'is_staff' => 1, 'body' => $data['body'], 'created_at' => now(),
                    ]);
                    $record->forceFill(['updated_at' => now()])->save();
                    DB::table('player_inbox')->insert([
                        'player_id' => $record->player_id, 'category' => 'support',
                        'title' => "Staff replied to ticket #{$record->id}",
                        'body' => 'Open Help → Support to read the reply.', 'is_read' => 0, 'created_at' => now(),
                    ]);
                    Notification::make()->title('Reply sent')->success()->send();
                }),
            Action::make('toggleStatus')
                ->label(fn (SupportTicket $record) => $record->status === 'open' ? 'Close' : 'Reopen')
                ->icon('heroicon-o-lock-closed')
                ->action(function (SupportTicket $record): void {
                    $open = $record->status === 'open';
                    $record->forceFill(['status' => $open ? 'closed' : 'open', 'closed_by' => $open ? 'staff' : null, 'updated_at' => now()])->save();
                }),
            Action::make('restrict')
                ->label(fn (SupportTicket $record) => $record->isRestricted() ? 'Unrestrict' : 'Restrict')
                ->icon('heroicon-o-no-symbol')->color('danger')->requiresConfirmation()
                ->modalDescription('Restricted users cannot open new tickets.')
                ->action(function (SupportTicket $record): void {
                    if ($record->isRestricted()) {
                        SupportTicketBan::where('player_id', $record->player_id)->delete();
                        Notification::make()->title('Restriction lifted')->success()->send();
                    } else {
                        SupportTicketBan::create(['player_id' => $record->player_id, 'reason' => 'By ' . auth()->user()->username, 'created_at' => now()]);
                        Notification::make()->title('User restricted from tickets')->success()->send();
                    }
                }),
        ])->toolbarActions([]);
    }

    public static function getPages(): array { return ['index' => ManageSupportTickets::route('/')]; }
}

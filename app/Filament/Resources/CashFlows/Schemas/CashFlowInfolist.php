<?php

namespace App\Filament\Resources\CashFlows\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CashFlowInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('shift.name')
                    ->label('Shift')
                    ->placeholder('-'),
                TextEntry::make('type'),
                TextEntry::make('amount')
                    ->numeric(),
                TextEntry::make('description'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}

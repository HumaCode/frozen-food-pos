<?php

namespace App\Filament\Resources\CashFlows;

use App\Filament\Resources\CashFlows\Pages\CreateCashFlow;
use App\Filament\Resources\CashFlows\Pages\EditCashFlow;
use App\Filament\Resources\CashFlows\Pages\ListCashFlows;
use App\Filament\Resources\CashFlows\Pages\ViewCashFlow;
use App\Filament\Resources\CashFlows\Schemas\CashFlowForm;
use App\Filament\Resources\CashFlows\Schemas\CashFlowInfolist;
use App\Filament\Resources\CashFlows\Tables\CashFlowsTable;
use App\Models\CashFlow;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CashFlowResource extends Resource
{
    protected static ?string $model = CashFlow::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'description';

    public static function form(Schema $schema): Schema
    {
        return CashFlowForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CashFlowInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashFlowsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashFlows::route('/'),
            'create' => CreateCashFlow::route('/create'),
            'view' => ViewCashFlow::route('/{record}'),
            'edit' => EditCashFlow::route('/{record}/edit'),
        ];
    }
}

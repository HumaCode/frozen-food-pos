<?php

namespace App\Filament\Resources\CashFlows\Pages;

use App\Filament\Resources\CashFlows\CashFlowResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCashFlow extends ViewRecord
{
    protected static string $resource = CashFlowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

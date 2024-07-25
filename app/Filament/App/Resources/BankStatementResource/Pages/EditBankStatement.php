<?php

namespace App\Filament\Admin\Resources\BankStatementResource\Pages;

use App\Filament\Admin\Resources\BankStatementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBankStatement extends EditRecord
{
    protected static string $resource = BankStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
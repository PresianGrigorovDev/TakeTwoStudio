<?php

namespace App\Filament\Resources\PromPackageResource\Pages;

use App\Filament\Resources\PromPackageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPromPackage extends EditRecord
{
    protected static string $resource = PromPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

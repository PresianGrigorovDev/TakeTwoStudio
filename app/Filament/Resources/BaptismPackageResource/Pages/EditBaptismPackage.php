<?php

namespace App\Filament\Resources\BaptismPackageResource\Pages;

use App\Filament\Resources\BaptismPackageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBaptismPackage extends EditRecord
{
    protected static string $resource = BaptismPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BaptismPackageResource\Pages;
use App\Filament\Support\ServiceScopedPackageResource;

/** Кръщения → Пакети и цени: the packages of the "baptism" service (service_packages). */
class BaptismPackageResource extends ServiceScopedPackageResource
{
    protected static string $serviceSlug = 'baptism';

    protected static ?string $navigationGroup = 'Кръщения';

    protected static ?int $navigationSort = 2;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBaptismPackages::route('/'),
            'create' => Pages\CreateBaptismPackage::route('/create'),
            'edit' => Pages\EditBaptismPackage::route('/{record}/edit'),
        ];
    }
}

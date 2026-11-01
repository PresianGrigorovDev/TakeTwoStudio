<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WeddingPackageResource\Pages;
use App\Filament\Support\ServiceScopedPackageResource;

/** Сватби → Пакети и цени: the packages of the "weddings" service (service_packages). */
class WeddingPackageResource extends ServiceScopedPackageResource
{
    protected static string $serviceSlug = 'weddings';

    protected static ?string $navigationGroup = 'Сватби';

    protected static ?int $navigationSort = 2;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWeddingPackages::route('/'),
            'create' => Pages\CreateWeddingPackage::route('/create'),
            'edit' => Pages\EditWeddingPackage::route('/{record}/edit'),
        ];
    }
}

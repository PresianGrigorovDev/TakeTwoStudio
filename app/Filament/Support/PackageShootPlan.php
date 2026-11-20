<?php

namespace App\Filament\Support;

use App\Filament\Resources\ServicePackageResource;
use App\Models\OrderShoot;
use App\Models\Service;
use App\Models\ServicePackage;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;

final class PackageShootPlan
{
    public static function items(): Repeater
    {
        return Repeater::make('shoot_plan')
            ->label('Заснемания за проследяване')
            ->helperText('Добавете по един ред за всяка фотосесия или събитие. Редовете се копират при избор на пакета в поръчката.')
            ->schema(self::fields())
            ->defaultItems(0)
            ->columns(['sm' => 2])
            ->addActionLabel('Добави заснемане')
            ->itemLabel(fn (array $state) => $state['name'] ?? null)
            ->reorderableWithButtons()
            ->columnSpanFull();
    }

    public static function fields(): array
    {
        return [
            TextInput::make('name')->label('Име')->required()->maxLength(255),
            Select::make('kind')->label('Вид')->options(OrderShoot::KINDS)->in(array_keys(OrderShoot::KINDS))->required(),
        ];
    }

    public static function options(): array
    {
        $options = [];
        $serviceNames = Service::pluck('name_bg', 'slug');

        foreach (ServiceHub::packageResources() as $slug => $resource) {
            if ($resource::getModel() === ServicePackage::class) {
                continue;
            }

            $group = $serviceNames[$slug] ?? $resource::getNavigationGroup() ?? 'Пакети на услуги';
            $packages = $resource::getEloquentQuery()
                ->whereJsonLength('shoot_plan', '>', 0)
                ->orderBy('sort_order')
                ->get(['id', 'name']);

            foreach ($packages as $package) {
                $options[$group]["{$slug}:{$package->id}"] = $package->name;
            }
        }

        $packages = ServicePackage::with('service:id,name_bg')
            ->whereJsonLength('shoot_plan', '>', 0)
            ->orderBy('name_bg')
            ->get(['id', 'service_id', 'name_bg']);

        foreach ($packages as $package) {
            $group = $package->service?->name_bg ?? 'Пакети на услуги';
            $options[$group]["service:{$package->id}"] = $package->name_bg;
        }

        return $options;
    }

    public static function find(string $value): ?Model
    {
        [$type, $id] = array_pad(explode(':', $value, 2), 2, '');

        if (! ctype_digit($id)) {
            return null;
        }

        $resource = $type === 'service'
            ? ServicePackageResource::class
            : (ServiceHub::packageResources()[$type] ?? null);

        return $resource ? $resource::getEloquentQuery()->find($id) : null;
    }
}

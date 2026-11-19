<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderShoot;
use App\Models\PromPackage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class May2026PromClassesSeeder extends Seeder
{
    private const CLASSES = [
        ['Спортно', '2026-05-23', 'Лукс'],
        ['МГ', '2026-05-24', 'Лукс'],
        ['Гео Милев', '2026-05-25', 'Лукс'],
        ['Пета', '2026-05-26', 'Лукс'],
        ['Електро', '2026-05-27', 'Парти'],
    ];

    public function run(): void
    {
        if (! Schema::hasTable('order_shoots')
            || ! Schema::hasColumn('orders', 'shoot_package_name')
            || ! Schema::hasColumn('prom_packages', 'shoot_plan')) {
            throw new RuntimeException('Първо изпълнете миграцията 2026_11_19_110000_add_order_shoots.');
        }

        $events = [
            ['kind' => OrderShoot::EVENT, 'name' => 'Канене на класния'],
            ['kind' => OrderShoot::EVENT, 'name' => 'Изпращане'],
            ['kind' => OrderShoot::EVENT, 'name' => 'Бал'],
        ];

        $plans = [
            'Лукс' => [
                ['kind' => OrderShoot::SESSION, 'name' => 'Фотосесия 1'],
                ['kind' => OrderShoot::SESSION, 'name' => 'Фотосесия 2'],
                ...$events,
            ],
            'Парти' => [
                ['kind' => OrderShoot::SESSION, 'name' => 'Свободна фотосесия'],
                ...$events,
            ],
        ];

        DB::transaction(function () use ($plans): void {
            $catalog = PromPackage::orderBy('id')->lockForUpdate()->get()
                ->groupBy(fn (PromPackage $package) => self::packageType($package->name));

            $packages = [];
            foreach ($plans as $type => $plan) {
                $matches = $catalog->get($type, collect());
                $visible = $matches->where('is_visible', true);
                $choices = $visible->isNotEmpty() ? $visible : $matches;

                if ($choices->count() !== 1) {
                    throw new RuntimeException("Проверете кой е актуалният пакет „{$type}“ в „Абитуриенти → Пакети и цени“.");
                }

                $package = $choices->first();
                if (blank($package->shoot_plan)) {
                    $package->update(['shoot_plan' => $plan]);
                }
                $packages[$type] = $package;
            }

            foreach (self::CLASSES as [$name, $date, $type]) {
                $matches = Order::where('name', $name)->where('event_date', $date)->lockForUpdate()->get();
                if ($matches->count() > 1) {
                    throw new RuntimeException("Има повече от една поръчка за „{$name}“ на {$date}.");
                }

                $order = $matches->first() ?? new Order([
                    'name' => $name,
                    'phone' => '',
                    'service_type' => 'Абитуриентски Бал',
                    'event_date' => $date,
                    'status' => 'accepted',
                ]);

                $currentPackage = $order->shoot_package_name;
                if ($order->service_type !== 'Абитуриентски Бал'
                    || (filled($currentPackage) && self::packageType($currentPackage) !== $type)) {
                    throw new RuntimeException("Проверете услугата и пакета на съществуващата поръчка за „{$name}“ на {$date}.");
                }

                $package = $packages[$type];
                if (blank($currentPackage)) {
                    $order->shoot_package_name = $package->name;
                }
                $order->save();

                if (! $order->shoots()->exists()) {
                    $order->shoots()->createMany($package->shoot_plan);
                }
            }
        });

        $this->command?->info('Готово. В поръчките са подготвени петте класа за 23–27 май 2026 г.');
    }

    private static function packageType(string $name): ?string
    {
        return match (Str::lower(Str::squish($name))) {
            'лукс', 'лукс пакет', 'пакет лукс' => 'Лукс',
            'парти', 'парти пакет', 'пакет парти' => 'Парти',
            default => null,
        };
    }
}

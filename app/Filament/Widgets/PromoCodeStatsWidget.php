<?php

namespace App\Filament\Widgets;

use App\Models\PromoCode;
use App\Models\Order;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class PromoCodeStatsWidget extends Widget
{
    protected static string $view = 'filament.widgets.promo-code-stats';

    protected static ?int $sort = 10;

    protected int | string | array $columnSpan = 'full';

    public function getStats(): array
    {
        // Top codes by order count
        $topCodes = PromoCode::withCount('orders')
            ->having('orders_count', '>', 0)
            ->orderByDesc('orders_count')
            ->limit(10)
            ->get();

        // Revenue per promo code (sum of price on orders)
        $revenueByCode = Order::whereNotNull('promo_code')
            ->select('promo_code', DB::raw('COUNT(*) as orders_count'), DB::raw('SUM(price) as total_revenue'), DB::raw('SUM(discount_amount) as total_discounted'))
            ->groupBy('promo_code')
            ->orderByDesc('total_revenue')
            ->get();

        // Orders per source channel
        $bySource = PromoCode::select('source', DB::raw('SUM(uses_count) as total_uses'))
            ->whereNotNull('source')
            ->where('uses_count', '>', 0)
            ->groupBy('source')
            ->orderByDesc('total_uses')
            ->get();

        return [
            'top_codes'      => $topCodes,
            'revenue_by_code' => $revenueByCode,
            'by_source'      => $bySource,
        ];
    }

    protected function getViewData(): array
    {
        return [
            'stats' => $this->getStats(),
        ];
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class CompleteExpiredOrders extends Command
{
    protected $signature = 'orders:complete-expired';

    protected $description = 'Маркира приетите поръчки като завършени, когато датата на събитието е минала';

    public function handle()
    {
        $count = Order::where('status', 'accepted')
            ->whereNotNull('event_date')
            ->where('event_date', '<', now()->startOfDay())
            ->update(['status' => 'completed']);

        $this->info("Завършени поръчки: {$count}");
    }
}

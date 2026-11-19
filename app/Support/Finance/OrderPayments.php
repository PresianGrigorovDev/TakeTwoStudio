<?php

namespace App\Support\Finance;

use App\Models\FinanceMovement;
use App\Models\FinanceProject;
use App\Models\Order;
use App\Models\User;
use Closure;
use Illuminate\Validation\ValidationException;

final class OrderPayments
{
    public function __construct(private Ledger $ledger) {}

    /** @param Order|Closure(): Order $source */
    public function record(Order|Closure $source, array $payment, User $actor, bool $accept = false): FinanceMovement
    {
        abort_unless($actor->isAdmin(), 403);

        return $this->ledger->record(function () use ($source, $accept): FinanceProject {
            $order = $source instanceof Closure ? $source() : $source->refresh();
            if ($order->status === 'cancelled') {
                throw ValidationException::withMessages(['amount' => 'Поръчката е отказана.']);
            }

            if ($accept && in_array($order->status, ['new', 'contacted'], true)) {
                $order->update(['status' => 'accepted']);
            }

            $project = FinanceProject::forOrder($order);
            if ($project) {
                return $project;
            }

            $data = Projects::defaults($order);

            return FinanceProject::create([
                'kind' => 'order',
                'title' => $order->name,
                'order_id' => $order->id,
                'agreed_cents' => $order->price !== null && $order->price >= 0 ? Euro::parse($order->price, 'agreed', allowZero: true) : null,
                'shares' => collect($data['participants'])->mapWithKeys(fn (array $participant) => [$participant['account_id'] => 1])->all(),
            ]);
        }, ['kind' => FinanceMovement::INCOME] + $payment, $actor);
    }
}

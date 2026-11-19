<?php

namespace App\Support\Finance;

use App\Models\Booking;
use App\Models\FinanceAccount;
use App\Models\FinanceProject;
use App\Models\Order;
use Illuminate\Validation\ValidationException;

final class Projects
{
    public function __construct(private Ledger $ledger) {}

    public static function defaults(Order|Booking $source): array
    {
        $members = $source->teamMembers()->pluck('team_members.id');
        if ($source instanceof Booking && $source->order) {
            $source = $source->order;
            if ($members->isEmpty()) {
                $members = $source->teamMembers()->pluck('team_members.id');
            }
        }

        if ($source instanceof Order && $members->isEmpty()) {
            $members = $source->bookings()->with('teamMembers')->get()->flatMap(fn (Booking $booking) => $booking->teamMembers->modelKeys());
        }

        $accounts = FinanceAccount::whereIn('team_member_id', $members)->orderBy('id')->pluck('id');

        return [
            'order_id' => $source instanceof Order ? $source->id : null,
            'booking_id' => $source instanceof Booking ? $source->id : null,
            'title' => $source->name,
            'agreed' => $source instanceof Order ? $source->price : null,
            'participants' => $accounts->map(fn ($id) => ['account_id' => $id, 'percentage' => null])->all(),
        ];
    }

    public function save(?FinanceProject $project, array $input): FinanceProject
    {
        return $this->ledger->transaction(function () use ($project, $input) {
            $existing = $project?->refresh();
            $attributes = [
                'title' => $input['title'],
                'shares' => self::weights($input['participants'] ?? [], $existing?->isStudio() ?? false),
                'agreed_cents' => filled($input['agreed'] ?? null) ? Euro::parse($input['agreed'], 'agreed', true) : null,
            ];

            if ($existing) {
                $existing->update($attributes);

                return $existing;
            }

            $order = Order::find($input['order_id'] ?? null);
            $booking = Booking::find($input['booking_id'] ?? null);
            if (($order && $booking) || (! $order && ! $booking)) {
                throw ValidationException::withMessages(['order_id' => 'Изберете една поръчка или един ангажимент.']);
            }
            if ($booking && ! Booking::isClientKind($booking->kind)) {
                throw ValidationException::withMessages(['booking_id' => 'Изберете клиентски ангажимент или фотосесия.']);
            }
            $order ??= $booking?->order;
            $associated = $booking ? FinanceProject::forBooking($booking) : FinanceProject::forOrder($order);
            if ($associated) {
                throw ValidationException::withMessages(['order_id' => 'Вече има финансов проект за този запис. Отворете го от „Финанси“ в поръчката или ангажимента.']);
            }

            return FinanceProject::create([
                ...$attributes,
                'order_id' => $order?->id,
                'booking_id' => $order ? null : $booking->id,
                'kind' => $order ? 'order' : 'booking',
            ]);
        });
    }

    private static function weights(array $participants, bool $optional): array
    {
        if ($participants === []) {
            if (! $optional) {
                throw ValidationException::withMessages(['participants' => 'Добавете поне един участник.']);
            }

            return [];
        }

        $members = FinanceAccount::where('kind', 'member')->pluck('id')->flip();
        $hasPercentages = collect($participants)->contains(fn ($row) => filled($row['percentage'] ?? null));
        $weights = [];
        foreach ($participants as $person) {
            $account = (int) ($person['account_id'] ?? 0);
            if (! $members->has($account) || isset($weights[$account])) {
                throw ValidationException::withMessages(['participants' => 'Изберете различен член на екипа за всеки ред.']);
            }

            $weights[$account] = $hasPercentages ? Euro::parse($person['percentage'] ?? null, 'participants') : 1;
        }

        if ($hasPercentages && array_sum($weights) !== 10000) {
            throw ValidationException::withMessages(['participants' => 'Сборът на процентите трябва да е 100%.']);
        }

        ksort($weights);

        return $weights;
    }
}

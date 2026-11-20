<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Order;
use Illuminate\Support\Arr;

final class OrderCalendar
{
    private const SCHEDULE = ['event_date', 'start_time', 'end_time'];

    private const CLIENT = ['name', 'phone', 'email', 'service_type'];

    /** Call in the transaction that saves the order and its team. */
    public static function fromOrder(Order $order): ?Booking
    {
        $booking = $order->booking()->lockForUpdate()->first();

        if (! $order->event_date) {
            if ($booking) {
                $booking->status = 'cancelled';
                $booking->is_primary_for_order = null;
                $booking->save();
            }

            return null;
        }
        if (! $booking && $order->status === 'cancelled') {
            return null;
        }
        if (! $booking && $order->bookings()->active()->where('kind', Booking::KIND_CLIENT)->whereDoesntHave('orderShoot')->exists()) {
            return null;
        }

        $booking ??= $order->bookings()->make([
            'kind' => Booking::KIND_CLIENT,
            'message' => $order->details,
        ]);

        $booking->is_primary_for_order = true;
        if (! $booking->exists || $order->wasChanged('status')) {
            $booking->status = match ($order->status) {
                'accepted', 'completed' => 'confirmed',
                'cancelled' => 'cancelled',
                default => 'pending',
            };
        }

        if ($booking->end_date && $booking->event_date) {
            $days = (int) $booking->event_date->diffInDays($booking->last_date);
            $booking->end_date = $order->event_date->copy()->addDays($days);
        }

        $client = Arr::only($booking->exists ? $order->getChanges() : $order->getAttributes(), self::CLIENT);
        $booking->fill(Arr::only($order->getAttributes(), self::SCHEDULE) + $client)->save();
        $booking->teamMembers()->sync($order->teamMembers()->pluck('team_members.id'));

        return $booking;
    }

    /** A client entry linked in the calendar can become the first main entry of its order. */
    public static function fromBooking(Booking $booking, bool $mayBecomePrimary = false): void
    {
        if (! $booking->order_id || $booking->kind !== Booking::KIND_CLIENT) {
            return;
        }
        if (! $booking->is_primary_for_order && ! $mayBecomePrimary) {
            return;
        }

        $order = Order::query()->lockForUpdate()->findOrFail($booking->order_id);
        $client = $booking->is_primary_for_order ? Arr::only($booking->getChanges(), self::CLIENT) : [];
        if (! $booking->is_primary_for_order) {
            if ($booking->orderShoot()->exists()) {
                return;
            }

            $previous = $order->booking()->lockForUpdate()->first();
            if ($previous) {
                if ($previous->hasHours() || ! $previous->isActive() || ! $booking->isActive() || ! $booking->hasHours()
                    || ! $previous->event_date->isSameDay($booking->event_date)) {
                    return;
                }

                $previous->orderShoot()->first()?->update(['booking_id' => $booking->id]);
                $previous->is_primary_for_order = null;
                $previous->status = 'cancelled';
                $previous->save();
            }

            $booking->is_primary_for_order = true;
            $booking->save();
        }

        if (array_key_exists('phone', $client)) {
            $client['phone'] ??= '';
        }
        if (array_key_exists('service_type', $client)) {
            $client['service_type'] ??= $order->service_type;
        }

        if ($booking->status === 'confirmed' && in_array($order->status, ['new', 'contacted'], true)) {
            $client['status'] = 'accepted';
        }

        $order->fill($client);
        self::copySchedule($booking, $order);
    }

    public static function connectExisting(Order $order): void
    {
        if ($order->booking()->exists()) {
            return;
        }

        // Earlier calendar entries copied the order's title, and package shoots have their own titles.
        $bookings = $order->bookings()->where('kind', Booking::KIND_CLIENT)->with('orderShoot')->get();
        $candidates = $bookings->filter(fn (Booking $booking) => ! $booking->orderShoot || $booking->name === $order->name);
        $sameDay = $candidates->filter(fn (Booking $booking) => $booking->event_date->toDateString() === $order->event_date?->toDateString());
        $sameSlot = $sameDay->filter(fn (Booking $booking) => $booking->start_time === $order->start_time && $booking->end_time === $order->end_time);
        $sameName = $candidates->where('name', $order->name);
        $booking = match (true) {
            $sameSlot->count() === 1 => $sameSlot->first(),
            $sameDay->count() === 1 => $sameDay->first(),
            $sameName->count() === 1 => $sameName->first(),
            ! $order->event_date && $candidates->count() === 1 => $candidates->first(),
            default => null,
        };

        if ($booking) {
            $booking->is_primary_for_order = true;
            $booking->save();
            if (! $booking->teamMembers()->exists()) {
                $booking->teamMembers()->sync($order->teamMembers()->pluck('team_members.id'));
            }
            self::copySchedule($booking, $order);
        } elseif ($candidates->isEmpty()) {
            self::fromOrder($order);
        }
    }

    private static function copySchedule(Booking $booking, Order $order): void
    {
        $order->fill(Arr::only($booking->getAttributes(), self::SCHEDULE))->save();
        $order->teamMembers()->sync($booking->teamMembers()->pluck('team_members.id'));
    }
}

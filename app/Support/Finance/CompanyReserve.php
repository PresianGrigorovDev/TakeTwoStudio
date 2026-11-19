<?php

namespace App\Support\Finance;

use Illuminate\Validation\ValidationException;

final class CompanyReserve
{
    public static function basisPoints(mixed $percentage): int
    {
        if ($percentage === null || $percentage === '') {
            return 0;
        }

        try {
            $points = Euro::parse($percentage, 'company_percentage', allowZero: true);
            if ($points <= 10000) {
                return $points;
            }
        } catch (ValidationException) {
            // Use the same percentage error for invalid format and range.
        }

        throw ValidationException::withMessages(['company_percentage' => 'Въведете процент от 0 до 100 с до два знака след десетичния разделител.']);
    }

    public static function cents(int $amount, int $basisPoints): int
    {
        return intdiv($amount * $basisPoints + 5000, 10000);
    }

    /** A refund consumes the oldest outstanding receipts and releases their reserve. */
    public static function release(int $amount, array &$receipts): int
    {
        $released = 0;

        foreach ($receipts as $id => [$balance, $rate]) {
            if ($amount === 0) {
                break;
            }
            $portion = min($amount, $balance);
            $remaining = $balance - $portion;
            $released += self::cents($balance, $rate) - self::cents($remaining, $rate);
            $amount -= $portion;

            if ($remaining === 0) {
                unset($receipts[$id]);
            } else {
                $receipts[$id][0] = $remaining;
            }
        }

        return $released;
    }
}

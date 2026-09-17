<?php

namespace Tests\Feature;

use App\Models\GameEvent;
use App\Models\Order;
use App\Models\PromoCode;
use App\Support\GameVoucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Voucher codes from the QR game are accepted by the site calculators:
 * POST /api/validate-promo-code recognises them and POST /submit-order redeems them once.
 */
class GameVoucherRedemptionTest extends TestCase
{
    use RefreshDatabase;

    private function issueVoucher(string $code = 'VN-PROM-K7M3', string $target = 'prom'): GameEvent
    {
        $this->postJson('/api/igra/event', ['target' => $target, 'loc' => 'mg', 'event' => 'voucher', 'code' => $code])->assertNoContent();

        return GameEvent::query()->where('event', 'voucher')->where('code', $code)->firstOrFail();
    }

    /** @return array<string,mixed> */
    private function orderPayload(string $promoCode, float $finalPrice = 850): array
    {
        return [
            'name' => 'Тест Клиент',
            'phone' => '+359888123456',
            'email' => 'test@example.com',
            'final_price' => $finalPrice,   // the calculator posts the ALREADY discounted total
            'details' => 'Пакет Лукс',
            'orderType' => 'Prom',
            'promo_code' => $promoCode,
            'gdpr_consent' => '1',
        ];
    }

    public function test_validate_endpoint_recognises_game_vouchers(): void
    {
        // Unknown game-format code: a game-specific message, not the generic one.
        $this->postJson('/api/validate-promo-code', ['code' => 'VN-PROM-ZZZZ'])
            ->assertOk()
            ->assertJson(['valid' => false, 'message' => 'Кодът от играта е изтекъл, вече е използван или не е разпознат.']);

        // Ordinary unknown codes keep the old message.
        $this->postJson('/api/validate-promo-code', ['code' => 'NOPE'])
            ->assertOk()
            ->assertJson(['valid' => false, 'message' => 'Невалиден промо код.']);

        $voucher = $this->issueVoucher();

        // Valid at the base level (lower-case input is normalised like ordinary promo codes).
        $this->postJson('/api/validate-promo-code', ['code' => 'vn-prom-k7m3'])
            ->assertOk()
            ->assertJson(['valid' => true, 'discount_type' => 'percent', 'discount_value' => 5, 'message' => 'Кодът от играта е приложен! Намаление: 5%']);

        // After sharing the Story the same code is worth the shared level.
        $this->postJson('/api/igra/event', ['target' => 'prom', 'event' => 'share', 'code' => 'VN-PROM-K7M3', 'meta' => ['method' => 'download']])->assertNoContent();
        $this->postJson('/api/validate-promo-code', ['code' => 'VN-PROM-K7M3'])
            ->assertOk()
            ->assertJson(['valid' => true, 'discount_value' => 15]);

        // Redeemed by the studio (Filament) -> no longer valid.
        GameVoucher::redeem($voucher->fresh());
        $this->postJson('/api/validate-promo-code', ['code' => 'VN-PROM-K7M3'])->assertJson(['valid' => false]);

        // Older than 72h -> no longer valid.
        $old = $this->issueVoucher('VN-WED-A2B3', 'wedding');
        $old->forceFill(['created_at' => now()->subHours(73)])->save();
        $this->postJson('/api/validate-promo-code', ['code' => 'VN-WED-A2B3'])->assertJson(['valid' => false]);
        $this->assertNull(GameVoucher::find('VN-WED-A2B3'));
    }

    public function test_order_with_a_game_voucher_records_the_discount_and_redeems_the_code_once(): void
    {
        $voucher = $this->issueVoucher();
        $this->postJson('/api/igra/event', ['target' => 'prom', 'event' => 'share', 'code' => 'VN-PROM-K7M3', 'meta' => ['method' => 'share']])->assertNoContent();

        // 1000 EUR package, 15% applied by the calculator -> 850 posted.
        $this->post('/submit-order', $this->orderPayload('VN-PROM-K7M3', 850))->assertSessionHas('success');

        $order = Order::query()->firstOrFail();
        $this->assertSame(850.0, (float) $order->price, 'the server must not subtract the discount a second time');
        $this->assertSame(150.0, (float) $order->discount_amount);
        $this->assertSame('VN-PROM-K7M3', $order->promo_code);
        $this->assertNull($order->promo_code_id);
        $this->assertNotNull($voucher->fresh()->redeemed_at, 'the voucher is redeemed by the order');

        // Second use of the same code: order goes through, but without a discount.
        $this->post('/submit-order', $this->orderPayload('VN-PROM-K7M3', 1000))->assertSessionHas('success');
        $second = Order::query()->latest('id')->firstOrFail();
        $this->assertNull($second->promo_code);
        $this->assertNull($second->discount_amount);
        $this->assertSame(1000.0, (float) $second->price);
    }

    public function test_ordinary_promo_codes_are_no_longer_discounted_twice(): void
    {
        $promo = PromoCode::query()->create(['code' => 'LETO10', 'discount_type' => 'percent', 'discount_value' => 10, 'is_active' => true]);

        // 1000 EUR package, 10% applied by the calculator -> 900 posted.
        $this->post('/submit-order', $this->orderPayload('LETO10', 900))->assertSessionHas('success');

        $order = Order::query()->firstOrFail();
        $this->assertSame(900.0, (float) $order->price);
        $this->assertSame(100.0, (float) $order->discount_amount);
        $this->assertSame($promo->id, $order->promo_code_id);
        $this->assertSame(1, $promo->fresh()->uses_count);

        $this->assertSame(50.0, GameVoucher::discountFromDiscountedPrice(950, 'fixed_eur', 50));
        $this->assertSame(0.0, GameVoucher::discountFromDiscountedPrice(900, 'percent', 0));
    }
}

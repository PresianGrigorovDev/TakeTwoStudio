<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class HomeInquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_inquiry_keeps_service_period_and_shows_confirmation(): void
    {
        Mail::fake();

        $this->post('/submit-contact', [
            'form' => 'home-inquiry',
            'name' => 'Мария',
            'phone' => '0888123456',
            'orderType' => 'Абитуриентски бал за целия клас',
            'event_period' => 'май 2027',
            'message' => 'Заснемане на изпращането и бала.',
            'gdpr_consent' => '1',
        ])->assertRedirect(url('/').'#contact');

        $this->assertDatabaseHas('inquiries', [
            'service_type' => 'Абитуриентски бал за целия клас',
            'message' => "Дата или период: май 2027\n\nЗаснемане на изпращането и бала.",
        ]);

        $this->get('/')->assertOk()->assertSee('Запитването ви е изпратено.');
    }

    public function test_home_inquiry_keeps_input_and_shows_validation_errors_at_the_form(): void
    {
        $this->post('/submit-contact', [
            'form' => 'home-inquiry',
            'name' => 'Мария',
            'event_period' => 'май 2027',
        ])->assertRedirect(url('/').'#contact')
            ->assertSessionHasErrorsIn('homeInquiry', ['orderType', 'phone', 'gdpr_consent']);

        $this->get('/')->assertOk()
            ->assertSee('Проверете отбелязаните полета')
            ->assertSee('value="Мария"', false)
            ->assertSee('value="май 2027"', false)
            ->assertSee('Изберете вид услуга.')
            ->assertSee('aria-describedby="gdpr_consent_home_error"', false);
    }
}

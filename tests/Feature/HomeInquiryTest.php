<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class HomeInquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_inquiry_keeps_the_message_and_shows_confirmation(): void
    {
        Mail::fake();

        $this->post('/submit-contact', [
            'form' => 'home-inquiry',
            'name' => 'Мария',
            'phone' => '0888123456',
            'orderType' => 'Запитване от началната страница',
            'email' => 'maria@example.test',
            'message' => 'Заснемане на изпращането и бала през май.',
            'gdpr_consent' => '1',
        ])->assertRedirect(url('/').'#contact');

        $this->assertDatabaseHas('inquiries', [
            'service_type' => 'Запитване от началната страница',
            'message' => 'Заснемане на изпращането и бала през май.',
        ]);

        $this->get('/')->assertOk()->assertSee('Запитването ви е изпратено.');
    }

    public function test_home_inquiry_keeps_input_and_shows_validation_errors_at_the_form(): void
    {
        $this->post('/submit-contact', [
            'form' => 'home-inquiry',
            'name' => 'Мария',
            'message' => 'Заснемане през май',
        ])->assertRedirect(url('/').'#contact')
            ->assertSessionHasErrorsIn('homeInquiry', ['orderType', 'phone', 'gdpr_consent']);

        $this->get('/')->assertOk()
            ->assertSee('Проверете отбелязаните полета')
            ->assertSee('value="Мария"', false)
            ->assertSee('Заснемане през май')
            ->assertSee('Изберете вид услуга.')
            ->assertSee('aria-describedby="gdpr_consent_home_error"', false);
    }
}

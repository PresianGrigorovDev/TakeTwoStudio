<?php

namespace App\Console\Commands;

use App\Mail\NewInquiryNotification;
use App\Models\Inquiry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Replaces the former public GET /test-email-send route (which anyone could hit
 * to create inquiries and trigger admin e-mails). Run it from the server shell:
 *   php artisan mail:test-inquiry
 */
class SendTestInquiryMail extends Command
{
    protected $signature = 'mail:test-inquiry {--keep : Keep the test inquiry row instead of deleting it afterwards}';

    protected $description = 'Send the admin "new inquiry" notification with a test inquiry to verify mail delivery';

    public function handle(): int
    {
        $to = config('mail.admin_email');

        if (empty($to)) {
            $this->error('config("mail.admin_email") is empty - set MAIL_ADMIN_EMAIL in .env.');

            return self::FAILURE;
        }

        $inquiry = Inquiry::create([
            'customer_name' => 'Тестов Клиент (Автоматичен Тест)',
            'customer_phone' => '0888 123 456',
            'customer_email' => 'test@example.com',
            'service_type' => 'Тест на имейл функцията',
            'message' => 'Автоматично тестово запитване за проверка на изпращането на имейли (artisan mail:test-inquiry).',
            'status' => 'new',
        ]);

        try {
            Mail::to($to)->send(new NewInquiryNotification($inquiry));
            $this->info("Test inquiry #{$inquiry->id} e-mailed to {$to}.");
        } catch (\Throwable $e) {
            $this->error('Mail failed: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            if (! $this->option('keep')) {
                $inquiry->delete();
                $this->line('Test inquiry row deleted (use --keep to retain it).');
            }
        }

        return self::SUCCESS;
    }
}

<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

$adminEmail = Config::get('mail.admin_email');
echo "Admin Email Config: " . $adminEmail . "\n";
echo "Mail Username: " . Config::get('mail.mailers.smtp.username') . "\n";
echo "Mail Host: " . Config::get('mail.mailers.smtp.host') . "\n";
echo "Mail Port: " . Config::get('mail.mailers.smtp.port') . "\n";

try {
    Mail::raw('Това е тестово съобщение от Take Two Studio за проверка на имейл функцията.', function ($message) use ($adminEmail) {
        $message->to($adminEmail)
                ->subject('Тест на имейл функцията - Take Two Studio');
    });
    echo "SUCCESS: Email sent successfully to " . $adminEmail . "\n";
} catch (\Throwable $e) {
    echo "ERROR sending email: " . $e->getMessage() . "\n";
}

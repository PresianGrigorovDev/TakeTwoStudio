<!DOCTYPE html>
<html>
<head>
    <title>Относно вашата заявка за резервация - Take Two Studio 1603</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h2 style="color: #D4AF37;">Относно вашата заявка за резервация</h2>

        <p>Здравейте, <strong>{{ $booking->name }}</strong>,</p>

        <p>За съжаление, не можем да потвърдим вашата резервация за <strong>{{ $booking->event_date->format('d.m.Y') }}</strong> ({{ $booking->service_type }}).</p>

        <p>Моля, опитайте с друга дата или се свържете директно с нас, за да намерим подходящо решение.</p>

        <p style="margin-top: 20px;">
            Телефон: <strong>{{ \App\Support\Settings::phoneDisplay(\App\Support\Settings::phone()) }}</strong><br>
            Имейл: <strong>{{ \App\Support\Settings::email() }}</strong>
        </p>

        <p>Благодарим ви за интереса!</p>

        <p style="color: #888; font-size: 12px; margin-top: 30px;">Take Two Studio 1603 | Варна, България</p>
    </div>
</body>
</html>

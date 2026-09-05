<!DOCTYPE html>
<html>
<head>
    <title>Резервацията ви е потвърдена - Take Two Studio 1603</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h2 style="color: #D4AF37;">Резервацията ви е потвърдена!</h2>

        <p>Здравейте, <strong>{{ $booking->name }}</strong>,</p>

        <p>С удоволствие ви уведомяваме, че вашата резервация е потвърдена.</p>

        <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;"><strong>Дата:</strong></td>
                <td style="padding: 10px; border: 1px solid #eee;">{{ $booking->event_date->format('d.m.Y') }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;"><strong>Часове:</strong></td>
                <td style="padding: 10px; border: 1px solid #eee;">{{ $booking->start_time }} – {{ $booking->end_time }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;"><strong>Услуга:</strong></td>
                <td style="padding: 10px; border: 1px solid #eee;">{{ $booking->service_type }}</td>
            </tr>
        </table>

        <p style="margin-top: 20px;">Ако имате въпроси, не се колебайте да ни се обадите на <strong>{{ \App\Support\Settings::phoneDisplay(\App\Support\Settings::phone()) }}</strong> или да ни пишете на <strong>{{ \App\Support\Settings::email() }}</strong>.</p>

        <p>Очакваме ви с нетърпение!</p>

        <p style="color: #888; font-size: 12px; margin-top: 30px;">Take Two Studio 1603 | Варна, България</p>
    </div>
</body>
</html>

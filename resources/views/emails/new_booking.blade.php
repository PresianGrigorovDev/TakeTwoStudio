<!DOCTYPE html>
<html>
<head>
    <title>Нова Заявка за Резервация - Take Two Studio</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h2 style="color: #D4AF37;">Нова заявка за резервация!</h2>

        <p>Имате нова заявка за резервация от системата на <strong>Take Two Studio</strong>.</p>

        <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;"><strong>Име:</strong></td>
                <td style="padding: 10px; border: 1px solid #eee;">{{ $booking->name }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;"><strong>Телефон:</strong></td>
                <td style="padding: 10px; border: 1px solid #eee;">{{ $booking->phone }}</td>
            </tr>
            @if($booking->email)
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;"><strong>Имейл:</strong></td>
                <td style="padding: 10px; border: 1px solid #eee;">{{ $booking->email }}</td>
            </tr>
            @endif
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;"><strong>Желана дата:</strong></td>
                <td style="padding: 10px; border: 1px solid #eee;">{{ $booking->event_date->format('d.m.Y') }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;"><strong>Часове:</strong></td>
                <td style="padding: 10px; border: 1px solid #eee;">{{ $booking->start_time }} – {{ $booking->end_time }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;"><strong>Тип услуга:</strong></td>
                <td style="padding: 10px; border: 1px solid #eee;">{{ $booking->service_type }}</td>
            </tr>
            @if($booking->message)
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;"><strong>Съобщение:</strong></td>
                <td style="padding: 10px; border: 1px solid #eee;">{!! nl2br(e($booking->message)) !!}</td>
            </tr>
            @endif
        </table>

        <p style="margin-top: 20px;">Можете да потвърдите или откажете резервацията от <a href="{{ url('/admin/bookings') }}">административния панел</a>.</p>
    </div>
</body>
</html>

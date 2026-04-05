<!DOCTYPE html>
<html>
<head>
    <title>Ново Контактно Запитване - Take Two Studio</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h2 style="color: #4CAF50;">Получихте ново контактно запитване!</h2>
        
        <p>Имате ново запитване от контактната форма на <strong>Take Two Studio</strong>.</p>
        
        <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;"><strong>Тип Услуга:</strong></td>
                <td style="padding: 10px; border: 1px solid #eee;">{{ $inquiry->service_type }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;"><strong>Име:</strong></td>
                <td style="padding: 10px; border: 1px solid #eee;">{{ $inquiry->customer_name }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;"><strong>Телефон:</strong></td>
                <td style="padding: 10px; border: 1px solid #eee;">{{ $inquiry->customer_phone }}</td>
            </tr>
            @if($inquiry->customer_email)
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;"><strong>Имейл:</strong></td>
                <td style="padding: 10px; border: 1px solid #eee;">{{ $inquiry->customer_email }}</td>
            </tr>
            @endif
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;"><strong>Съобщение:</strong></td>
                <td style="padding: 10px; border: 1px solid #eee;">{{ nl2br(e($inquiry->message)) }}</td>
            </tr>
        </table>

        <p style="margin-top: 20px;">Можете да прегледате запитването директно в <a href="{{ url('/admin/inquiries') }}">административния панел (Запитвания)</a>.</p>
    </div>
</body>
</html>

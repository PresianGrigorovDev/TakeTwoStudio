<!DOCTYPE html>
<html>
<head>
    <title>Ново Запитване - Take Two Studio</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h2 style="color: #4CAF50;">Получихте ново запитване!</h2>
        
        <p>Имате ново запитване от системата на <strong>Take Two Studio</strong>.</p>
        
        <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;"><strong>Тип Услуга:</strong></td>
                <td style="padding: 10px; border: 1px solid #eee;">{{ $order->service_type }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;"><strong>Име:</strong></td>
                <td style="padding: 10px; border: 1px solid #eee;">{{ $order->name }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;"><strong>Телефон:</strong></td>
                <td style="padding: 10px; border: 1px solid #eee;">{{ $order->phone }}</td>
            </tr>
            @if($order->email)
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;"><strong>Имейл:</strong></td>
                <td style="padding: 10px; border: 1px solid #eee;">{{ $order->email }}</td>
            </tr>
            @endif
            @if($order->price)
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;"><strong>Калкулирана Цена:</strong></td>
                <td style="padding: 10px; border: 1px solid #eee;">{{ $order->price }} лв.</td>
            </tr>
            @endif
            <tr>
                <td style="padding: 10px; border: 1px solid #eee;"><strong>Допълнителни Детайли/Съобщение:</strong></td>
                <td style="padding: 10px; border: 1px solid #eee;">{{ nl2br(e($order->details)) }}</td>
            </tr>
        </table>

        <p style="margin-top: 20px;">Можете да прегледате запитването и да промените статуса му директно в <a href="{{ url('/admin/orders') }}">административния панел</a>.</p>
    </div>
</body>
</html>

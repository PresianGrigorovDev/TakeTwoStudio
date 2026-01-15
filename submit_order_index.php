<?php
// submit_order_index.php

// 1. ВАШИЯТ ИМЕЙЛ (Сменете го с вашия!)
$to = "taketwostudiobg@gmail.com"; 

// 2. Проверка дали формата е изпратена
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Взимане и изчистване на данните
    $name = strip_tags(trim($_POST["name"]));
    $phone = strip_tags(trim($_POST["phone"]));
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $message = trim($_POST["message"]);

    // Заглавие на имейла
    $subject = "Ново запитване от сайта (Take Two Studio)";

    // Съдържание на имейла
    $email_content = "Получихте ново съобщение от контактната форма:\n\n";
    $email_content .= "Име: $name\n";
    $email_content .= "Телефон: $phone\n";
    $email_content .= "Email: $email\n\n";
    $email_content .= "Съобщение:\n$message\n";

    // Хедъри (За да не влиза в спам и да се чете кирилицата)
    $headers = "From: order@taketwostudio1603.com\r\n"; // Използвайте домейн имейл
    $headers .= "Reply-To: $email\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Изпращане
    if (mail($to, $subject, $email_content, $headers)) {
        // Успех - пренасочване обратно към сайта с съобщение
        echo "<script>alert('Благодарим ви! Съобщението е изпратено.'); window.location.href='index.html';</script>";
    } else {
        // Грешка
        echo "<script>alert('Възникна грешка при изпращането. Моля, опитайте по-късно.'); window.location.href='index.html';</script>";
    }
} else {
    // Ако някой отвори файла директно без да попълва форма
    header("Location: index.html");
    exit;
}
?>
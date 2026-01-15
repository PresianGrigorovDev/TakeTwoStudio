<?php
// submit_order.php

// 1. ВАШИЯТ ИМЕЙЛ (Сменете го с вашия!)
$to = "taketwostudiobg@gmail.com"; 

// 2. Проверка дали формата е изпратена чрез POST метод
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Взимане и изчистване на данните от формата
    // strip_tags() премахва HTML тагове за сигурност
    // trim() премахва излишни празни пространства
    
    // Тип на поръчката (Commercial, Wedding, Prom и т.н.)
    $orderType = isset($_POST['orderType']) ? strip_tags(trim($_POST['orderType'])) : 'Общо запитване';
    
    $name = isset($_POST['name']) ? strip_tags(trim($_POST['name'])) : '';
    $phone = isset($_POST['phone']) ? strip_tags(trim($_POST['phone'])) : '';
    // Валидиране и санитаризиране на имейла
    $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    // Валидация - поне име и телефон са задължителни
    if (empty($name) || empty($phone)) {
        echo "<script>alert('Моля, попълнете име и телефон.'); window.history.back();</script>";
        exit;
    }

    // Заглавие на имейла
    // Превеждаме типа на поръчката за по-ясна тема
    $subjectType = $orderType;
    if ($orderType == 'Commercial') $subjectType = 'Реклама / Бизнес';
    
    $subject = "Ново запитване от уебсайта - Реклама / Бизнес - Take Two Studio";

    // Съдържание на имейла
    $email_content = "Получихте ново съобщение за Реклама / Бизнес:\n\n";
    $email_content .= "--- Детайли за клиента ---\n";
    $email_content .= "Име / Фирма: $name\n";
    $email_content .= "Телефон: $phone\n";
    $email_content .= "Email: $email\n\n";
    $email_content .= "--- Описание на проекта ---\n";
    $email_content .= "$message\n\n";
    
    $email_content .= "-------------------------\n";
    $email_content .= "Изпратено от форма тип: $orderType\n";

    // Хедъри (За да не влиза в спам и да се чете кирилицата)
    // Важно: 'From' трябва да е домейн имейл (напр. no-reply@taketwostudio1603.com), за да минава спам филтрите
    $headers = "From: order@taketwostudio1603.com\r\n"; 
    $headers .= "Reply-To: $email\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Изпращане на имейла
    if (mail($to, $subject, $email_content, $headers)) {
        // Успех - пренасочване обратно към сайта със съобщение
        // Може да пренасочите към "thank-you.html" или просто да върнете назад
        echo "<script>
                alert('Благодарим ви! Вашето запитване беше изпратено успешно.\nЩе се свържем с вас скоро.');
                window.location.href = 'index.html'; 
              </script>";
    } else {
        // Грешка при изпращане
        echo "<script>
                alert('Възникна грешка при изпращането. Моля, опитайте отново по-късно или се свържете с нас по телефона.');
                window.history.back();
              </script>";
    }
} else {
    // Ако някой отвори файла директно без да попълва форма
    header("Location: index.html");
    exit;
}
?>
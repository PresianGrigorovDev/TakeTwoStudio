<?php
// submit_order.php

// 1. ВАШИЯТ ИМЕЙЛ (Сменете го с вашия!)
$to = "taketwostudiobg@gmail.com"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Взимане на общите данни (има ги във всички калкулатори)
    $name = strip_tags(trim($_POST["name"]));
    $phone = strip_tags(trim($_POST["phone"]));
    $final_price = strip_tags(trim($_POST["final_price"]));
    $details = strip_tags(trim($_POST["details"])); // Това идва от JavaScript-а

    // Променливи за специфичните полета (ако ги има)
    $school = isset($_POST["school"]) ? strip_tags(trim($_POST["school"])) : "Не е посочено";
    $date = isset($_POST["date"]) ? strip_tags(trim($_POST["date"])) : "Не е посочена";
    
    // Определяне на тип услуга според полетата
    $type_of_service = "Поръчка от калкулатор";
    if (isset($_POST["school"])) {
        $type_of_service = "Абитуриентски Бал";
    } elseif (isset($_POST["date"])) {
        $type_of_service = "Свето Кръщение";
    } else {
        $type_of_service = "Сватба";
    }

    // Заглавие на имейла
    $subject = "Нова поръчка: $type_of_service - $name";

    // Съдържание на имейла
    $email_content = "Получихте ново запитване за $type_of_service:\n\n";
    $email_content .= "--------------------------------------\n";
    $email_content .= "Име на клиента: $name\n";
    $email_content .= "Телефон: $phone\n";
    
    // Добавяме специфичните полета само ако са попълнени
    if (isset($_POST["school"])) {
        $email_content .= "Училище/Клас: $school\n";
    }
    if (isset($_POST["date"])) {
        $email_content .= "Дата на събитието: $date\n";
    }

    $email_content .= "--------------------------------------\n";
    $email_content .= "ИЗБРАНИ УСЛУГИ:\n";
    $email_content .= "$details\n\n";
    $email_content .= "ОБЩА ЦЕНА: $final_price €\n";
    $email_content .= "--------------------------------------\n";

    // Хедъри
    $headers = "From: order@taketwostudio1603.com\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Изпращане
    if (mail($to, $subject, $email_content, $headers)) {
        // При успех връщаме потребителя назад
        // Можете да ги пратите към специална страница "thank-you.html" ако направите такава
        echo "<script>alert('Вашето запитване е прието успешно! Ще се свържем с вас скоро.'); window.history.back();</script>";
    } else {
        echo "<script>alert('Грешка при изпращане. Моля свържете се с нас по телефона.'); window.history.back();</script>";
    }

} else {
    // Ако файлът се отвори директно
    header("Location: index.html");
    exit;
}
?>
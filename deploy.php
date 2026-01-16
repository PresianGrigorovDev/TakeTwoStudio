<?php
// Позволява ни да виждаме грешките директно в браузъра
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "--- Старт на Deployment ---<br>";

// Име на вашето хранилище (repository)
$repo_name = "TakeTwoStudio";

// Опит за изпълнение на командата
$output = shell_exec("/usr/local/cpanel/bin/jethost-git-sync --repo=$repo_name 2>&1");

if ($output === null) {
    echo "ГРЕШКА: Функцията shell_exec е забранена или командата не работи.<br>";
} else {
    echo "Резултат от сървъра: <pre>$output</pre>";
}

echo "--- Край ---";
?>
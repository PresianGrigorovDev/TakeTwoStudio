<?php
// Изчистване на кеша и показване на грешки за тест
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Initalizing deployment...<br>";

// Изпълнява официалната команда на cPanel за Deploy
// Заменете 'TakeTwoStudio' с точното име на репозиторито от cPanel
$command = 'uapi VersionControl deployment create repository=TakeTwoStudio';
$output = shell_exec($command . ' 2>&1');

echo "Result: <pre>$output</pre>";
?>
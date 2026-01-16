<?php
// Изпълнява команда за обновяване на git хранилището
shell_exec('/usr/local/cpanel/bin/jethost-git-sync --repo=TakeTwoStudio'); 
echo "Deployment triggered!";
?>
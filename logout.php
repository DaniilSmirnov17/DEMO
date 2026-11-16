<?php
// начало сессии 
session_start();
// уничтожение сессии 
session_destroy();
// редирект на страницу входа 
header('Location: login.php');
exit;
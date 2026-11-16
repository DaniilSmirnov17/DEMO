<?php
session_start();

$type     = 'mysql';
$server   = 'localhost';             
$db       = 'ExamTestSam';             
$username = 'root';   
$password = '';   
$options = [                         
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];                                               
$dsn = "$type:host=$server;dbname=$db;"; 
try {                   
    $conn = new PDO($dsn, $username, $password, $options);   
} catch (PDOException $e) {                             
    throw new PDOException($e->getMessage(), $e->getCode()); 
}
?>
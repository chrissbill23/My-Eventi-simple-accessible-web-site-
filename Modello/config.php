<?php

$db_host = "localhost";
$db_user = "root";
$db_password = "sciences";
$db_name = "eventi";
$DB = new mysqli($db_host, $db_user, $db_password, $db_name);
if($DB->connect_error) die("Connessione al database non riuscita: " . $DB->connect_error);
else 
mysqli_set_charset($DB,"utf8");;
?>

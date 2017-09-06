<?php

require_once("./Modello/controller.php");
session_start();
/*INTERFACCIA OFFERTA A TUTTE LE PAGINE DEDICATE ALLA VISUALIZZAZIONE DEL PROFILO DI UN UTENTE, PER COMUNICARE CON IL CONTROLLER*/
if(!isset($_SESSION['controller'])){
     $_SESSION['controller'] = new Controller;
} 
    if(isset($_GET['utente']))
        $_SESSION['controller']->profiloUtente($_GET['utente']);
    elseif(isset($_GET['social']))
        $_SESSION['controller']->social($_GET['social'],isset($_GET['followers']), isset($_GET['searchuser']) ? $_GET['searchuser']:'');

?>

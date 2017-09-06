<?php

require_once("./Modello/controller.php");
session_start();
/*INTERFACCIA OFFERTA A TUTTE LE PAGINE DEDICATE ALLA REGISTRAZIONE UTENTE, PER COMUNICARE CON IL CONTROLLER*/
if(!isset($_SESSION['controller'])){
     $_SESSION['controller'] = new Controller;
} 
        if(!isset($_SESSION['user'])){ 
        if($_SESSION['controller']->aggiungiUtente($_POST['cognome'], $_POST['nome'], 
                                   $_POST['mail'], $_POST['nomeutente'], $_POST['password1'],$_POST['password2'])){
            $_SESSION['controller']->spazioUtente($_POST['nomeutente'],$_POST['password1']);
            }
        }
        else header("Location: spaziopersonale.php");

?>

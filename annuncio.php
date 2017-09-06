<?php

require_once("./Modello/controller.php");
    session_start();
/*INTERFACCIA OFFERTA A TUTTE LE PAGINE DEDICATE ALLA VISUALIZZAZIONE DI UN ANNUNCIO, PER COMUNICARE CON IL CONTROLLER*/  
if(!isset($_SESSION['controller'])){
     $_SESSION['controller'] = new Controller;
}  
if(isset($_GET['view'])){
    $_SESSION['controller']->showEventDetails($_GET['view']);
}
elseif(isset($_GET['partecipa']))
     $_SESSION['controller']->partecipa($_GET['partecipa']);
     elseif(isset($_GET['ritira']))
        $_SESSION['controller']->ritiraPartecipazione($_GET['ritira']);
        elseif(isset($_GET['showfoto'])){
            $_SESSION['controller']->showFullFoto($_GET['showfoto'],(isset($_GET['index']) ? $_GET['index'] : 0), isset($_GET['editmode']) );
        }
        elseif(isset($_GET['segnalaannuncio'])){
            $motivo = isset($_GET['motivo']) ? $_GET['motivo'] : '' ;        
            if($motivo == 'Altro motivo')
            $motivo = $_GET['altromotivo'];
            $_SESSION['controller']->segnalaAnnuncio($_GET['segnalaannuncio'],$motivo);
        }
        elseif(isset($_GET['partecipanti']))
            $_SESSION['controller']->listaPartecipanti($_GET['partecipanti']);
    


?>

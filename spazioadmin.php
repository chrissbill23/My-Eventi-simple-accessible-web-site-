<?php

require_once("./Modello/controller.php");

/*INTERFACCIA OFFERTA A TUTTE LE PAGINE DEDICATE ALL' AMMINISTRATORE, PER COMUNICARE CON IL CONTROLLER*/
session_start();
if(!isset($_SESSION['controller'])){
     $_SESSION['controller'] = new Controller;
}
if(isset($_GET['listevblocked']) ||isset($_GET['blockedevents'])){
    $modified = isset($_GET['events']) ? $_GET['events'] : -1;
    $keyword = isset($_GET['searchev']) ? $_GET['searchev'] : '';
    $_SESSION['controller']->eventiBloccati($modified, $keyword,isset($_GET['goToPage']) ? $_GET['goToPage'] :1 );
}
else {
    if(isset($_GET['listevsegnalati'])){
        $_SESSION['controller']->eventiSegnalati();
    }
    elseif(isset($_GET['makepremium'])){
        $_SESSION['controller']->makepremium($_GET['makepremium'],isset($_GET['conf']));
    }
    elseif(isset($_GET['evsegnalato'])){
        $_SESSION['controller']->eventoSegnalato($_GET['evsegnalato']);
    }elseif(isset($_GET['rmsegnalato'])){
            $_SESSION['controller']->removeSegnalato($_GET['rmsegnalato']);
    }elseif(isset($_GET['putprimopiano'])){
        $_SESSION['controller']->mettiinprimopiano($_GET['putprimopiano'], isset($_GET['conf']));
    }
    elseif(isset($_GET['bloccauser'])){
        $motivo = isset($_GET['motivo']) ? $_GET['motivo']: '';
        $_SESSION['controller']->blockUser($_GET['bloccauser'],isset($_GET['block']), $motivo,isset($_GET['blockevent']),isset($_GET['visited']));
    }elseif(isset($_GET['quarantine']) || isset($_GET['segnalaannuncio'])){
        $val = isset($_GET['block']);
        $motivo = isset($_GET['motivo']) ? $_GET['motivo'] : '' ;        
        if($motivo == 'Altro motivo')
            $motivo = $_GET['altromotivo'];
        $_SESSION['controller']->mettirimuoviInQuanrantena(isset($_GET['quarantine'])? $_GET['quarantine'] : $_GET['segnalaannuncio'],$val, $motivo);
    }
    else{
        $_SESSION['controller']->spazioUtente();
    }
}
?>

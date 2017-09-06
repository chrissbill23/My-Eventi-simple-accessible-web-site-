<?php

require_once("./Modello/controller.php");

/*INTERFACCIA OFFERTA A TUTTE LE PAGINE DEDICATE ALL'UTENTE ISCRITTO, PER COMUNICARE CON IL CONTROLLER*/
session_start();
function fotoDesc(){
    $x = 1;
         $fotoDesc = array();
         while(isset($_POST["DescFoto$x"])){
            if($_FILES["Foto$x"]['name'] != '')
                $fotoDesc[] = $_POST["DescFoto$x"];
            ++$x;
         }
    return $fotoDesc;
}
function etich(){
    $etich = array();
         $x = 1;
         while(isset($_POST["etich$x"])){
            $etich[] = $_POST["etich$x"];
            ++$x;
         }
    return $etich;
}
function fotoname(){
    $fotoName = array();
         $x = 1;
         while(isset($_POST["DescFoto$x"])){
            if($_FILES["Foto$x"]['name'] != '')
                    $fotoName[] = "Foto$x";
            ++$x;
         }
    return $fotoName;
}
if(!isset($_SESSION['controller'])){
     $_SESSION['controller'] = new Controller;
}
 if(!isset($_GET['action'])){
     if(isset($_GET['news'])){
        $_SESSION['controller']->ultimeNotizie($_GET['news']);
     }
     elseif(isset($_GET['myeventi'])){
        $page = isset($_GET['goToPage'])?(int)$_GET['goToPage']:1;
        $_SESSION['controller']->myEventi(isset($_GET['pass']),$page,$_GET['myeventi']);
     }
     elseif(isset($_GET['myannunci'])){
        $page = isset($_GET['goToPage'])?(int)$_GET['goToPage']:1;
        $keyword = isset($_GET['keyword'])?$_GET['keyword']:'';
        $_SESSION['controller']->myAnnunci($page,$_GET['myannunci'], $keyword);
     }
     elseif(isset($_GET['deleteuser'])){
        $_SESSION['controller']->eliminaUtente($_GET['deleteuser'], isset($_GET['conf'])); 
     }
     elseif(isset($_GET['mysocial'])){
        $_SESSION['controller']->mySocial(isset($_GET['followers']), isset($_GET['searchuser']) ? $_GET['searchuser']:'');
     }
     elseif(isset($_GET['segui'])){
        $_SESSION['controller']->seguiUser($_GET['segui'], isset($_GET['conf'])); 
     }
     elseif(isset($_GET['elim'])){
        switch ($_GET['elim']){
        case 'foto': $_SESSION['controller']->EliminaFoto($_GET['ev'],$_GET['index']); break;
        case 'evento': $_SESSION['controller']->eliminaEvento($_GET['ev'], isset($_GET['conf'])); break;
        }
     }
     elseif(isset($_GET['searchuser'])){
        $userType = isset($_GET['typeusers']) ? $_GET['typeusers'] : -1;
        $keyword = isset($_GET['searchuser']) ? $_GET['searchuser'] : '';
        $_SESSION['controller']->utentiIscritti(isset($_GET['desc']),$userType, $keyword, isset($_GET['goToPage']) ?$_GET['goToPage'] : 1 );
     }else{
        if(isset($_POST['insertNewEvent'])){
         $fotoDesc = fotoDesc();
         $etich = etich();
         $fotoName = fotoname();
         $_SESSION['controller']->aggiungiEvento($_POST['titolo'], $_POST['breveDesc'], 
                                                 $_POST['totDesc'], $_POST['tipiEventi'], $_POST['categ'],
                                                 $_POST['giorno'], $_POST['mese'], $_POST['anno'], $_POST['ora'], $_POST['minuti'], $_POST['denom'],
                                                 $_POST['via'], $_POST['com'], $_POST['prov'],$_FILES,$fotoName, $fotoDesc, $etich);
        }
        elseif(isset($_POST['editEvent'])){
            $etich = etich();
            $_SESSION['controller']->updateInfoEvento($_POST['editEvent'], $_POST['titolo'], $_POST['breveDesc'], 
                                                 $_POST['totDesc'], $_POST['tipiEventi'], $_POST['categ'],
                                                 $_POST['giorno'], $_POST['mese'], $_POST['anno'], $_POST['ora'], $_POST['minuti'],$_POST['denom'],
                                                 $_POST['via'], $_POST['com'], $_POST['prov'], $etich);
        }
        elseif(isset($_POST['accountsetting'])){
            $_SESSION['controller']->updateProfilo($_POST['cognome'], $_POST['nome'], 
                                   $_POST['mail'],$_POST['oldpassword'], $_POST['newpassword1'], $_POST['newpassword2']);
        }
        elseif(isset($_POST['addfoto'])){
        $fotoDesc = fotoDesc();
         $etich = etich();
         $fotoName = fotoname();
         $_SESSION['controller']->aggiungiNuoveFoto($_POST['addfoto'],$_FILES,$fotoName, $fotoDesc, $etich);
        }
        elseif(isset($_POST['nomeutente']) && isset($_POST['password']))
            $_SESSION['controller']->spazioUtente($_POST['nomeutente'],$_POST['password']);
        else $_SESSION['controller']->spazioUtente();
    }
 }
 else{
        switch ($_GET['action']){
        case 'logout': $_SESSION['controller']->logout(); break;
        case 'nuovoannunciopagina': $_SESSION['controller']->addNewEventPage(); break;
        case 'myprofile': $_SESSION['controller']->myprofile(); break;
        case'accountsetting': $_SESSION['controller']->accountSettingPage(); break;
        case 'edit': $_SESSION['controller']->modificaEvento($_GET['event']); break;
        case 'editfoto': $_SESSION['controller']->modificaFotoEvento($_GET['event']); break;
        }

    }

?>

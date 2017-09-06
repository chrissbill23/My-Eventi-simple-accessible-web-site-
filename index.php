<?php

require_once("./Modello/controller.php");
session_start();
/*INTERFACCIA DEDICATA ALLA PAGINA: PRINCIPALE, DI LOGIN, DI REGISTRAZIONE, DI FAQ, DI COMUNICARE CON IL CONTROLLER*/
if(!isset($_SESSION['controller'])){
     $_SESSION['controller'] = new Controller();
}
    if(!isset($_GET['action'])){
        if(!isset($_POST['recuppass']))
            $_SESSION['controller']->HomePageSito();
        else $_SESSION['controller']->recupPass($_POST['recuppass']);
    }
    else{
        switch ($_GET['action']){
        case 'login': $_SESSION['controller']->loginPage(); break;
        case 'signup': isset($_SESSION['user']) ? header("Location: spaziopersonale.php") : 
                                $_SESSION['controller']->signupPage(); break;
        case 'annuncio': $_SESSION['controller']->showEventDetails($_GET['event'], $_GET['publisher']); break;
        case 'faq': $_SESSION['controller']->faq(isset($_GET['id'])? $_GET['id'] :'' ); break;
        case 'bepremium': $_SESSION['controller']->bepremium(isset($_GET['conf']),isset($_GET['annulla'])); break;
        case 'getpass': $_SESSION['controller']->recupPass(); break;
        }

    }

?>

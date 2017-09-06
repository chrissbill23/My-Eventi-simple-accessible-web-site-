<?php
/*INTERFACCIA DEDICATA A TUTTE LE PAGINE DELL'ARCHIVIO DI ANNUNCIO DI COMUNICARE CON IL CONTROLLER*/
require_once("./Modello/controller.php");
session_start();
if(!isset($_SESSION['controller'])){
     $_SESSION['controller'] = new Controller();
}
if(isset($_GET['arch'])){
    switch($_GET['arch']){
        case 'types' : $_SESSION['controller']->tipiEventiPage(); break;
        case 'categ' : $_SESSION['controller']->categEventiPage(); break;
    }
}
elseif(isset($_GET['citta']))
    $_SESSION['controller']->eventsInCityPage();
    else{
    $page = isset($_GET['goToPage']) ? $_GET['goToPage'] : 1;
    $type = -1;
    if(isset($_GET['type']))
        $type = $_GET['type'];
    $categ = -1;
    if(isset($_GET['categ']))
        $categ = $_GET['categ'];
    $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
    $city = isset($_GET['city']) ? $_GET['city'] : '';
    $publisher = isset($_GET['publisher']) ? $_GET['publisher'] : '';
    $fromgiorno = isset($_GET['fromgiorno']) ? $_GET['fromgiorno'] : '';
    $frommese = isset($_GET['frommese']) ? $_GET['frommese'] : '';
    $fromanno = isset($_GET['fromanno']) ? $_GET['fromanno'] : '';
    $togiorno = isset($_GET['togiorno']) ? $_GET['togiorno'] : '';
    $tomese = isset($_GET['tomese']) ? $_GET['tomese'] : '';
    $toanno = isset($_GET['toanno']) ? $_GET['toanno'] : '';
    $fromgiornopub = isset($_GET['fromgiornopub']) ? $_GET['fromgiornopub'] : '';
    $frommesepub = isset($_GET['frommesepub']) ? $_GET['frommesepub'] : '';
    $fromannopub = isset($_GET['fromannopub']) ? $_GET['fromannopub'] : '';
    $togiornopub = isset($_GET['togiornopub']) ? $_GET['togiornopub'] : '';
    $tomesepub = isset($_GET['tomesepub']) ? $_GET['tomesepub'] : '';
    $toannopub = isset($_GET['toannopub']) ? $_GET['toannopub'] : '';
    $_SESSION['controller']->searchResult($type,$categ,$keyword,$city, $publisher, $page,
                                        $fromgiorno,$frommese,$fromanno,$togiorno,$tomese,$toanno,
                                        $fromgiornopub,$frommesepub,$fromannopub,$togiornopub,$tomesepub,$toannopub);
}

?>

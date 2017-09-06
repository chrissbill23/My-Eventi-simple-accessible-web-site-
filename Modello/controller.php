<?php

require_once("utente.php");
require_once("views.php");
/*LA CLASSE CONTROLLER E' UNA CLASSE I CUI OGGETTI RAPPRESENTANO DEGLI INTERMEDIARI TRA LE PAGINE CHE INTERAGISCONO CON L'UTENTE, E IL MODELLO. UN OGGETTO CONTROLLER CREA/AGGIORNA DINAMICAMENTE LE PAGINE IN BASE ALLE RICHIESTE DEGLI UTENTI
E AI RISULTATI DELLE OPERAZONI RITORNATE DAL MODELLO
*/
class controller {
    
    private function esisteView($name){//metodo che ritorna true se la pagina $name esiste altrimenti restituisce false e porta alla pagina 404
        if(file_exists($name))
            return true;
        else{
            $this->errore(0);
            return false;
        }
    }
    private function isUserConnected(){//metodo che ritorna true se l'utente è connesso, false altrimenti
        return isset($_SESSION['user']) && $_SESSION['user']->isConnected();
    }
    
    private function buildPremiumUserNav($page){ // metodo che ritorna una stringa html, costruita dalla pagina $page ( pagina html dello spazio utente in base all'utente connesso convertita in stringa), 
    //che rappresenta $page con in più la navigazione per gli utenti premium se l'utente connesso è un utente premium.
        if($_SESSION['user']->isPremium()){
            $nav = '<li id="navutentePremium"><a href="spaziopersonale.php?action=nuovoannunciopagina">Pubblica un nuovo annuncio di evento</a></li>'."\n".
                  '<li><a href="spaziopersonale.php?myannunci=0">Gestione dei tuoi annunci pubblicati</a></li>'."\n";
             $page = str_replace('<li id="navutentePremium"></li>',$nav,$page);
    }
    else { $str = !$_SESSION['user']->haChiestoPremium() ? '<li id="navutentePremium"><a href="index.php?action=bepremium">Richiedi di essere un utente <span xml:lang="en" lang="en">premium !</span></a></li>':
                    '<li id="navutentePremium"><a href="index.php?action=bepremium&annulla">Annulla la tua domanda di passaggio ad un utente <span xml:lang="en" lang="en">premium</span></a></li>';
        $page = str_replace('<li id="navutentePremium"></li>',$str,$page);
    }
    return $page;
    }
    private function buildNavWithNumbers($menu){//Metodo di supporto al metodo buildUtenteNav, il suo compito è quello di aggiungere alle voci presenti nel menù spazio utente $menu, il numero di record che troveranno cliccandoci
        if($_SESSION['user']->isAdmin()){
                    $menu = str_replace('<li><a href="spazioadmin.php?listevsegnalati">Annunci segnalati dagli utenti</a></li>',
                    '<li><a href="spazioadmin.php?listevsegnalati">Annunci segnalati dagli utenti ('.sizeof(evento::eventiSegnalati()).' annunci)</a></li>',$menu);
                    $menu = str_replace('<li><a href="spaziopersonale.php?searchuser">lista in ordine alfabetico crescente degli utenti iscritti</a></li>', 
                                '<li><a href="spaziopersonale.php?searchuser">lista in ordine alfabetico crescente degli utenti iscritti ('.sizeof($_SESSION['user']->utentiIscritti(false)).' utenti)</a></li>',$menu); 
                    $menu = str_replace('<li><a href="spaziopersonale.php?searchuser&amp;desc">lista in ordine alfabetico decrescente degli utenti iscritti</a></li>', 
                                '<li><a href="spaziopersonale.php?searchuser&amp;desc">lista in ordine alfabetico decrescente degli utenti iscritti ('.sizeof($_SESSION['user']->utentiIscritti(false)).' utenti)</a></li>',$menu);
                    $menu = str_replace('<li><a href="spazioadmin.php?listevblocked">Annunci messi in quarantena</a></li>', 
                                  '<li><a href="spazioadmin.php?listevblocked">Annunci messi in quarantena ('.sizeof(evento::blockedEvents()).' annunci)</a></li>',$menu);
        }
        else{ 
            $menu =  str_replace('<li><a href="spaziopersonale.php?news">Ultime notizie</a></li>',
                                '<li><a href="spaziopersonale.php?news">Ultime notizie ('.$_SESSION['user']->totNews().' nuove notizie)</a></li>', $menu);
            $menu =  str_replace('<li><a href="spaziopersonale.php?myeventi&amp;desc=1">Le tue partecipazioni ad eventi futuri</a></li>',
                                '<li><a href="spaziopersonale.php?myeventi&amp;desc=1">Le tue partecipazioni ad eventi futuri ('.sizeof($_SESSION['user']->partecipazione(false)).' eventi)</a></li>',  $menu); 
            $menu =  str_replace('<li><a href="spaziopersonale.php?myeventi&amp;pass&amp;desc=1">Le tue partecipazioni ad eventi passati</a></li>',
                                            '<li><a href="spaziopersonale.php?myeventi&amp;pass&amp;desc=1">Le tue partecipazioni ad eventi passati ('.sizeof($_SESSION['user']->partecipazione(true)).' eventi)</a></li>',$menu);
            $menu =  str_replace('<li><a href="spaziopersonale.php?mysocial">La lista degli utenti che segui</a></li>',
                                            '<li><a href="spaziopersonale.php?mysocial">La lista degli utenti che segui ('.sizeof(utente::listSocial($_SESSION['user']->giveUserName(),false)).' utenti)</a></li>',$menu);
            $menu =  str_replace('<li><a href="spaziopersonale.php?mysocial&amp;followers">La lista degli utenti che ti seguono</a></li>',
                                            '<li><a href="spaziopersonale.php?mysocial&amp;followers">La lista degli utenti che ti seguono ('.sizeof(utente::listSocial($_SESSION['user']->giveUserName(),true)).' utenti)</a></li>',$menu);
            $menu =  str_replace('<li><a href="spaziopersonale.php?myannunci=0">Gestione dei tuoi annunci pubblicati</a></li>',
                                            '<li><a href="spaziopersonale.php?myannunci=0">Gestione dei tuoi annunci pubblicati ('.sizeof(utente::EventiPubblicati($_SESSION['user']->giveUserName(), '', true)).' annunci)</a></li>',$menu);                          
        }
        return $menu;
        
    }
    private function buildUtenteNav($htmlpaginacorrente, $paginacorrente = -1){//metodo che ritorna una stringa che rappresenta la pagina corrente $paginacorrente  dello spazio utente(in base all'utente connesso), con contenuto $htmlpaginacorrente e con il 
    //link alla pagina corrente disattivato. Se la pagina utente non esiste viene mostrata la pagina 404
        $str='';
        if($_SESSION['user']->isAdmin()){
            if($this->esisteView(views::spaziopersonaleAdmin)){
                $str = str_replace('<div id="selectedNav"></div>','<div id="selectedNav">'."\n".$htmlpaginacorrente."\n</div>\n",
                                           file_get_contents(views::spaziopersonaleAdmin));
                switch($paginacorrente){
                    case 0: $str = str_replace('<li><a href="spazioadmin.php?listevsegnalati">Annunci segnalati dagli utenti</a></li>',
                    '<li class="clickedNav">Annunci segnalati dagli utenti</li>',$str); break;
                    case 1: $str = str_replace('<li><a href="spaziopersonale.php?searchuser">lista in ordine alfabetico crescente degli utenti iscritti</a></li>', 
                                '<li class="clickedNav">lista in ordine alfabetico crescente degli utenti iscritti</li>',$str); break;
                    case 2: $str = str_replace('<li><a href="spaziopersonale.php?searchuser&amp;desc">lista in ordine alfabetico decrescente degli utenti iscritti</a></li>', 
                                '<li class="clickedNav">lista in ordine alfabetico decrescente degli utenti iscritti</li>',$str); break;
                    case 3: $str = str_replace('<li><a href="spazioadmin.php?listevblocked">Annunci messi in quarantena</a></li>', 
                                  '<li class="clickedNav">Annunci messi in quarantena</li>',$str); break;
                        
                }
            }
            else $this->errore(0);
        }
        else{
            if($this->esisteView(views::spaziopersonaleUtente)){
                $str = str_replace('<div id="selectedNav"></div>','<div id="selectedNav">'."\n".$htmlpaginacorrente."\n</div>\n",
                                           file_get_contents(views::spaziopersonaleUtente));
                $str = $this->buildPremiumUserNav($str);
                switch($paginacorrente){
                    case 0: $str =  str_replace('<li><a href="spaziopersonale.php?news">Ultime notizie</a></li>',
                                   '<li class="clickedNav">Ultime notizie</li>',$str); break;
                    case 1: $str =  str_replace('<li><a href="spaziopersonale.php?myeventi&amp;desc=1">Le tue partecipazioni ad eventi futuri</a></li>',
                                            '<li class="clickedNav">Le tue partecipazioni ad eventi futuri</li>',$str); break;
                    case 2: $str =  str_replace('<li><a href="spaziopersonale.php?myeventi&amp;pass&amp;desc=1">Le tue partecipazioni ad eventi passati</a></li>',
                                            '<li class="clickedNav">Le tue partecipazioni ad eventi passati</li>',$str); break;
                    case 3: $str =  str_replace('<li><a href="spaziopersonale.php?mysocial">La lista degli utenti che segui</a></li>',
                                            '<li class="clickedNav">La lista degli utenti che segui</li>',$str); break;
                    case 4: $str =  str_replace('<li><a href="spaziopersonale.php?mysocial&amp;followers">La lista degli utenti che ti seguono</a></li>',
                                            '<li class="clickedNav">La lista degli utenti che ti seguono</li>',$str); break;
                    case 5: $str =  str_replace('<li><a href="spaziopersonale.php?myannunci=0">Gestione dei tuoi annunci pubblicati</a></li>',
                                            '<li class="clickedNav">Gestione dei tuoi annunci pubblicati</li>',$str); break;                          
                }
                
            }
            else $this->errore(0);
            
        }
        $str = $this->buildNavWithNumbers($str);
        return $str;
    }
    private function errore($code){//metodo che porta alla pagina di avviso errore in base al tipo di errore avvenuto rappresentato da $code
        switch ($code){
            case 0 : header('Location: '.views::page_404); break;
            case 1 : header('Location: '.views::page_403); break;
            case 2 : $this->messaggioImportante('<p><strong>Spiacenti &Egrave; avvenuto un errore interno, riprovare pi&ugrave; tardi</strong>.</p>'."\n".
                                               '<p><a href="spaziopersonale.php">Torna nel tuo spazio personale</a></p>'."\n".
                                               '<p><a href="index.php">Torna nella pagina principale</a></p>'."\n"); break;
        }
    }
    private function giveHeader($title, $paginacorrente = -1){//metodo che retituisce l'intestazione assegnando al tag title il valore della variabile $title e disattiavando il link alla pagina corrente $paginacorrente
        if($this->esisteView(views::headerfragment)){ 
            $risultato = file_get_contents(views::headerfragment);
            
            $s ='My Eventi';
            if($this->isUserConnected()){
            switch($_SESSION['user']->isAdmin()){
                case true: $risultato =  str_replace('<li><a accesskey="6" href="index.php?action=login">Accedi al tuo spazio personale</a></li>',
                            "<li><a accesskey=\"6\" href=\"spaziopersonale.php\">Il tuo spazio amministratore</a></li>\n".
                            '<li id="login_logout"><a accesskey="o" href="spaziopersonale.php?action=logout">Disconnettiti</a></li>', 
                            $risultato); break;
                case false: $risultato = str_replace('<li><a accesskey="6" href="index.php?action=login">Accedi al tuo spazio personale</a></li>',
                            "<li><a accesskey=\"6\" href=\"spaziopersonale.php\">Il tuo spazio personale</a></li>\n".
                            '<li id="login_logout"><a accesskey="o" href="spaziopersonale.php?action=logout">Disconnettiti</a></li>', 
                            $risultato); break;
            }
            }
            switch($paginacorrente){
                case 0: $risultato = str_replace('<li id="home"><a accesskey="1" href="index.php" xml:lang="en" lang="en">Home</a></li>', '<li class="clickedNav" xml:lang="en" lang="en">Home</li>',
                                         $risultato); break;
                case 1: $risultato = str_replace('<li><a accesskey="2" href="archivio.php?arch=types">Tipi di eventi</a></li>', '<li class="clickedNav">Tipi di eventi</li>',
                                      $risultato);break;
                case 2: $risultato = str_replace('<li><a accesskey="3" href="archivio.php?arch=categ">Categorie di eventi</a></li>', '<li class="clickedNav">Categorie di eventi</li>',
                                        $risultato); break;
                case 3: $risultato = str_replace('<li><a accesskey="4" href="archivio.php?citta">Eventi nella tua citt&agrave;</a></li>', '<li class="clickedNav">Eventi nella tua citt&agrave;</li>',
                                      $risultato); break;
                case 5: $risultato =  str_replace("<li><a accesskey=\"6\" href=\"spaziopersonale.php\">Il tuo spazio personale</a></li>\n","<li class=\"clickedNav\">Il tuo spazio personale</li>\n",$risultato); 
                                     $s = 'Il tuo spazio personale My Eventi'; break;
                case 6: $risultato =  str_replace("<li><a accesskey=\"6\" href=\"spaziopersonale.php\">Il tuo spazio amministratore</a></li>\n","<li class=\"clickedNav\">Il tuo spazio amministratore</li>\n",$risultato);
                                    $s = 'Il tuo spazio amministratore My Eventi';break;
            }
            $risultato = str_replace('<title></title>','<title>'.$title." - $s</title>",$risultato);       
            return $risultato;
        }
    }
    private function putInContenuto($html,$breadcrumb){//metodo che inserisce frammento HTML $html dentro al div del contenuto, comune delle pagine, cambiando il breadcrumb con $breadcrumb e restituisce il risultato ottenuto
            $risultato = str_replace('<p id="breadcrumb"></p>',($breadcrumb != '' ? '<p id="breadcrumb">Ti trovi in : '.$breadcrumb."\n</p>\n":''),
                                           file_get_contents(views::contentonly));
            $risultato = str_replace('<span id="content"></span>',"\n".$html."\n",$risultato);
            return $risultato;
    }
    private function buildPrimoPiano(){//metodo che costruisce il frammento HTML del primo piano e lo restituisce
        $eventi = evento::primopianoEvents();
        $str ='';
        $tot = sizeof($eventi);
        if($tot==0)
            $str = '<p>Nessun annuncio di evento in primo piano</p>';
        else{
            $str="<p><strong>Ci sono $tot annunci di eventi in primo piano</strong></p>\n<ul>\n";
            foreach($eventi as $ev){
               $str = $str.'<li><h2><a href="annuncio.php?view='.$ev->giveId().'">In primo piano - Annuncio di '.$ev->giveTipo().': '.$ev->Titolo()."</a></h2>\n";
                $str = $str.$ev->givebreveDesc(true)."\n";
                if($ev->countFoto() > 0)
                    $str = $str.'<a href="annuncio.php?view='.$ev->giveId().'">'.$ev->giveFoto(0)."</a>\n";
                $str = $str.'</li>'; 
            }
             $str=$str."</ul>\n";
        }
        return $str;
    }
    private function listUsersSocial($idu, $followers, $specificuser=''){//metodo che costruisce la lista degli utenti seguaci di $idu (se $followers=true), o quella degli utenti che $idu segue(se $followers= false),
    //aggiunge alla lista una form per cercare un utente tra il social di $idu. Se $specificuser != '' allora la lista non è altro che il risultato di una ricerca tra il social di $idu. Restituisce il risultato ottenuto
        $utenti = utente::listSocial($idu,$followers);
            $str = ''; $s = '';
                $form = $this->isUserConnected() ? $this->searchUserForm(false,$this->isUserConnected() && $_SESSION['user']->isAdmin()) : '';
                $str = $str."\n <div id=\"risultaticontent\">\n";
                
                $check = $this->isUserConnected() && $_SESSION['user']->giveUserName() == $idu;
                if($check){
                if ($followers)
                    $str = $str."<h4> Sono stati trovati <count> utenti che ti seguono </h4>\n";
                else{ $str = $str."<h4> Sono stati trovati <count> utenti che segui </h4>\n"; 
                    }
                }
                else $str = $str.($followers ? "<h4> Sono stati trovati <count> che seguono $idu </h4>\n" :
                        "<h4> Sono stati trovati <count> utenti che $idu segue</h4>\n");
                $links = '<p class="hidden" ><a accesskey="k" href="#searchUserform">Salta la lista '.($followers ? ' dei seguaci': ' degli utenti seguiti')." e vai a cerca un utente </a></p>\n";
                $count = 0;
                if(sizeof($utenti) > 0){
                    $links = '<p class="hidden"><a href="#listUsersfound" accesskey="j">Vai alla lista '.($followers ? ' dei seguaci': ' degli utenti seguiti').'</a></p>'."\n".$links;
                    $str = $str.$links."<ul id=\"listUsersfound\">\n";
                    foreach($utenti as $ut){
                        $aggiungi = $specificuser != '' ? strpos(mb_strtolower($ut, 'UTF-8'), mb_strtolower($specificuser, 'UTF-8')) !== false : TRUE;
                        if($aggiungi){
                            $s = $followers ? '' : ( $check ? "<span class=\"linkGest\">[<a href=\"spaziopersonale.php?segui=$ut\">smetti di seguire $ut</a>]</span>" : '');
                            $c = ($followers && $check ? 'che ti segue': (!$followers && $check ? 'che segui':(!$check&&$followers?"che segue $idu":"che $idu segue")));
                            $str = $str.'<li><a href="profilo.php?utente='.$ut.'">Utente '.($count+1)." $c : $ut</a> $s</li>\n";
                            ++$count;
                        }
                    }
                    $str = $str."</ul>\n";
                }
                $str = $str."</div>\n".$form;
                $str = str_replace('<count>',$count,$str);
                return $str;
    }
    private function segnalazioneEvFormBuild($action,$id){//metodo che costruisce il form per segnalare un annuncio e lo restituisce
        $maxcharactersrTextarea = DB_handler::maxLengthBrevDesc[1]*DB_handler::maxLengthBrevDesc[0];
        $str = "<form action=\"$action\" method=\"get\" id=\"segnalazione\">\n<p><strong> &Egrave; obbligatorio dare un motivo percui segnali l'annuncio. Scegli il motivo selezionando uno dei pulsanti di opzioni, e in caso di altro motivo, scrivere il motivo con al massimo $maxcharactersrTextarea caratteri</strong></p> ";
         
        $formTags = array();
        $label = array();
        $x=1;
        foreach(DB_handler::motivisegnalato as $value){
        $formTags[] = new input('motivo'.$x,'motivo',$value, 'radio'); 
        $label[] = $value;
        ++$x;
        }
        $links = '<a class="annulla" href="annuncio.php?view='.$id.'">Annulla e torna all\'annuncio</a>';
        if(!$this->isUserConnected() || !$_SESSION['user']->isAdmin())
        $links = $links.'<a class="annulla" href="index.php">Annulla e torna alla pagina principale</a>';
        else{
            $links = $links.'<a class="annulla" href="spazioadmin.php?listevsegnalati">Annulla e Torna agli annunci segnalati</a>';
        }
            
        $formTags[] = new input('altromotivoradio','motivo','Altro motivo', 'radio'); 
        $label[] = 'Altro motivo';
        $formTags[] = new textarea('altromotivo','altromotivo',DB_handler::maxLengthBrevDesc[1],DB_handler::maxLengthBrevDesc[0]);
        $label[] = 'In caso di altro motivo, scrivere obbligatoriamente  il motivo in un massimo di '.(DB_handler::maxLengthBrevDesc[1]*DB_handler::maxLengthBrevDesc[0]).' caratteri';
        $submit = '<input type ="hidden" name="segnalaannuncio" value="'.$id.'"/>'."\n".'<input type ="hidden" name="block"/>'."\n".
                '<input type ="submit" value="Invia"/>';

        $str = $str.$this->stampaFieldsetFormTagswithLabels('Seleziona il motivo :',$formTags, $label, $submit,'', true). "</form>\n".$links;
        return $str;
    }
    private function reasonblockUserFormBuild($idu){//Metodo che restituisce la form che richiede all'utente amministratore una spiegazione del blocco dell'utente $idu
        $str = "<form action=\"spazioadmin.php\" method=\"get\">\n<p><strong> &Egrave; obbligatorio inserire il motivo del blocco</strong></p> ";
         
        $formTags = array();
        $label = array();
        $formTags[] = new textarea('motivo','motivo',DB_handler::maxLengthBrevDesc[1],DB_handler::maxLengthBrevDesc[0]);
        $label[] = 'Motiva il blocco dell\' utente con al massimo '.(DB_handler::maxLengthBrevDesc[1]*DB_handler::maxLengthBrevDesc[0]).' caratteri';
        $submit = '<input type ="hidden" name="bloccauser" value="'.$idu.'"/>'."\n".'<input type ="hidden" name="block"/>'."\n".
                '<input type ="submit" value="Invia"/>';

        $str = $str.$this->stampaFieldsetFormTagswithLabels('Inserisci il motivo :',$formTags, $label, $submit). "</form>\n";
        return $str;
    }
    private function buildhiddensearchInput($type = -1, $cat = -1, $keyword = '', $citta = '', $owner='', 
                                    $fromgiorno='',$frommese='',$fromanno='',$togiorno='',$tomese='',$toanno='',
                                    $fromgiornopub='',$frommesepub='',$fromannopub='',$togiornopub='',$tomesepub='',$toannopub=''){
        //metodo che restituisce un array con in posizione 0 input di tipo hidden necessari per contenere i valori delle parole inserite dall'utente per cercare degli annunci. Questi input sono utili quando l'utente naviga tra le pagine dei risultati ottenuti.
        //in posizione 1 troviamo l'equivalente degli input in posizione, in forma di frammento per i link : pagina seguente e pagina precedente
        //Esempio: Quando l'utente va alla pagina due dei risultati, viene rieseguita la ricerca dai loro valori assegnati e si mostrano i risutati partendo da un certo indice dei risultati che è il risultato dell'operazione 2*maxperpage -1 
        $input =  '<input type="hidden" name="type" value="'.$type.'"/>'."\n".
                   '<input type="hidden" name="categ" value="'.$cat.'"/>'."\n".
                   '<input type="hidden" name="keyword" value="'.$keyword.'"/>'."\n".
                   '<input type="hidden" name="city" value="'.$citta.'"/>'."\n".
                   '<input type="hidden" name="publisher" value="'.$owner.'"/>'."\n".
                   '<input type="hidden" name="fromgiorno" value="'.$fromgiorno.'"/>'."\n".
                   '<input type="hidden" name="frommese" value="'.$frommese.'"/>'."\n".
                   '<input type="hidden" name="fromanno" value="'.$fromanno.'"/>'."\n".
                   '<input type="hidden" name="togiorno" value="'.$togiorno.'"/>'."\n".
                   '<input type="hidden" name="tomese" value="'.$tomese.'"/>'."\n".
                   '<input type="hidden" name="toanno" value="'.$toanno.'"/>'."\n".
                   '<input type="hidden" name="fromgiornopub" value="'.$fromgiornopub.'"/>'."\n".
                   '<input type="hidden" name="frommesepub" value="'.$frommesepub.'"/>'."\n".
                   '<input type="hidden" name="fromannopub" value="'.$fromannopub.'"/>'."\n".
                   '<input type="hidden" name="togiornopub" value="'.$togiornopub.'"/>'."\n".
                   '<input type="hidden" name="tomesepub" value="'.$tomesepub.'"/>'."\n".
                   '<input type="hidden" name="toannopub" value="'.$toannopub.'"/>'."\n";
          $link = "type=$type&amp;categ=$cat&amp;keyword=$keyword&amp;city=$citta&amp;publisher=$owner&amp;fromgiorno=$fromgiorno&amp;frommese=$frommese&amp;fromanno=$fromanno&amp;togiorno=$togiorno".
                 "&amp;tomese=$tomese&amp;toanno=$toanno&amp;fromgiornopub=$fromgiornopub&amp;frommesepub=$frommesepub&amp;fromannopub=$fromannopub&amp;togiornopub=$togiornopub".
                 "&amp;tomesepub=$tomesepub&amp;toannopub=$toannopub";
        return array($input, $link);
    }
    private function buildsearchBox($type = -1, $cat = -1, $keyword = '', $citta = '', $owner='', 
                                    $fromgiorno='',$frommese='',$fromanno='',$togiorno='',$tomese='',$toanno='',
                                    $fromgiornopub = '',$frommesepub ='',$fromannopub='',$togiornopub='',$tomesepub='',$toannopub=''){
          // Metodo che restituisce la form di ricerca avanzata di annunci con valori di default passati come parametro
        $tipiev = DB_handler::TipiDiEventi();
                $tipi = new select('type','type');
                $db = new DB_handler;
                $tipi->addOption('Tutte le tipologie di eventi', -1); 
                foreach ($tipiev as $value) 
                    $tipi->addOption($value, $db->giveIdTypeEvent($value));
                $cate = DB_handler::CategorieEventi();
                $categ = new select('categ','categ');
                $categ->addOption('Tutte le categorie', -1); 
                foreach ($cate as $value) 
                $categ->addOption($value, $db->giveIdCatEvent($value));   
                if($type != -1)
                    $tipi->setValue($db->giveNomeTypeEvent($type));
                if($cat != -1)
                    $categ->setValue($db->giveNomeCatEvent($cat));
                    
                $word = new input('keyword','keyword',$keyword); 
                $word->setMaxlength(views::maxLengthSearchinput);
                $city = new input('city','city',$citta); 
                $city->setMaxlength(views::maxLengthSearchinput);
                $publisher = new input('publisher','publisher',$owner); 
                $publisher->setMaxlength(views::maxLengthSearchinput);
                
                $rangedate = '<fieldset class="rangedata">'."\n".
                            '<legend>Data dell\' evento</legend>'."\n".
                            "<p><em>Scegli un intervallo di tempo in cui si svolger&agrave; l'evento.</em>\n ".
                            "<strong> Inseririsci 2 cifre per il giorno e il mese, e 4 cifre per l'anno. Esempio: 23 per il giorno, 09 per il mese e 2017 per l'anno.</strong></p>\n".
                            '<div class="interv"><p><strong>Dal:</strong></p><div><label for="fromgiorno"><span class="hidden">Campo facoltativo: Inserisci 2 cifre per il </span>Giorno<span class="hidden"> di partenza in cui l\'evento che cerchi si svolger&agrave;, <em>ad esempio: 15</em></span></label>'."\n".
                            '<input type="text" name="fromgiorno" id="fromgiorno" maxlength="2" value="'.$fromgiorno.'"/></div>'."\n".
                            '<div><label for="frommese"><span class="hidden">Campo facoltativo: Inserisci 2 cifre per il </span>Mese<span class="hidden"> di partenza in cui l\'evento che cerchi si svolger&agrave;, <em>ad esempio: 04</em></span></label>'."\n".
                            '<input type="text" name="frommese" id="frommese" maxlength="2" value="'.$frommese.'"/></div>'."\n".
                            '<div><label for="fromanno"><span class="hidden">Campo facoltativo: Inserisci 4 cifre per l\' </span>Anno<span class="hidden"> di partenza in cui l\'evento che cerchi si svolger&agrave;, <em>ad esempio: 2017</em></span></label>'."\n".
                            '<input class="anno" type="text" name="fromanno" id="fromanno" maxlength="4" value="'.$fromanno.'"/></div></div>'."\n".
                            '<div class="interv"><p><strong>Al:</strong></p><div><label for="togiorno"><span class="hidden">Inserisci 2 cifre per il </span>Giorno<span class="hidden"> finale in cui l\'evento che cerchi finir&agrave;, <em>ad esempio: 15</em></span></label>'."\n".
                            '<input type="text" name="togiorno" id="togiorno" maxlength="2" value="'.$togiorno.'"/></div>'."\n".
                            '<div><label for="tomese"><span class="hidden">Campo facoltativo: Inserisci 2 cifre per il </span>Mese<span class="hidden"> finale in cui l\'evento che cerchi finir&agrave;, <em>ad esempio: 04</em></span></label>'."\n".
                            '<input type="text" name="tomese" id="tomese" maxlength="2" value="'.$tomese.'"/></div>'."\n".
                            '<div><label for="toanno"><span class="hidden">Campo facoltativo: Inserisci 4 cifre per l\' </span>Anno<span class="hidden"> finale in cui l\'evento che cerchi finir&agrave;<em>ad esempio: 2017</em></span></label>'."\n".
                            '<input class="anno" type="text" name="toanno" id="toanno" maxlength="4" value="'.$toanno.'"/></div></div>'.
                            "\n</fieldset>\n";
                $rangedate2 = '<fieldset class="rangedata">'."\n".
                            '<legend>Data di pubblicazione dell\'annuncio</legend>'."\n".
                            "<p><em>Scegli un intervallo di tempo in cui l' annuncio di evento &egrave; stato pubblicato.</em> \n ".
                            "<strong> Inseririsci 2 cifre per il giorno e il mese, e 4 cifre per l'anno. Esempio: 23 per il giorno, 09 per il mese e 2017 per l'anno.</strong></p>\n".
                            '<div class="interv"><p><strong>Dal:</strong></p><div><label for="fromgiornopub"><span class="hidden">Inserisci 2 cifre per il </span>Giorno<span class="hidden"> iniziale dell\'intervallo di tempo di pubblicazione degli annunci di eventi, <em>ad esempio: 15</em></span></label>'."\n".
                            '<input type="text" name="fromgiornopub" id="fromgiornopub" maxlength="2" value="'.$fromgiornopub.'"/></div>'."\n".
                            '<div><label for="frommesepub"><span class="hidden">Campo facoltativo: Inserisci 2 cifre per il </span>Mese<span class="hidden"> iniziale dell\'intervallo di tempo di pubblicazione degli annunci di eventi, <em>ad esempio: 04</em></span></label>'."\n".
                            '<input type="text" name="frommesepub" id="frommesepub" maxlength="2" value="'.$frommesepub.'"/></div>'."\n".
                            '<div><label for="fromannopub"><span class="hidden">Campo facoltativo: Inserisci 4 cifre per l\' </span>Anno<span class="hidden"> iniziale dell\'intervallo di tempo di pubblicazione degli annunci di eventi, <em>ad esempio: 2017</em></span></label>'."\n".
                            '<input class="anno" type="text" name="fromannopub" id="fromannopub" maxlength="4" value="'.$fromannopub.'"/></div></div>'."\n".
                            '<div class="interv"><p><strong>Al:</strong></p><div><label for="togiornopub"><span class="hidden">Inserisci 2 cifre per il </span>Giorno<span class="hidden"> finale dell\'intervallo di tempo di pubblicazione degli annunci di eventi, <em>ad esempio: 15</em></span></label>'."\n".
                            '<input type="text" name="togiornopub" id="togiornopub" maxlength="2" value="'.$togiornopub.'"/></div>'."\n".
                            '<div><label for="tomesepub"><span class="hidden">Campo facoltativo: Inserisci 2 cifre il </span>Mese<span class="hidden"> finale dell\'intervallo di tempo di pubblicazione degli annunci di eventi, <em>ad esempio: 04</em></span></label>'."\n".
                            '<input type="text" name="tomesepub" id="tomesepub" maxlength="2" value="'.$tomesepub.'"/></div>'."\n".
                            '<div><label for="toannopub"><span class="hidden">Campo facoltativo: Inserisci 4 cifre per l\' </span>Anno<span class="hidden"> finale dell\'intervallo di tempo di pubblicazione degli annunci di eventi, <em>ad esempio: 2017</em></span></label>'."\n".
                            '<input class="anno" type="text" name="toannopub" id="toannopub" maxlength="4" value="'.$toannopub.'"/></div></div>'.
                            "\n</fieldset>\n";
                
                $submit = '<input type ="submit" value="cerca"/>'; 
                
                $formTags = array( $word, $city, $publisher, $tipi, $categ);
                $label = array('<span class="hidden">Campo facoltativo: </span>Inserisci una parola chiave caratteristica dell\'evento ( al massimo '.views::maxLengthSearchinput.' caratteri), <em>ad esempio: Informatica</em>',
                        '<span class="hidden">Campo facoltativo: </span>Inserisci una citt&agrave; dove si svolger&agrave; l\'evento, ( al massimo '.views::maxLengthSearchinput.' caratteri), <em>ad esempio: Treviso</em>', 
                        '<span class="hidden">Campo facoltativo: </span>Inserisci il nome utente, nome o cognome del pubblicatore dell\'annuncio ( al massimo '.views::maxLengthSearchinput.' caratteri)',
                '<span class="hidden">Campo facoltativo: </span>Seleziona il tipo di evento ( Modifica la voce corrente scegliendo una delle opzioni )','<span class="hidden">Campo facoltativo: </span> Seleziona una categoria ( Modifica la voce corrente scegliendo una delle opzioni )');
                return $this->stampaFieldsetFormTagswithLabels('Ricerca avanzata di annunci',$formTags, $label, $rangedate.$rangedate2.$submit,
                '',false,'<p><a href="index.php?action=faq&amp;id=advancedsearch">Ottieni informazioni e aiuto sulla ricerca avanzata (verrai indirizzato alla pagina di aiuto)</a></p><p><em>Tutti i campi sono facoltativi</em></p>');
    }
    private function segnalaErroreForm($paginahtmltoString, $errore, $iserrore=false, $IdAndValuInputText = array(), 
                                        $IdAndValuTextArea = array(), $selectsOption = array()){
        //Metodo che segnala un messaggio di errore o di successo (variabile $iserrore), sulla pagina HTML $paginahtmltoString contenente la form e setta i valori di default dei tag input, textarea e select con gli array associativi:
        //$IdAndValuInputText, $IdAndValuTextArea, $selectsOption 
        $risultato = '';
        if(!is_array($errore)){
            $risultato =  $iserrore ? str_replace('<div id="hide"></div>',"<div id=\"showerror\"><p><strong>&Egrave; AVVENUTO UN ERRORE : $errore</strong></p></div>", 
                            $paginahtmltoString) : ( $errore != '' ? str_replace('<div id="hide"></div>',"<div id=\"shownoerror\"><p><strong>$errore</strong></p></div>", 
                            $paginahtmltoString) : str_replace('<div id="hide"></div>','', $paginahtmltoString)); 
        }
        else{
            $str = "\n<ul>\n";
            foreach($errore as $er)
                $str = $str."<li>$er</li>\n";
        $risultato =  $iserrore ? str_replace('<div id="hide"></div>',"<div id=\"showerror\"><p><strong>SONO AVVENUTI DEGLI ERRORI:</strong></p>$str</div>", 
                            $paginahtmltoString) : ( sizeof($errore) > 0 ? str_replace('<div id="hide"></div>',"<div id=\"shownoerror\">$str</div>", 
                            $paginahtmltoString) : str_replace('<div id="hide"></div>','', $paginahtmltoString)); 
        }
                            
        foreach ($IdAndValuInputText as $id => $val){
                $val = DB_handler::escapeCharacters($val);
                $risultato = str_replace("id=\"$id\"","id=\"$id\" value =\"$val\"", $risultato);
        }
        foreach ($IdAndValuTextArea as $id => $val){
                $val = DB_handler::escapeCharacters($val);
                $risultato = str_replace("</textarea>\n<span id=\"$id"."ReloadValue\"></span>",
                "$val</textarea>\n<span id=\"$id"."ReloadValue\"></span>", $risultato);
        }
        foreach ($selectsOption as $val){
            $risultato = str_replace("<option value=\"$val\">","<option value=\"$val\" selected=\"selected\">", $risultato);
        }
                
        echo $risultato;
    }
    private function stampaFieldsetFormTagswithLabels($legenda, $formTags = array(), $label = array(), $childFieldset = '',$idfieldset='',$putindiv=false,$messaggiohtmlfieldset='', $class=''){
      //Metodo che costruisce una fieldset con legenda $legend, con i tag nell'array $formTags, con i correspettivi label dendtro l'array $label,  con il/i figlio/i e discendenti fieldset in forma stringa dentro la variabile $childFieldset
      //Con id fieldeset = $idfieldset, e classe = $class, con un messaggio HTML valido $messaggiohtmlfieldset. Se $putindiv=true allora i tag del fieldset vengono ciascuno messi dentro a dei div 
      $list = ''; 
      if(count($formTags) == count($label)){
        $i = 0;
        $list = '<fieldset'.($idfieldset != ''?" id=\"$idfieldset\" ":' ').($class != ''?" class=\"$class\" ":' ').">\n<legend>$legenda</legend>\n $messaggiohtmlfieldset\n";
        foreach ($formTags as $value) {
                    $list = $list.($putindiv?'<div>':'').$value->printWithLabel($label[$i]).($putindiv?'</div>':'')."\n";
                    ++$i;
        }
        $list = $list.$childFieldset."\n</fieldset>\n";
      }
      return $list;
    }
    private function buildform($tipovalue=-2, $catValue=-2, $denomValue=-2, $editmode = false, $id = '', $owner='',$error = false){
        //Metodo che restituisce la form per creare o modificare un annuncio di evento con i valori di default passati. Se $error =true allora il chiamante è in fase di errore un ricaricamento della form con i valori di default. Se
        //$editmode=true allora si tratta di una modifica dell'annuncio e quindi il chiamante chiede la form con i valori dell'annuncio che sono stati salvati
        if($_SESSION['user']->isConnected()){
            if($this->esisteView(views::addEvent)){
                $risultato = file_get_contents(views::addEvent);
                $ev = new evento($id, $owner);
                $risultato = str_replace('<title></title>','<title>'.($editmode ? 'Modifica l\'annuncio di evento dal titolo: '.$ev->Titolo(): 'Pubblica un nuovo annuncio di evento').' - My Eventi</title>',$risultato);
              
                //CAMPI OBBLIGATORI
                $campo = '<span class="hidden"><strong>Campo obbligatorio : </strong></span> ';
                $db= new DB_handler;
                $tipiev = DB_handler::TipiDiEventi();
                $tipi = new select('tipiEventi','tipiEventi');
                foreach ($tipiev as $value) 
                    $tipi->addOption($value, $db->giveIdTypeEvent($value));
                if($tipovalue != -2)
                    $tipi->setValueFromIndex($tipovalue);
                $cat = DB_handler::CategorieEventi();
                $categ = new select('categ','categ');
                foreach ($cat as $value) 
                    $categ->addOption($value,$db->giveIdCatEvent($value));
                if($catValue != -2)
                    $categ->setValueFromIndex($catValue);
                $denom = views::denominazioneUrbanSelect();
                
                if($denomValue != -2)
                    $denom->setValueFromIndex($denomValue);
                else
                    $denom->setValueFromIndex(views::giveIndexDenomfromName('via'));
                if($editmode && $tipovalue==-2 && $catValue==-2 && $denomValue==-2){
                    if(!$error){
                        $denom->setValueFromIndex(views::giveIndexDenomfromName($ev->givedDenomurb()));
                        $tipi->setValue($ev->giveTipo());
                        $categ->setValue($ev->giveCateg());
                    }
                    $risultato = str_replace('<span id="forEdit"></span>','<div id="editfotolink"><a href="spaziopersonale.php?action=editfoto&amp;event='.$id.'">'.
                    'Salta la modifica testuale dell\'annuncio e Vai a Gestione Foto </a></div>', 
                            $risultato);
                    $risultato = str_replace('<input type="hidden" name="insertNewEvent"/>','<input type="hidden" name="editEvent" value ="'.$id.'"/>', 
                            $risultato);   
                }
                $titolo = new input('titolo','titolo',$ev->Titolo()); 
                $titolo->setMaxlength(DB_handler::maxLengthTitolo);
                
                $y = ''; 
                $m = '';
                $d = '';
                
                $date = DateTime::createFromFormat("d/m/Y", $ev->dataEvento());
                if($date !== false){
                $y = $date->format("Y"); 
                $m = $date->format("m");
                $d = $date->format("d");
                }
                
                $giorno = new input('giorno','giorno',$d);
                $giorno->setMaxlength(2); 
                $mese = new input('mese','mese', $m);
                $mese->setMaxlength(2);
                $anno = new input('anno','anno',$y);
                $anno->setMaxlength(4);
                $anno->setClass('anno');
                $ora= new input('ora','ora',$ev->giveOra());
                $ora->setMaxlength(2);
                $minuto= new input('minuti','minuti',$ev->giveMinuto());
                $minuto->setMaxlength(2);
                $Ora = $this->stampaFieldsetFormTagswithLabels('Inserisci l\'ora e il minuto in cui inizier&agrave; l\'evento',array($ora,$minuto), array($campo.($editmode ? 'Modifica ' : 'Inserisci ').'l\' Ora <span class="hidden"> in cui si svolger&agrave; l\'evento con al massimo 2 cifre <em>Ad esempio: 17 se inizia alle ore 17</em></span>',
                                                                $campo.($editmode ? 'Modifica ' : 'Inserisci ').'il Minuto <span class="hidden"> in cui si svolger&agrave; l\'evento con al massimo 2 cifre <em>Ad esempio: 10 se inizia al minuto 10</em></span>'),
                                                               '','',true,"<p><strong>Inseririsci al massimo 2 cifre per l'ora, da 0 a 23, e al massimo 2 cifre per il minuto, da 0 a 59. Esempio: 15 per l'ora, 5 per il minuto.</strong></p>\n", 'interv');
                $data = $this->stampaFieldsetFormTagswithLabels('Scrivere il giorno, il mese e l\'anno in cui ci svolger&agrave; l\'evento',array($giorno,$mese,$anno), array($campo.($editmode ? 'Modifica ' : 'Inserisci ').'il giorno <span class="hidden"> in cui si svolger&agrave; l\'evento con al massimo 2 cifre, <em>Ad esempio: 05 se sar&agrave; il giorno 5</em></span>',
                                    $campo.($editmode ? 'Modifica ' : 'Inserisci ').'il mese <span class="hidden"> in cui si svolger&agrave; l\'evento con al massimo 2 cifre, <em>Ad esempio: 09 se sar&agrave; a Settembre</em></span>',
                                    $campo.($editmode ? 'Modifica ' : 'Inserisci ').'l\' anno <span class="hidden"> in cui si svolger&agrave; l\'evento con esattamente 4 cifre, <em>Ad esempio: 2018 </em></span>'),
                                                               '','',true,"<p><strong> Inseririsci al massimo 2 cifre per il giorno, da 0 a 31, e al massimo 2 cifre per il mese, da 1 a 12, e finalmente 4 cifre per l'anno. Esempio: 23 per il giorno, 09 per il mese e 2017 per l'anno.</strong></p>\n", 'interv');
                $dataeora = $this->stampaFieldsetFormTagswithLabels('Quando si svolger&agrave; ?',array(), array(),$data.$Ora,'',true,'','rangedata');
                
                $indirizzo = new input('via','via',$ev->giveVia());
                $indirizzo->setMaxlength(30);
                $comune = new input('com','com', $ev->giveCitta());
                $comune->setMaxlength(20);
                $prov = new input('prov','prov',$ev->giveProv());
                $prov->setMaxlength(20);
                $indirizzo = $this->stampaFieldsetFormTagswithLabels('Dove si svolger&agrave; ?',array($denom, $indirizzo,$comune,$prov),
                                    array($campo.'Seleziona la denominazione urbanistica, <em> una tra: via, viale, corso, piazza, eccetera ... </em><span class="hidden"> in cui si svolger&agrave; l\'evento</span>, Modifica la voce corrente scegliendo una delle opzioni',
                                    $campo.'Inserisci il nome dell\'indirizzo con numero civico ( al massimo 30 caratteri ) <span class="hidden"> in cui si svolger&agrave; l\'evento</span> , <em>Esempio: Eremitani 13 A</em>',
                                    $campo.'Inserisci il nome del comune o citt&agrave; ( al massimo 20 caratteri ) <span class="hidden"> in cui si svolger&agrave; l\'evento</span>, <em>Esempio: Valsanzibio</em>',
                                    $campo.'Inserisci il nome della provincia del comune o della citt&agrave;( al massimo 20 caratteri ) <span class="hidden"> in cui si svolger&agrave; l\'evento</span> , <em>Esempio: Padova</em>')
                                    );
                
                
                $formTags = array($titolo, $tipi, $categ);
                $label = array($campo.($editmode ? 'Modifica ' : 'Inserisci ').'il titolo dell\'annuncio ( al massimo '.DB_handler::maxLengthTitolo.' caratteri ), <em> Ad esempio: NUOVI CORSI DI CINESE - ISTITUTO CONFUCIO</em>',
                               $campo.'Seleziona il tipo dell\' evento (Modifica la voce corrente scegliendo una delle opzioni )',
                                $campo.'Seleziona la categoria dell\'evento (Modifica la voce corrente scegliendo una delle opzioni )');
                
                $str = $this->stampaFieldsetFormTagswithLabels('Inserisci le informazioni caratteristiche dell\'evento :',$formTags, $label, $dataeora.$indirizzo,'infoinput');
   
                
                
                unset($formTags);
                unset($label);  
                //CAMPI FACOLTATIVI
                $campo2 = '<span class="hidden"><strong>Campo facoltativo : </strong></span> ';
                $foto = '';
                if(!$editmode){
                $tempfield = '';
                for ($x = 1; $x <= evento::$maxFoto; $x++) {
                    $fotoLabel = array();
                    $fotoInput = new input("Foto$x","Foto$x",'','file');
                    $desc = new input("DescFoto$x","DescFoto$x");
                    $desc->setMaxlength(evento::$maxLengthDescFoto);
                    $fotoLabel = array("$campo2 Scegli la foto numero $x tra i tuoi <span xml:lang=\"en\" lang=\"en\">file</span> in uno dei formati ".evento::formatiFotoWithAbbr().'. La foto pu&ograve; pesare al pi&ugrave; 1 <span xml:lang="en" lang="en">Megabyte</span>.',"<strong>Inserisci obbligatoriamente una breve descrizione della foto $x selezionata</strong> ( al massimo 70 caratteri )");
                    $tempfield = $tempfield.$this->stampaFieldsetFormTagswithLabels("Carica la foto numero $x",array($fotoInput, $desc),$fotoLabel,'','',false, '', 'uploadsfotos');
                } 
                
                $foto = $this->stampaFieldsetFormTagswithLabels('Carica '.evento::$maxFoto.' foto dell\'annuncio',array(),array(),$tempfield,'jumptofoto',false,'<p><em>Il caricamento di foto &egrave; facoltativo ma consigliato'.
                             '. Una locandina dell\' annuncio potrebbe attrare di pi&ugrave; l\' utente qualora l\' annuncio venisse messo in primo piano.</em>'.
                             ' <a href="index.php?action=faq&amp;id=uppp">Scopri come mettere il tuo annuncio in primo piano</a>. <strong>Attenzione : per ogni foto caricata &egrave; obbligatorio inserire una breve descrizione chiara e comprensibile.'.
                             ' La descrizione vuota di una foto caricata comporter&agrave; il non caricamento di tale foto e di tutte le altre foto caricate con o senza descrizione.</strong> <em><a href="index.php?action=faq&amp;id=upfoto">Ottieni maggiori informazioni sulle foto di un annuncio</a>.</em></p>'.
                             '<p><strong>I formati foto accettati sono</strong>: '.evento::formatiFotoWithAbbr().'</p>'.
                             '<p><strong>Ogni foto può pesare al pi&ugrave;: '.evento::maxsizeFoto().'</strong></p>'.
                             '<p class="hidden"><a href="#pubadesso">Salta il caricamento di foto</a></p>');
                }
                
                $etInput = array();
                $etLabel = array();
                $tot = $ev->countEtich();
                $index = 0;
                for ($x = 1; $x <= evento::$maxEtichette; $x++) {
                    $temp = new input("etich$x","etich$x", $index < $tot ? $ev->giveEtich($index): '');
                    $temp->setMaxlength(DB_handler::maxLengthEtichetta);
                    $etInput[] = $temp;
                    $etLabel[] = "$campo2 Inserisci l' etichetta $x <span class=\"hidden\"><em>( una parola chiave riguardante il contesto dell'evento)</em></span>, al massimo ".DB_handler::maxLengthEtichetta." caratteri, i caratteri in pi&ugrave; verrano ignorati";
                    ++$index;
                } 
                $etichette = $this->stampaFieldsetFormTagswithLabels('Inserisci delle etichette ( al massimo '.DB_handler::maxLengthEtichetta.' caratteri per ogni etichetta )',$etInput,$etLabel,'','etichette',true,
                            '<p><em>Le etichette sono parole chiavi riguardanti il contesto dell\'evento che permetteranno al tuo annuncio di apparire nei risultati di ricerca ogni volta che l\' utente inserir&agrave;'.
                            ' almeno una di queste parole. Le etichette sono facoltative ma il mancato inserimento influenzer&agrave; le apparizioni dell\' annuncio nei risultati di ricerca.'.
                            ' Si consiglia di inserire etichette, ovvero parole chiavi che riguardano il conteso dell\' evento. <a href="index.php?action=faq&amp;id=upetich">Ottieni maggiori informazioni sulle etichette</a>.</em> </p>'.
                            '<p class="hidden"><a href="#'.($editmode ? 'pubadesso':'jumptofoto').'">Salta l\' inserimento di etichette</a></p>');
                                    
                $breveDesc = new textarea('breveDesc','breveDesc',DB_handler::maxLengthBrevDesc[1],DB_handler::maxLengthBrevDesc[0],$error ? '' : $ev->givebreveDesc());
                $breveDesc->setMessage('<p><em>La breve introduzione viene visualizzata come anteprima del tuo annuncio nei risultati di ricerca. Si consiglia di scrivere una breve introsduzione accattivante.</em></p>');
                $totDesc = new textarea('totDesc','totDesc',DB_handler::maxLengthDesc[1],DB_handler::maxLengthDesc[0],$error ? '' : $ev->giveDesc()) ;
                $totDesc->setMessage('<p><em>Si chiede di scrivere con il massimo della precisione, tutto quello che gli utenti devono sapere su questo evento: di cosa si tratta, come partecipare eccetera ...</em></p>');
                
                $formTags = array($breveDesc, $totDesc);
                $label = array($campo.($editmode ? 'Modifica la ' : 'Inserisci una ').'breve introduzione dell\'annuncio ( al massimo '.DB_handler::maxLengthBrevDesc[0] * DB_handler::maxLengthBrevDesc[1].' caratteri )',
                               $campo.($editmode ? 'Modifica la descrizione dell\'evento ' : 'Descrivi l\'evento con il massimo della precisione').' ( al massimo '.DB_handler::maxLengthDesc[0] * DB_handler::maxLengthDesc[1].' caratteri )');
                
                $descs = $this->stampaFieldsetFormTagswithLabels('Descrizione dell\'evento :',$formTags, $label, '','descfield');
                $risultato = str_replace('<span id="campiobbligatori"></span>',"<div class=\"particompilazione\">\n<h2>CAMPI OBBLIGATORI</h2>\n".
                                            "<p><strong>ATTENZIONE : </strong> i campi che seguono sono tutti obbligatori e devono essere compilati. I campi senza valore non verrano accettati e verr&agrave; generato un messaggio di errore. 
                                            <a href=\"index.php?action=faq&amp;id=upmustfield\">Ottieni maggiori informazioni e aiuto sui campi obbligatori</a>.</p>\n".
                                            "$str\n$descs\n</div>\n", $risultato);
                $risultato = str_replace('<span id="campifacoltativi"></span>',"<div class=\"particompilazione noshowprint\">\n<h2>CAMPI FACOLTATIVI</h2>\n".
                                        "<p>I campi in questa parte sono facoltativi e non devono essere necessariamente compilati. Tuttavia il loro mancato valore potrebbe influenzare il posizionamento dell'annuncio nei risultati di ricerca. 
                                         <a href=\"index.php?action=faq&amp;id=upfacofield\">Ottieni maggiori informazioni e aiuto sui campi facoltativi</a>.</p>\n".
                                        "$etichette $foto\n</div>\n",$risultato);
                $risultato = str_replace('<legend id="bigTitle"></legend>','<legend id="bigTitle">'.($editmode? 'MODIFICA DELL\' ANNUNCIO DAL TITOLO: '.$ev->TITOLO() : 'PUBBLICA UN NUOVO ANNUNCIO DI EVENTO').'</legend>',$risultato);
                $risultato = str_replace('<span class="annulla"></span>','<a class="annulla" accesskey="n" href="spaziopersonale.php?myannunci">'.($editmode? 'Annulla l\'operazione di modifica dell\'annuncio e Torna alla gestione dei tuoi annunci pubblicati' : 'Annulla l\'inserimento di un nuovo annuncio e Torna alla gestione dei tuoi annunci pubblicati').'</a>',$risultato);
                
                                  
            return $risultato;
            }
        }
        else{
           return  "Errore";
        }
    }
    
    private function HomePageUtente(){//Metodo che dà la pagina principale dello spazio utente, attualmente rappresentata con la pagina delle ultime notizie per gli utenti non amministratori e dalla pagina degli eventi segnalati per gli utenti amministratori
        if(!$_SESSION['user']->isAdmin())
        header('Location: spaziopersonale.php?news');
        else 
        header('Location: spazioadmin.php?listevsegnalati');
    }
    private function vaiaPaginaform($action, $totpagine, $paginacorrente){//Metodo che restituisce una mini form composta da select e bottone che permette di navigare tra un numero $totpagine di pagine nell'azione $action e settando il 
//valore di default della select a $paginacorrente+1 se esite altrimenti a $paginacorrente-1 in modo da suggerire all'utente che esiste una succesiva o precedente    
        $select = new select('goToPage','goToPage');
        for($i = 0; $i < $totpagine; ++$i){
            $select->addOption(($i+1)." su $totpagine",$i+1);
        }
        $p = "\n<div>".($paginacorrente > 1 ? '<span class="prevpage"><a href="'.$action.'?goToPage='.($paginacorrente - 1).'&undefined"><span class="hidden">Vai alla</span> pagina prima <span class="hidden">dei risultati</span></a></span>' : '');
        $p = $p.($paginacorrente < $totpagine ? '<span class="nextpage"><a href="'.$action.'?goToPage='.($paginacorrente + 1).'&undefined"><span class="hidden">Vai alla</span> pagina dopo <span class="hidden">dei risultati</span></a></span>' : '');
        $p =$p."</div>";
        $select->setValue(($paginacorrente < $totpagine ? $paginacorrente+1 : 1)." su $totpagine");
        $form = "<div id=\"gotopagina\">\n<form action=\"$action\" method=\"get\">\n".
        $this->stampaFieldsetFormTagswithLabels('Vai a pagina dei risultati',array($select),array('Seleziona una pagina dei risultati'),'<span id="hiddeninput"></span>'."\n".
                                                '<input type="submit" name="submit" value="Vai"/>'."\n", '',true,$p).
        "</form>\n</div>\n";
        return $form;
    }
    private function modificaFotoEventoPage($idev){//metodo che restituisce la pagina di gestione delle foto di un annuncio di evento
        if($this->esisteView(views::editFoto)){
            $ev = new evento($idev,$_SESSION['user']->giveUserName());
            
            $str = '<span id="hide"></span>'."\n".
            '<div ><a class="annulla" accesskey="n" href="spaziopersonale.php?myannunci&amp;desc">Esci da gestione foto e torna ai tuoi annunci</a></div>'."\n".
            '<p class="mobilegodown"><a href="#newFoto">Salta la lista delle foto caricate e vai ad aggiungi foto</a></p>'."\n".
            '<div id="oldFoto">'."\n<h2>Foto caricate:</h2>\n".
            '<p><a href="annuncio.php?showfoto='.$idev.'&amp;editmode"><span class="hidden"><strong>Pagina consigliata a persone vedenti: </strong></span>Mostra le foto non ridimensionate</a></p>'."\n";
            $tot = $ev->countFoto();
            if($tot > 0){
                $str = $str."\n<ol>\n";
                for($i = 0; $i < $tot; ++$i)
                    $str = $str.'<li><h4>Foto caricata '.($i+1).'</h4><span>'.$ev->giveFoto($i).'</span><span class="linkGest"> [<a href="spaziopersonale.php?elim=foto&amp;index='.
                                                                $i.'&amp;ev='.$idev.'">elimina la foto caricata '.($i+1).'</a>]</span>'."\n</li>\n";
                $str = $str.'</ol>';
            }
            else{
                $str = $str."<p>Non ha ancora caricato nessuna foto per questo evento.</p>\n";
            }
            $str = $str."\n</div>\n";
            
            
            $str = $str.'<div id="newFoto">'."\n<h2>Aggiungi nuove foto:</h2>\n";
            if($tot < evento::$maxFoto){
                $diff = evento::$maxFoto-$tot; 
                $str = $str."\n<p><em>Puoi ancora aggiungere $diff foto.</em></p>\n";
                $str = $str.'<form id= "newfotouploads" action="spaziopersonale.php" method="post" enctype="multipart/form-data">'."\n";
            
                
                $foto = '';
                $tempfield = '';
                for ($x = 1; $x <= $diff; $x++) {
                    $fotoLabel = array();
                    $fotoInput = new input("Foto$x","Foto$x",'','file');
                    $temp = new input("DescFoto$x","DescFoto$x");
                    $temp->setMaxlength(evento::$maxLengthDescFoto);
                    $fotoLabel[] = "Scegli la foto numero $x su $diff che puoi ancora aggiungere, tra i tuoi <span xml:lang=\"en\" lang=\"en\">file</span> in uno dei formati ".evento::formatiFotoWithAbbr().'. La foto pu&ograve; pesare al pi&ugrave; 1 <span xml:lang="en" lang="en">Megabyte</span>.';
                    $fotoLabel[] = "<strong>Inserisci obbligatoriamente una breve descrizione della foto $x selezionata</strong> ( al massimo 70 caratteri )";
                    $tempfield = $tempfield ."\n". $this->stampaFieldsetFormTagswithLabels("Carica la foto numero $x",array($fotoInput, $temp),$fotoLabel,'','',false, 
                    '', 'uploadsfotos');
                }
                $submit = "\n".'<input type="hidden" name="addfoto" value="'.$idev.'"/>'."\n".'<input type="submit" name="submit" value="Aggiungi le foto caricate"/>'."\n";
                $foto = $this->stampaFieldsetFormTagswithLabels('Caricamento foto',array(),array(),$tempfield.$submit,'',false,
                '<p><strong>Attenzione: &Egrave; obbligatorio dare una una descrizione per ogni foto caricata. Ogni foto senza descrizione non verrà accettata.</strong></p>'.
                    '<p><strong>I formati foto accettati sono</strong>: '.evento::formatiFotoWithAbbr().'</p>'.
                    '<p><strong>Ogni foto può pesare al pi&ugrave; '.evento::maxsizeFoto().'</strong></p>');
                
                $str = $str.$foto."\n</form>\n";
                }
                else{
                $str = $str."<p><strong>Non &egrave; pi&ugrave; possibile aggiungere ulteriori foto. Il limite massimo di foto &egrave; stato raggiunto.</strong></p>\n";
            }
            $str = $str."\n</div>\n".'<p class="mobilegoup viewinmobilelink"><a href="#oldFoto">Torna alla lista delle foto caricate</a></p>'."\n";
            $str = str_replace('<div id="oldFoto"></div><div id="newFoto"></div>',$str,
                                   file_get_contents(views::editFoto));
            $str = str_replace('<title></title>','<title>Gestione foto del tuo annuncio pubblicato con titolo: '.$ev->Titolo().' - My Eventi</title>',
                                   $str);
            return $str;
        }
        
        
    }
    public function HomePageSito(){//Metodo che porta alla pagina principale del sito
        if($this->esisteView(views::contentonly)){
               $str = '<div id="primo_piano"><h1>In Primo Piano</h1>'."\n".$this->buildPrimoPiano().'</div>';
               $str = $this->putInContenuto($str, 'Home  - In primo piano');
               echo $this->giveHeader('Home', 0) .$str.file_get_contents(views::footer);
                
        }
    }
    public function loginPage(){//Metodo che porta alla pagina di accesso utente
        if($this->isUserConnected()){
            header("Location: spaziopersonale.php");
            }
            elseif($this->esisteView(views::login)){
                 echo file_get_contents(views::login);  
            }
                
    }
    public function spazioUtente($idu ='', $password=''){//Metodo che connette l'utente se esiste e non è stato bloccato, e lo porta al suo spazio personale. Altrimenti rdà un messaggio di errore se l'utente non è stato bloccato, oppure un 
    //messaggio di avvenuto blocco dell'utente se l'utente è stato bloccato
        if(isset($_SESSION) ){
            if(!isset($_SESSION['user']))
                $_SESSION['user'] = new utente($idu,$password);
        
            if($_SESSION['user']->isConnected()){
                $this->HomePageUtente();
            }
            else{ 
                unset($_SESSION['user']);
                if(utente::isUtenteBlocked($idu, $password)){
                $this->messaggioImportante('<p><strong>Ciao '.$idu.' .</strong> Il tuo profilo risulta bloccato per il seguente motivo: '.
                                          utente::MotivoUtenteBlocked($idu).'. </p>'.
                                          '<p>Se credi che sia stato un errore, mandaci la tua revendicazione alla mail admin@myeventi.it</p>'.
                                          '<p><a href="index.php">Torna nella pagina principale</a></p>');
                }
                else{
                    $inputtext = array('nomeutente'=>$idu, 'password'=>$password);
                    $this->segnalaErroreForm(file_get_contents(views::login),'Nome utente o password non corretto',true, $inputtext);
                    }
                }
       }
    }
    public function signupPage(){//Metodo che porta alla pagina di iscrizione al sito
        if($this->esisteView(views::signup))
            echo file_get_contents(views::signup);
    }
    public function logout(){//metodo che disconnette l'utente e porta alla pagina principale
        if(isset($_SESSION['user'])){
            unset($_SESSION['user']);
        }
        header('Location: index.php');  
    }
    public function addNewEventPage(){//Metodo che porta alla form di creazione di un annuncio di evento, se l'utente connesso è un utente premium
       if($this->isUserConnected()){
            if($_SESSION['user']->isPremium()){
                echo $this->buildform();
            }
            else $this->errore(1);
       }
       else $this->loginPage();
    }
    public function aggiungiUtente($cogn, $nom, $mail, $idu, $pass1, $pass2){//Metodo che aggiunge un nuovo utente, ritorna true se non ci sono stati errori nella registrazione, altrimenti restituisce false e seegnala l'errore avvenuto 
        $errore = utente::addNewUser($cogn, $nom, $mail, $idu, $pass1, $pass2);
        if($errore != ""){
            $inputtext = array('cognome'=>$cogn, 'nome'=>$nom,
                               'mail'=>$mail, 'nomeutente'=>$idu);
            $this->segnalaErroreForm(file_get_contents(views::signup),"$errore",true,$inputtext);
            return false;
        }
        return true;
    }
    public function aggiungiEvento($tit, $b_desc, $desc, $tipo, $categ, $g, $m, $a,$o,$min, $denom, $via, $comune, $prov, $foto,$fotoName,$fotoDesc, $etich){
        //Metodo che aggiunge un nuovo annuncio di evento se il pubblicatore è connesso ed è premium. Ritorna true se non ci sono stati errori nell'aggiunta, altrimenti restituisce false e seegnala l'errore avvenuto 
        if($this->isUserConnected()){
            if($_SESSION['user']->isPremium()){
            $denom2 = views::giveDenomfromindex($denom);
            $errore = $_SESSION['user']->addNewEvent($tit, $b_desc, $desc, $tipo, $categ, $g, $m, $a,$o,$min, $denom2, $via, $comune, 
                                                    $prov,$foto,$fotoName,$fotoDesc,$etich);
            if($errore[0] == true){
                $inputtext = array('titolo'=>$tit, 'giorno'=>$g, 'mese'=>$m, 'anno'=>$a, 
                                    'via'=>$via, 'com'=>$comune, 'prov'=>$prov);
                for ($x = 1; $x <= evento::$maxEtichette && $etich[$x-1] != ''; ++$x) {
                    $inputtext += array("etich$x" => $etich[$x-1]);
                }                     
                $textarea = array('breveDesc'=>$b_desc,'totDesc'=>$desc);
                $this->segnalaErroreForm($this->buildform((int)$tipo, (int)$categ,(int)$denom),$errore[1],true,$inputtext, $textarea);
                return false;
            }
            $this->messaggioImportante('<p><strong>L\'annuncio &egrave; stato pubblicato con successo!</strong>'.
                                        ' <a href="annuncio.php?view='.$errore[1].'">Leggi l\'annuncio</a>,'.
                                        ' <a href="spaziopersonale.php?action=nuovoannunciopagina">pubblica un altro annuncio</a>,'.
                                        ' oppure <a href="spaziopersonale.php?myannunci=0">Torna ai tuoi annunci pubblicati</a> </p>');
            $_SESSION['user']->notificaSeguaci($_SESSION['user']->giveUserName().' ha pubblicato un nuovo annuncio dal titolo: '.$tit,'annuncio.php?view='.$errore[1],'E');
            return true; 
            }
            else $this->errore(1);
        }
        else $this->loginPage();
        return false;
    }
    public function updateInfoEvento($idev,$tit, $b_desc, $desc, $tipo, $categ, $g, $m, $a,$o,$min, $denom,$via, $comune, $prov,$etich){
        //Metodo che aggiorna i dettagli dell'annuncio con id=$idev se quello che lo chiede è un utente connesso, premium e proprietario dell'annuncio. Ritorna true se la modifica è avvenuta con successo altrimenti restituisce false e segnala l'errore avvenuto
        if($this->isUserConnected()){
            if($_SESSION['user']->isPremium()){
            $denom2 = views::giveDenomfromindex($denom);
            $errore = $_SESSION['user']->editEventInfo($idev, $tit, $b_desc, $desc, $tipo, $categ, $g, $m, $a,$o, $min, $denom2, $via, $comune,$prov,$etich);
            if($errore[0] ===true){
                $inputtext = array('titolo'=>$tit, 'giorno'=>$g, 'mese'=>$m, 'anno'=>$a,
                                    'via'=>$via, 'com'=>$comune, 'prov'=>$prov);
                for ($x = 1; $x <= evento::$maxEtichette && $etich[$x-1] != ''; ++$x) {
                    $inputtext += array("etich$x" => $etich[$x-1]);
                }                     
                $textarea = array('breveDesc'=>$b_desc,'totDesc'=>$desc);
                $this->segnalaErroreForm($this->buildform((int)$tipo, (int)$categ,(int)$denom,true,$idev, $_SESSION['user']->giveUserName(), true),$errore[1],true,$inputtext, $textarea);
                return false;
            }
            $this->messaggioImportante('<p><strong>L\'annuncio &egrave; stato modificato con successo!</strong>'.
                                        ' <a href="annuncio.php?view='.$idev.'">Leggi l\'annuncio</a>,'.
                                        ' <a href="spaziopersonale.php?action=nuovoannunciopagina">pubblica un altro annuncio</a>,'.
                                        ' oppure <a href="spaziopersonale.php?myannunci=0">Torna ai tuoi annunci pubblicati</a> </p>');
            return true;
            }
            else $this->errore(1);
        }
        else $this->loginPage();
        
    }
    public function showEventDetails($id){//metodo che mostra in pagina HTML, tutto l'annuncio $id nei minimi dettagli a tutti gli utenti, e aggiunge un div con opzioni di modifica se l'utente che lo visualizza è il proprietario o l'amministratore, 
    //altrimenti mostra un div con un link al segnala annuncio per gli altri utenti. 
        if($this->esisteView(views::contentonly)){
            $ev = new evento($id);
            $risultato = $ev->viewHtmlStringInfoEvent($id);
            if($risultato == '')
                $this->errore(0);
            else{
				
                $db = new DB_handler;
                $checkConnected = false;
                if(!$ev->isBlocked()|| ($this->isUserConnected() && ($_SESSION['user']->giveUserName() == $ev->giveOwner() || $_SESSION['user']->isAdmin()))){
                    $risultato = str_replace('<span id="datapub"></span>','<p id="infoPublicazione">Questo annuncio &egrave; stato pubblicato il '.$ev->dataPub().
                            ' da '.$ev->giveOwner().', <a href="profilo.php?utente='.$ev->giveOwner().'">'."Visita il profilo di ".$ev->giveOwner().", il pubblicatore di questo annuncio, per maggiori informazioni e altri annunci pubblicati da lui</a></p>\n", $risultato);
                    if($ev->isBlocked()){
                        $risultato = str_replace('<span id="blockedevent"></span>','<p><strong>ATTENZIONE :</strong> Questo annuncio &egrave; stato bloccato per il seguente motivo: '.$ev->MotivoBlocked()
                                                                                 .'. Risulta perciò temporaneamente inaccessibile agli utenti.</p>'.(!$_SESSION['user']->isAdmin() ?
                                                                                 '<p>Le chiediamo di rileggere le nostre <a href="index.php?action=faq&amp;id=rulesPublish">regole di pubblicazione</a> e di modificare l\'annuncio entro 15 giorni di tempo, '.
                                                                                 'in modo che sia compattibile con le nostre regole e di nuovo accessibile agli utenti.'."\n</p>" : '')."\n",
                                           $risultato);
                   
                    }
                    if($ev->countFoto() > 0 )
						$risultato = str_replace('<span id="messfoto"></span>', '<p><strong><a href="annuncio.php?showfoto='.$id.'"><span class="hidden">Pagina consigliata a persone vedenti: </span>Mostra le foto non ridimensionate dell\'annuncio</a></strong></p>', $risultato);
                    $checkyoung = !$ev->isOld() && !$ev->isBlocked();
                    $checkConnected = $this->isUserConnected();
                    $link ='<h3>Partecipazioni</h3>'."\n";
                    $link = $link .($checkyoung ? '<p>'.$ev->totPartecipazioni().' utenti parteciperanno a questo evento</p>':
                                      '<p>'.$ev->totPartecipazioni().' utenti hanno partecipato a questo evento</p>');
                    $s = '';
                    $gestUt = '<div id="gestUt">';    
                    if( $checkConnected){
                        if($_SESSION['user']->isAdmin())
                            $gestUt = $gestUt."\n".(!$ev->isBlocked() ? (!DB_handler::isinprimopiano($id)?'<span>[<a href="spazioadmin.php?putprimopiano='.$id.'&amp;conf">metti questo annuncio in primo piano</a>]</span> ':
                                    '<span>[<a href="spazioadmin.php?putprimopiano='.$id.'">rimuovi questo annuncio dal primo piano</a>]</span>').
                                    '<span>[<a href="spazioadmin.php?quarantine='.$id.'&amp;block">metti in quarantena questo annuncio</a>]</span>':
                                   '<span>[<a href="spazioadmin.php?quarantine='.$id.'">rimuovi questo annuncio dalla quarantena</a>]</span>').
                                  '<span>[<a href="spaziopersonale.php?elim=evento&amp;ev='.$id.'">elimina questo annuncio</a>]</span>'.
                                  (!utente::isUtenteBlocked($ev->giveOwner())?'<span>[<a href="spazioadmin.php?bloccauser='.$ev->giveOwner().
                                    '&amp;block">blocca chi ha pubblicato l\'annuncio</a>] </span>' : '<span>[<a href="spazioadmin.php?bloccauser='.$ev->giveOwner().
                                    '">sblocca chi ha pubblicato l\'annuncio</a>]</span>' );
                        else{
                            if($_SESSION['user']->isParticipant($id))
                                $link = $link.'<span id="linkpart"><a href="annuncio.php?ritira='.$id.'"><span class="hidden">Partecipi gi&agrave; a questo evento : </span>RITIRA LA TUA PARTECIPAZIONE QUESTO EVENTO</a></span>';
                            elseif($checkyoung)
                                $link = $link.'<span id="linkpart"><a href="annuncio.php?partecipa='.$id.'"><span class="hidden">Non partecipi ancora a questo evento : </span>PARTECIPA ALL\'EVENTO</a></span>';
                            if($_SESSION['user']->giveUserName() == $ev->giveOwner()) 
                                $gestUt = $gestUt."\n<h3>Impostazioni annuncio</h4>".'<span>[<a href="spaziopersonale.php?action=edit&event='.$id.'">modifica questo annuncio</a>]</span>'.
                                  '<span>[<a href="spaziopersonale.php?elim=evento&amp;ev='.$id.'">elimina l\'annuncio</a>]</span>';
                        }
                }
                if($checkyoung && !$checkConnected){
                        $link = $link.'<span id="linkpart"><a href="annuncio.php?partecipa='.$id.'"><span class="hidden">Non partecipi ancora a questo evento : </span>PARTECIPA ALL\'EVENTO</a></span>';
                }
                    if(($checkConnected && !$_SESSION['user']->isAdmin() && $ev->giveOwner() != $_SESSION['user']->giveUserName()) || !$checkConnected)
                    $s = '<span>[<a href="annuncio.php?segnalaannuncio='.$id.'">Segnala questo annuncio</a>]</span>';
                $risultato = str_replace('<div id="partecipazioni"></div>','<div id="partecipazioni">'.$link.
                                    '<p><a href="annuncio.php?partecipanti='.$id.'" >Lista dei partecipanti a questo evento ('.$ev->totPartecipazioni().' utenti)</a></p>'.'</div>',
                                           $risultato);
                $risultato = $risultato.$gestUt.$s."\n</div>\n";
                $risultato = $this->putInContenuto($risultato, '<a href="index.php">Home</a> > <a href="archivio.php?search&amp;type='.$db->giveIdTypeEvent($ev->giveTipo()).'">'.
                                                                $ev->giveTipo().'</a> > Dettagli annuncio dal titolo: <em>'.$ev->Titolo().'</em>');
                
            }
            else $risultato = $this->putInContenuto('<strong>ATTENZIONE: </strong>Questo annuncio &egrave; stato bloccato per violazione delle <a href="index.php?action=faq&amp;id=rulesPublish">regole di pubblicazione</a>','<a href="index.php">Home</a> > <a href="archivio.php?search&amp;type='.$db->giveIdTypeEvent($ev->giveTipo()).'">'.
                                                                $ev->giveTipo().'</a> > Dettagli annuncio dal titolo: <em> > '.$ev->Titolo().'</em>');
            $l = $checkConnected &&($ev->giveOwner() == $_SESSION['user']->giveUserName() || $_SESSION['user']->isAdmin()) ? 
            '<p class="mobilegodown viewinmobilelink"><a href="#gestUt">Vai alle impostazioni di questo tuo annuncio</a></p>'."\n":
            '<p class="mobilegodown viewinmobilelink"><a href="#gestUt">Vai a segnala questo annuncio</a></p>'."\n";
            $risultato = str_replace('<span id="navigazione"></span>','<p class="mobilegodown viewinmobilelink"><a href="#titolo">Vai al titolo dell\'annuncio</a></p>'."\n".
            '<p class="mobilegodown viewinmobilelink"><a href="#bdesc">Vai all\' introduzione testuale dell\'annuncio</a></p>'."\n".
            '<p class="mobilegodown viewinmobilelink"><a href="#desc">Vai alla descrizione dell\' evento oggetto</a></p>'."\n".
            '<p class="mobilegodown viewinmobilelink"><a href="#infoev">Vai a informazioni sull\'annuncio</a></p>'."\n".
            '<p class="mobilegodown viewinmobilelink"><a href="#partecipazioni">Vai a Partecipazioni dell\'evento</a></p>'."\n".
            '<p class="mobilegodown viewinmobilelink"><a href="#fotoev">Vai a foto caricate dell\'annuncio</a></p>'."\n".$l,
            $risultato);
            echo $this->giveHeader('Informazioni dettagliate sull\' annuncio '.$ev->giveTipo().': '.$ev->Titolo()).$risultato.file_get_contents(views::footer);
            }
        }                
    }
    public function myAnnunci($page=1, $ord=0, $keyword=''){//Metodo che porta alla pagina $page degli annunci pubblicati dall'utente connesso se questo è premium. Se $ord != 0 o $keyword != '', allora si tratta di una ricerca eseguita tra i suoi annunci pubblicati 
        if($this->esisteView(views::spaziopersonaleUtente)){
            if($this->isUserConnected()){
                if(! $_SESSION['user']->isPremium()){
                   $this->errore(1);
                   return;
                }
              
                $eventiPub = utente::EventiPubblicati($_SESSION['user']->giveUserName(), $keyword, true);
                $eventiPub = evento::ordinaeventi($eventiPub, ($ord == 2 || $ord==3), ($ord == 0 || $ord==2));
                $ordi = ($ord == 1 || $ord==3) ? 'crescente' : 'decrescente';
                $ordi = ($ord == 0 || $ord == 1 ? ' per data di pubblicazione di annuncio ' : ' per data di svolgimento ').$ordi;
                $totRecords = sizeof($eventiPub);
                $totPage = floor($totRecords / views::maxRecordPerPage);
                if($totRecords % views::maxRecordPerPage != 0 || $totPage == 0)
                    ++$totPage;
                $str = '<div id="risultaticontent"><h4>'.$totRecords." annunci pubblicati da te sono stati trovati".($totRecords > 0 ?", la lista che segue &egrave; ordinata $ordi": '')."</h4>\n";
                $select = new select('myannunci', 'myannunci');
                $select->addOption('Ordina per data di pubblicazione decrescente', 0);
                $select->addOption('Ordina per data di pubblicazione crescente', 1);
                $select->addOption('Ordina per data di svolgimento crescente', 3);
                $select->addOption('Ordina per data di svolgimento decrescente', 2);
                $select->setValueFromIndex($ord);
                $form = '<div id="searchUserform"><form action="spaziopersonale.php" method="get">'.
                            '<fieldset>'."\n".
                            '<legend>Cerca tra i tuoi annunci pubblicati</legend>'."\n".
                            '<label for="myannunci">Scegli l\'ordine di apparizione</label>'."\n".
                            $select->printTag()."\n".
                            '<label for="keyword">Inserisci una parola caratteristica dell\'annuncio specifico. <em>Esempio: Conferenza</em></label>'."\n".
                            '<input type="text" name="keyword" id="keyword" maxlength="'.views::maxLengthSearchinput.'" value="'.$keyword.'" />'.
                            '<input type="submit" value="Filtra" />'."\n".
                            '<div><a href="archivio.php?publisher='.$_SESSION['user']->giveUserName().'">Ricerca avanzata dei tuoi annunci pubblicati </a></div>'.
                            "\n</fieldset>\n ".
                            "\n</form>\n </div>\n";
                $head = $this->vaiaPaginaform('spaziopersonale.php',$totPage, $page) ;
                $head = str_replace('<span id="hiddeninput"></span>', '<input type="hidden" name="myannunci" value="'.$ord.'"/>'."\n".
                                                                      '<input type="hidden" name="keyword" value="'.$keyword.'"/>'."\n"  , $head);
                $head = str_replace('&undefined', '&amp;myannunci='.$ord.'&amp;keyword='.$keyword, $head);
                if($totRecords > 0){
                $links = '<p class="mobilegodown viewinmobilelink"><a accesskey="j" href="#lista_SUresults">Vai alla lista dei tuoi annunci pubblicati</a></p>'."\n".
                        '<p class="mobilegoup viewinmobilelink"><a accesskey="k" href="#searchUserform">Vai a Cerca tra i tuoi annunci pubblicati</a></p>'."\n".
                         '<p class="mobilegodown viewinmobilelink"><a accesskey="l" href="#gotopagina">Vai ad una pagina specifica della lista dei tuoi annunci pubblicati</a></p>'."\n";
                $str= $str.$links."<p class=\"paginacorrente\">Pagina $page su $totPage</p>".
                '<ol id="lista_SUresults">'."\n";
                $count = 0;
                for($i = ($page - 1)*views::maxRecordPerPage; $i < $totRecords && $count < views::maxRecordPerPage; ++$i){
                    $str = $str.'<li><p><a href="annuncio.php?view='.$eventiPub[$i]->giveId().'">Annuncio '.($i+1)." su $totRecords pubblicato  il ".$eventiPub[$i]->datapub().': '.$eventiPub[$i]->giveTipo().' - '.$eventiPub[$i]->Titolo()."</a></p>\n".
                    '<p class="linkGest">[<a href="annuncio.php?partecipanti='.$eventiPub[$i]->giveId() .'">'.$eventiPub[$i]->totPartecipazioni().' partecipanti per l\'annuncio '.($i+1).' di evento </a>]  '.
                    '[<a href="spaziopersonale.php?action=edit&amp;event='.$eventiPub[$i]->giveId() .'">modifica l\'annuncio '.($i+1).'</a>]  '.
                    '[<a href="spaziopersonale.php?action=editfoto&amp;event='.$eventiPub[$i]->giveId() .'">Gestisci le foto dell\' annuncio '.($i+1).'</a>]  '.
                        '[<a href="spaziopersonale.php?elim=evento&amp;ev='.$eventiPub[$i]->giveId() .'">elimina l\' annuncio '.($i+1)."</a>]</p></li>\n";
                    ++$count;
                }
                $str = $str."</ol>\n<p class=\"paginacorrente\"><span class=\"hidden\">Fine </span>Pagina $page su $totPage</p>\n<p class=\"mobilegoup viewinmobilelink\"><a href=\"#searchUserform\">Salta le altre pagine degli annunci pubblicati trovati e vai a cerca tra i tuoi annunci pubblicati</a></p>\n".
                $head;
                }
                $str = $str."\n<p class=\"mobilegoup viewinmobilelink\"><a href=\"#navUtente\">Torna nel men&ugrave; del tuo spazio personale</a></p></div>\n".$form;
                $str = $this->buildUtenteNav($str,5);
                if($str != ''){
                    $str = $this->putInContenuto($str,'<a href="index.php">Home</a> > Il tuo spazio personale: Gestione dei tuoi annunci pubblicati');                
                    echo $this->giveHeader("Gestione dei tuoi annunci pubblicati ($totRecords annunci), pagina $page su $totPage dei risultati", 5) . $str.file_get_contents(views::footer);
                }
            }
            else $this->loginPage();   
        }            
    }
    public function ultimeNotizie($tipo = ''){//Metodo che porta alle ultime notizie indirizzate all'utente connesso. Se $tipo != '' allora si tratta di ultime notizie di tipo $tipo
        if($this->esisteView(views::spaziopersonaleUtente)){
            if($this->isUserConnected()){
                $select = new select('news', 'news');
                $select->addOption('Tutte le notizie', -1);
                foreach(utente::tipidiNotizie as $key => $n){
                    $select->addOption($n, $key);
                    if($key == $tipo)
                        $select->setValue($n);
                }
                $form = '<div id="searchUserform"><form action="spaziopersonale.php" method="get">'.
                            '<fieldset>'."\n".
                            '<legend>Cerca tra le notizie della tua rete sociale</legend>'."\n".
                            '<label for="news">Seleziona il tipo di notizie che vuoi sapere. Modifica la voce corrente scegliendo una delle opzioni</label>'."\n".
                            $select->printTag()."\n".
                            '<input type="submit" value="filtra" />'.
                            "\n</fieldset>\n </form>\n </div>\n".'<a class ="hidden" href="#risultaticontent"> Torna alla lista delle ultime notizie</a>';
                $news = $_SESSION['user']->lastNewsHtmlString($tipo!= -1 ? $tipo : '');
                $str = '<div id="risultaticontent">'."\n".$news[0]."\n</div>\n <p class=\"hidden\"><a accesskey=\"m\" href=\"#navUtente\"> Torna nel men&ugrave; di navigazione del tuo spazio personale</a></p>".$form;
                $str = str_replace('<span id="comment"></span>',($news[1] > 0 ? '<p class="mobilegodown viewinmobilelink"><a href="#risultlist" accesskey="j">Vai alle notizie</a></p>' : '')."\n".
                                   '<p class="hidden" ><a accesskey="k" href="#searchUserform"> Vai a cerca tra le notizie</a></p>', $str);
                $str = $this->buildUtenteNav($str,0);
                if($str != ''){
                    $str = $this->putInContenuto($str, '<a href="index.php">Home</a> > Il tuo spazio personale: Ultime notizie');
                    echo $this->giveHeader('Ultime notizie della tua rete sociale; '.$news[1].' nuove notizie trovate', 5) . $str.file_get_contents(views::footer);
                }
               
            }
            else $this->loginPage();   
        }            
    }
    public function myEventi($passati = false, $page = 1, $desc=1){//Metodo che porta alla pagina  $page che elenca le partecipazioni future ad eventi futuri($passati=false) o passati ($passati=true), ordnati per data di svolgimento 
    // recente($desc=0) o meno recente($desc=1)
        if($this->esisteView(views::spaziopersonaleUtente)){
            if($this->isUserConnected()){ 
                $eventiPub = $_SESSION['user']->partecipazione($passati);
                $eventiPub = evento::ordinaeventi($eventiPub, true, (bool)$desc);
                $totRecords = sizeof($eventiPub);
                $ord = 'la lista che segue &egrave; ordinata '.($desc == 0 ? 'per data di svolgimento pi&ugrave; vicina' : 'per data di svolgimento meno vicina');
                $str = '<div id="risultaticontent">'.(!$passati ? '<h4> Hai '.$totRecords." eventi in arrivo":
                        '<h4> Hai partecipato a '.$totRecords." eventi").($totRecords > 0? ", $ord": '')."</h4>\n";
                $select = new select('myeventi', 'myeventi');
                $select->addOption('Ordina per data di svolgimento pi&ugrave; vicina', 0);
                $select->addOption('Ordina per data di svolgimento meno vicina', 1);
                $select->setValueFromIndex((int)$desc);
                $form = '<div id="searchUserform"><form action="spaziopersonale.php" method="get">'.
                            '<fieldset>'."\n".
                            '<legend>Ordina le tue partecipazioni ad eventi</legend>'."\n".
                            '<label for="myeventi">Scegli l\'ordine della lista delle tue partecipazioni. Modifica la voce corrente scegliendo una delle opzioni</label>'."\n".
                            $select->printTag()."\n".
                            ($passati ? '<input type="hidden" name="pass"/>'."\n" :'').
                            '<input type="submit" value="ordina" />'.
                            "\n</fieldset>\n </form>\n </div>\n";
                $totPage = floor($totRecords / views::maxRecordPerPage);
                if($totRecords % views::maxRecordPerPage != 0 || $totPage == 0)
                    ++$totPage;
                $p1 = $totRecords > 0 ? "\n<p class=\"paginacorrente\">Pagina $page su $totPage</p>\n" : '';
                $p2 = $totRecords > 0 ? "\n<p class=\"paginacorrente\"><span class=\"\">Fine </span>Pagina $page su $totPage</p>\n" : '';
                $links = ( $totRecords > 0 ? '<p class="mobilegodown viewinmobilelink"><a accesskey="j" href="#lista_SUresults">Vai alla lista delle tue partecipazioni ad eventi '.(!$passati ? 'in arrivo':'passati').'</a></p>'."\n".
                        '<p class="mobilegoup viewinmobilelink"><a accesskey="k" href="#searchUserform">Vai a ordina le tue partecipazioni ad eventi</a></p>'."\n".
                         '<p class="mobilegodown viewinmobilelink"><a accesskey="l" href="#gotopagina">Vai ad una pagina specifica delle tue partecipazioni '.(!$passati ? 'in arrivo':'passati').'</a></p>'."\n" : '');
                $str = $str.$links.$p1;
                $head = $totRecords > 0 ? $this->vaiaPaginaform('spaziopersonale.php',$totPage,$page) : '';
                $head = str_replace('<span id="hiddeninput"></span>', '<input type="hidden" name="myeventi" value="'.$desc.'"/>'."\n".
                                   ($passati ? '<input type="hidden" name="pass"/>'."\n": ''), $head);
                $head = str_replace('&undefined', '&amp;myeventi'.$desc.($passati ? '&pass': ''), $head);
                $count = 0;
                if($totRecords > 0){
                    
                $str = $str.'<ol id="lista_SUresults">'."\n";
                for($i = ($page - 1)*views::maxRecordPerPage; $i < $totRecords && $count < views::maxRecordPerPage; ++$i){
                    $str = $str.'<li><p><a href="annuncio.php?view='.$eventiPub[$i]->giveId().
                        '">Evento '.($i+1).' '.(!$passati ? 'in arrivo' : 'passato').' : In data '.$eventiPub[$i]->dataEvento().' '.(!$passati ? 'si terr&agrave;' : 'Si &egrave; tenuto').' l\' evento '.$eventiPub[$i]->giveTipo().' : '.$eventiPub[$i]->Titolo().
                    "</a></p>\n".'<p class="linkGest">[<a href="annuncio.php?ritira='.$eventiPub[$i]->giveId().'"> Ritira la tua partecipazione all\'evento '.($i+1).' </a>]  </p>'."\n</li>\n";
                    ++$count;
               }
                $str = $str."</ol>$p2".'<p class="mobilegoup viewinmobilelink"><a href="#searchUserform">Salta le altre pagine e vai a ordina le tue partecipazioni ad eventi</a></p>'.$head;
                }
                $str = $str."\n</div> \n<p class=\"mobilegoup viewinmobilelink\"><a accesskey=\"m\" href=\"#navUtente\"> Torna nel men&ugrave; di navigazione del tuo spazio personale</a></p>\n".$form;
                $str = $this->buildUtenteNav($str,$passati ? 2 : 1);
                if($str != ''){
                    $str = $this->putInContenuto($str, '<a href="index.php">Home</a> > Il tuo spazio personale: '.($passati == false ? 'Le tue partecipazioni ad eventi futuri':
                                            'Le tue partecipazioni ad eventi passati'));
                    echo $this->giveHeader('La lista delle tue parteciazioni ad eventi '.($passati ? 'passati' :'futuri' )." ($totRecords eventi) pagina $page su $totPage dei risultati", 5) . $str.file_get_contents(views::footer);
                }
               
            }
            else $this->loginPage();   
        }            
    }
    public function seguiUser($idu, $value){//Metodo che permette all'utente connesso di seguire($value=true), o di smettere di seguire($value=false) l'utente $idu
        if($this->isUserConnected()){
            $ris = $value ? $_SESSION['user']->seguiUtente($idu) : $_SESSION['user']->smettiSeguire($idu);
            if($ris){
                if($value){
                $messaggio = '<p>Adesso segui '.$idu.' riceverai notizie sulle sue attivit&agrave; come:</p>'."\n<ul>\n".
                                           "<li>Pubblicazione di un annuncio</li>\n".
                                           "<li>Partecipazione ad eventi</li>\n </ul>".
                                           '<p><a href="spaziopersonale.php?segui='.$idu.'">Scegli di smettere di seguire '.$idu."</a></p>\n".
                                           '<p><a href="spaziopersonale.php?mysocial"> Torna alla lista degli utenti che segui. '."</a></p>\n".
                                           '<p><a href="profilo.php?utente='.$idu.'"> Torna a visitare il profilo di '.$idu.'</a>'."</p>\n";
                    $_SESSION['user']->notificaUtente($idu,$_SESSION['user']->giveUserName().' ti segue adesso','profilo.php?utente='.$_SESSION['user']->giveUserName(), 'A');
                }
                 else 
                     $messaggio = '<p>Non segui pi&ugrave; '.$idu.' e non riceverai pi&ugrave; notizie sulle sue attivit&agrave;</p>'."\n".
                                           '<p><a href="spaziopersonale.php?segui='.$idu.'&amp;conf"> Scegli di riseguire '.$idu."</a>. Oppure</p>\n".
                                           '<p><a href="spaziopersonale.php?mysocial"> Torna alla lista degli utenti che segui. '."</a></p>\n".
                                           '<p><a href="profilo.php?utente='.$idu.'"> Torna a visitare il profilo di '.$idu.'</a>'."</p>\n";
                
                $this->messaggioImportante($messaggio);
            }
            else $this->errore(2);
        }else $this->loginPage();  
    }
    public function mySocial($followers, $specificuser=''){//Metodo che visualizza la lista degli utenti seguiti($followers=false) o che seguono($followers=true) l'utente connesso. Se $specificuser != '' allora si tratta di una ricerca tra il suo social 
        if($this->esisteView(views::spaziopersonaleUtente)){
            if($this->isUserConnected()){
              $str = $this->listUsersSocial( $_SESSION['user']->giveUserName(), $followers, $specificuser);  
              $str = $this->buildUtenteNav($str, !$followers ? 3 : 4);
              $str = $str.'<p class="mobilegoup viewinmobilelink"><a href="#navUtente">Torna nel men&ugrave; del tuo spazio personale</a></p>';
              if($str != ''){
                $str = $this->putInContenuto($str, '<a href="index.php">Home</a> > Il tuo spazio personale: La tua rete sociale - '.($followers == false ? 'La lista degli utenti che segui':
                                            'La lista degli utenti che ti seguono'));
                echo $this->giveHeader($followers ? 'Lista degli utenti che ti seguono ':'Lista degli utenti che segui',5) . $str.file_get_contents(views::footer);
              }
            }
            else $this->loginPage();   
        }            
    }
    public function social($idu,$followers, $specificuser=''){//Metodo che visualizza la lista degli utenti seguiti($followers=false) o che seguono($followers=true) l'utente $idu. Se $specificuser != '' allora si tratta di una ricerca tra il social di $idu
       if($this->esisteView(views::contentonly)){
              $str = $this->listUsersSocial($idu, $followers, $specificuser);
              $str = $this->putInContenuto("<p class=\"mobilegodown viewinmobilelink\"><a href=\"#risultaticontent\">Vai alla lista ".($followers ? "dei seguaci di $idu": "degli utenti seguiti da $idu")."</a></p>\n<div class=\"inverterdiv\">\n$str\n</div>", 
              '<a href="index.php">Home</a> > <a href="spaziopersonale.php?searchuser">Spazio personale: Cerca un utente iscritto</a> > <a href="profilo.php?utente='.$idu.'"> profilo di '.$idu.'</a> > '.($followers ? 'Lista degli utenti che seguono '.$idu:
                            'Lista degli utenti che '.$idu.' segue'));
              echo $this->giveHeader($followers ? 'Lista degli utenti che seguono '.$idu:
                            'Lista degli utenti che '.$idu.' segue') . $str.file_get_contents(views::footer);
        } 
    }
    public function ritiraPartecipazione($idev){//Metodo che permette all'utente connesso di togliere la sua partecipazione all'evento $idev
        if($this->isUserConnected()){
           if($_SESSION['user']->ritiraPartecipazione($idev))
              $this->messaggioImportante('<p><strong>La tua prenotazione all\' evento &egrave; stata annullata con successo ! Non sei pi&ugrave; un partecipante all\'evento.</strong> Ti ricordiamo che puoi gestire le tue 
                                    partecipazioni ad eventi nella voce "<em>Le tue partecipazioni ad eventi futuri</em>" presente nel tuo men&ugrave; dello spazio utente</p>'.
                                       "\n<p><a href=\"annuncio.php?view=$idev\">Torna a visitare l'annuncio</a></p>\n".
                                       "\n<p><a href=\"spaziopersonale.php?myeventi&amp;desc=1\">Torna alle tue partecipazioni ad eventi futuri</a></p>\n".
                                       "\n<p><a href=\"spaziopersonale.php?myeventi&amp;pass&amp;desc=1\">Torna alle tue partecipazioni ad eventi passati</a></p>\n");
            else $this->errore(2);
        }
        else $this->loginPage();
    }
    public function partecipa($idev){//Metodo che permette all'utente connesso di portare la sua partecipazione all'evento $idev
        if($this->isUserConnected()){
           if($_SESSION['user']->partecipa($idev)){
              $ev = new evento($idev);
              $_SESSION['user']->notificaSeguaci($_SESSION['user']->giveUserName().' parteciperà all\'evento: '.$ev->Titolo(),'annuncio.php?view='.$idev, 'F');
              $_SESSION['user']->notificaUtente($ev->giveOwner(),'Hai un nuovo partecipante al tuo annuncio di evento dal titolo: '.$ev->Titolo(), 'annuncio.php?partecipanti='.$idev, 'B');
              $this->messaggioImportante('<p><strong>La tua prenotazione all\' evento &egrave; avvenuta con successo ! Sei adesso un partecipante all\'evento.</strong> Ti ricordiamo che puoi gestire le tue 
                                    partecipazioni ad eventi nella voce "<em>Le tue partecipazioni ad eventi futuri</em>" nel tuo men&ugrave; dello spazio utente</p>'.
                                       "\n<p><a href=\"annuncio.php?view=$idev\">Torna a visitare l'annuncio</a></p>\n".
                                       "\n<p><a href=\"spaziopersonale.php?myeventi&amp;desc=1\">Torna alle tue partecipazioni ad eventi futuri</a></p>\n".
                                       "\n<p><a href=\"spaziopersonale.php?myeventi&amp;pass&amp;desc=1\">Torna alle tue partecipazioni ad eventi passati</a></p>\n");
           }
           else{
               if($_SESSION['user']->isParticipant($idev)){
                   $this->messaggioImportante('<p><strong>Sei stato gi&agrave; registrato come partecipante a questo evento</strong></p>'.
                                       "\n<p><a href=\"annuncio.php?ritira=$idev\">Puoi ritirare la tua partecipazione all'evento</a>, Oppure</p>\n".
                                       "<p><a href=\"annuncio.php?view=$idev\">Torna a visitare l'annuncio</a></p>\n".
                                       "<p><a href=\"spaziopersonale.php\">Torna nel tuo spazio personale</a></p>\n");
               }
               else $this->errore(2);
           }
        }
        else $this->loginPage();
    }
    public function modificaEvento($idev){//metodo che porta alla form di modifica annuncio evento
        if($this->isUserConnected()){
            echo $this->buildform(-2,-2,-2,true,$idev, $_SESSION['user']->giveUserName());
        }
        else $this->loginPage();
    }
    
    public function modificaFotoEvento($idev){//Metodo che porta alla pagina di gestione foto di annuncio di evento
        if($this->isUserConnected())
            echo $this->modificaFotoEventoPage($idev);
        else{
            $this->loginPage();
        }
     }
    public function aggiungiNuoveFoto($idev,$foto,$fotoName,$fotoDesc){//Metodo che aggiungi le foto $fot con nomi $fotoName e descrizioni $fotoDesc dell'annuncio $idev
        if($this->isUserConnected()){
            $errore = $_SESSION['user']->caricaNuoveFoto($idev,$foto,$fotoName,$fotoDesc);
            if(sizeof($errore) != 0){
                echo $this->segnalaErroreForm($this->modificaFotoEventoPage($idev), $errore, true);
            }
            else {
                echo $this->segnalaErroreForm($this->modificaFotoEventoPage($idev), "Nuove foto aggiunte con successo");
            }
        }
        else $this->loginPage();
    }
    public function EliminaFoto($idev,$indexfoto){//Metodo che cancella la foto $indexfoto dell'annuncio $idev
        if($this->isUserConnected()){
            if(!$_SESSION['user']->deleteFotoEv($idev,$indexfoto))
                echo $this->segnalaErroreForm($this->modificaFotoEventoPage($idev), "&Egrave; avvenuto un errore nella cancellazione",true);
            else {
             echo $this->segnalaErroreForm($this->modificaFotoEventoPage($idev), 'Foto '.($indexfoto+1).' cancellata  con successo !');
            }
        }
        else $this->loginPage();
    }
    public function eliminaEvento($idev,$conferma){//Metodo che permette di cancellare l'annuncio $idev. Se $conferma=true allora l'utente connesso ha confermato la cancellazione, altrimenti gli viene chiesto se vuole confermare
        if($this->isUserConnected()){
            if($conferma == false)
                $this->messaggioImportante('<p><strong>Attenzione : </strong> Proseguendo con l\'eliminazionione, verranno persi tutti i dati riguardanti l\' annuncio</p>'.
                                            '<p><a href="spaziopersonale.php?elim=evento&amp;ev='.$idev.'&amp;conf">Prosegui con l\'eliminazione </a> </p>'.
                                            '<p><a href="spaziopersonale.php?myannunci=0">Annulla l\'eliminazione e torna ai tuoi annunci pubblicati</a></p>'.
                                             '<p><a href="annuncio.php?view='.$idev.'">Annulla l\'eliminazione e torna a visitare l\'annuncio</a> </p>');
                                            
            else{
                if($_SESSION['user']->isAdmin()){
                $ev = new evento($idev);
                if($ev->isBlocked()){
                    $dateIniz = DateTime::createFromFormat("d/m/Y", $ev->giveLastQuarantineDate());
                    $datemod = DateTime::createFromFormat("d/m/Y", $ev->giveLastModifiedDate());
                    $end = $dateIniz->add(new DateInterval('P'.(evento::$maxDaysInQuarantine).'D'));
                    $fine = strtotime($end->format("Y").'-'.$end->format("m").'-'.$end->format("d"));
                    $today = DateTime::createFromFormat("d/m/Y", date('d\/m\/Y'));
                    $today = strtotime($today->format("Y").'-'.$today->format("m").'-'.$today->format("d"));
                        if(!DB_handler::isDateOld($datemod->format('d\/m\/Y'),$dateIniz->format('d\/m\/Y'))){
                           $this->messaggioImportante('<p><strong>Impossibile eliminare l\' annuncio. &Egrave; stata apportata una recente modifica dal pubblicatore</strong>.</p>'.
                                                      '<p>Le chiediamo di controllare se tali modifiche rispettano <a href="faq.php">le nostre regole di pubblicazione</a>.</p>'.
                                                      '<p>In caso affermativo dovr&agrave; rimuoverlo dalla quarantena, altrimenti dovr&agrave; rimetterlo in quarantena.</p>'.
                                                       '<p><a href="annuncio.php?view='.$idev.'">Visita l\'annuncio</a> oppure <a href="spazioadmin.php?listevblocked">Torna alla lista degli annunci in quarantena</a></p>');
                            return;
                        }
                        if( $fine > $today){
                           $this->messaggioImportante('<p><strong>Impossibile eliminare l\' annuncio senza aver superato i '.evento::$maxDaysInQuarantine.' giorni di tempo dati al pubblicatore per la modifica</strong>.</p>'.
                                                       '<p>Potr&agrave; eliminarlo solo a partire dal giorno '.$end->format('d\/m\/Y').' .</p>'.
                                                       '<p><a href="annuncio.php?view='.$idev.'">Visita l\'annuncio</a> oppure <a href="spazioadmin.php?listevblocked">Torna alla lista degli eventi in quarantena</a></p>');
                            return;
                        }
                }
                else {
                    $this->messaggioImportante('<p><strong>Non &egrave; possibile eliminare un annuncio senza prima metterlo in quarantena</strong></p>'.
                                                '<p><a href="spaziopersonale.php">Torna al tuo spazio di lavoro </a> </p>');
                    return;
                }
            
            }
            if($_SESSION['user']->deleteEvento($idev)){
             $this->messaggioImportante('<p>Annuncio eliminato con successo. </p><p><a href="spaziopersonale.php?myannunci=0">Torna ai tuoi annunci pubblicati</a></p>');
            }
            else $this->errore(2);
            }
        }else $this->loginPage();
    }
    public function accountSettingPage(){ //Metodo che porta alla form di modifica informazioni dell'utente connesso
         if($this->isUserConnected()){
            if($this->esisteView(views::accountSetting)){
                $inputtext = array('cognome'=>$_SESSION['user']->giveCognome(), 'nome'=>$_SESSION['user']->giveNome(), 
                                    'mail'=>$_SESSION['user']->giveMail(), 'nomeutente'=>$_SESSION['user']->giveUserName());
                $str = str_replace('<span id="de"></span>','<span id="de"><a href="spaziopersonale.php?deleteuser='.$_SESSION['user']->giveUserName()
                            .'">Elimina il tuo conto <span xml:lang="en" lang="en">My</span> eventi</a></span>',
                            file_get_contents(views::accountSetting)); 
                $str = $this->segnalaErroreForm($str,'',false,$inputtext); 
                echo $str;
                 
            }
         }
        else $this->loginPage();
    }
    public function updateProfilo($cogn, $nom, $mail,$oldPassword, $newPassword1, $newPassword2){
        //Metodo che aggiorna le informazioni dell'utente connesso. Restituisce true in caso di successo, altrimenti restituisce false e segnala l'errore all'utente
        if($this->isUserConnected()){
            $errore = '';
            if($newPassword1 != $newPassword2)
                $errore = 'La nuova password inserita e reinserita non coincidono';
            if($errore == ''){
                $check = true;
                if($newPassword1 != ''){
                    $errore = $_SESSION['user']->cambioPassword($oldPassword, $newPassword1);
                    $check = $errore == '';
                }
                if($check){    
                    $errore = $_SESSION['user']->updateInfo($cogn, $nom, $mail);
                    $check = $errore == '';
                }
                if($check){
                    unset($_SESSION['user']);
                    $this->messaggioImportante("<p><strong>Il tuo profilo &egrave; stato aggiornato con successo!</strong> Le chiediamo gentilmente di rieffettuare l'accesso per una verifica</p>\n".
                                                '<p><a href="index.php?action=login">Riacceddi al tuo spazio personale</a></p>'.
                                                '<p><a href="index.php">Torna nella pagina principale</a></p>');
                    return $check;
                }
                elseif($newPassword1 != '')
                    $_SESSION['user']->cambioPassword($newPassword1, $oldPassword);
                
            }
            $inputtext = array('cognome'=>$cogn, 'nome'=>$nom, 
                                    'mail'=>$mail);
            $str = str_replace('<span id="de"></span>','<span id="de"><a href="spaziopersonale.php?deleteuser='.$_SESSION['user']->giveUserName()
                            .'">Elimina il tuo conto <span xml:lang="en" lang="en">My</span> eventi</a></span>',
                            file_get_contents(views::accountSetting)); 
            $str = $this->segnalaErroreForm($str,"$errore",true,$inputtext); 
            echo $str;
            return false;
        }
        else $this->loginPage();
    }
    public function tipiEventiPage(){//Metodo che carica la pagina dedicata ai tipi di eventi
         if($this->esisteView(views::contentonly)){
            
            $type = DB_handler::TipiDiEventi();
            $tot = sizeof($type);
            $str = '<p><strong>'.$tot.' Tipi di eventi trovati</strong></p>';
            $list = '';
            $order = '';
            if($tot > 0){
                $a= '<p class="hidden"><a href="#resultlist">Salta l\'indice dei contenuti e vai direttamente alla lista di tutti i tipi</a></p>';
                $list = $list."\n<div id=\"resultlist\"><h2>Tipi di eventi degli annunci pubblicati</h2>\n";
            $order = "\n<div id=\"cataloger\"><h2>Indice dei contenuti</h2>\n<ul>\n";
            $str = $str.$a;
            $letter = '';
            $db = new DB_handler;
            
            if($letter != strtoupper(substr($type[0],0,1))){
                    $letter = strtoupper(substr($type[0],0,1));
                    $frase = "Tipi di eventi che iniziano con la lettera $letter";
                    $order = $order.'<li><a class="mobilegodown" href="#'.$letter.'"> '.$frase.'</a></li>';
                    $list = $list.'<h3>'.$frase.' :</h3><ul id = "'.$letter.'">'."\n";
                }
            for($i = 0; $i < $tot; ++$i){
                if($letter != strtoupper(substr($type[$i],0,1))){
                    $letter = strtoupper(substr($type[$i],0,1));
                    $frase = "Tipi di eventi che iniziano con la lettera $letter";
                    $order = $order.'<li><a class="mobilegodown" href="#'.$letter.'"> '.$frase.'</a></li>';
                    $list = $list.'</ul><h3>'.$frase.' :</h3><ul id = "'.$letter.'">'."\n";
                }
                $id =$db->giveIdTypeEvent($type[$i]);
                $list = $list."<li><a href=\"archivio.php?search&amp;type=$id\">$type[$i] : ".DB_handler::TotTypeEvents($type[$i]).' annunci</a></li>'."\n";
            }
            $order = $order."\n</ul>\n</div>\n";   
            $list = $list."\n</ul></div>\n";
            }
            $str = $str.$order.$list;
            $str = $this->putInContenuto($str,'<a href="index.php">Home</a> > Tipi di eventi');
            $head = $this->giveHeader('Tipi di eventi degli annunci pubblicati',1);
            echo $head . $str.file_get_contents(views::footer);
        }
    }
    public function categEventiPage(){//Metodo che carica la pagina dedicata alle categorie di annunci
        if($this->esisteView(views::contentonly)){
            
            $categ = DB_handler::CategorieEventi();
            $tot = sizeof($categ);
            $str = '<p><strong>'.$tot.' Categorie di eventi trovate</strong></p>';
            $list = '';
            $order = '';
            if($tot > 0){
                $a= '<p class="hidden"><a href="#resultlist">Salta l\'indice dei contenuti e vai direttamente alla lista di tutte le categorie</a></p>';
                $list = $list."\n<div id=\"resultlist\"><h2>Categorie di eventi degli annunci pubblicati</h2>\n";
                $str = $str.$a;
            $order = "\n<div id=\"cataloger\"><h2>Indice dei contenuti</h2>\n<ul>\n";
            $letter = '';
            $db = new DB_handler;
            if($letter != strtoupper(substr($categ[0],0,1))){
                    $letter = strtoupper(substr($categ[0],0,1));
                    $frase = "Nomi di categorie che iniziano con la lettera $letter";
                    $order = $order.'<li><a class="mobilegodown" href="#'.$letter.'"> '.$frase.'</a></li>';
                    $list = $list.'<h3>'.$frase.' :</h3><ul id = "'.$letter.'">'."\n";
                }
            for($i = 0; $i < $tot; ++$i){
                if($letter != strtoupper(substr($categ[$i],0,1))){
                    $letter = strtoupper(substr($categ[$i],0,1));
                    $frase = "Nomi di categorie che iniziano con la lettera $letter";
                    $order = $order.'<li><a class="mobilegodown" href="#'.$letter.'">'.$frase.'</a></li>';
                    $list = $list.'</ul><h3>'.$frase.' :</h3><ul id = "'.$letter.'">'."\n";
                }
                $id =$db->giveIdCatEvent($categ[$i]);
                $list = $list."<li><a href=\"archivio.php?search&amp;categ=$id\">$categ[$i] : ".DB_handler::TotEventsCategoria($categ[$i]).' annunci</a></li>'."\n";
            }
            $order = $order."\n</ul>\n</div>\n";   
            $list = $list."\n</ul></div>\n";
            }
            $str = $str.$order.$list;
            
            $str = $this->putInContenuto($str,'<a href="index.php">Home</a> > Categorie di eventi');
            $head = $this->giveHeader('Categorie di eventi degli annunci pubblicati',2);
            echo $head . $str.file_get_contents(views::footer);
        }
    }
    public function searchResult($type ='', $categ = '', $keyword = '', $city = '', $publisher = '', $page=1,
                                $fromgiorno='',$frommese='',$fromanno='',$togiorno='',$tomese='',$toanno='',
                                $fromgiornopub='',$frommesepub='',$fromannopub='',$togiornopub='',$tomesepub='',$toannopub=''){
        //Metodo che mostra i risultati della ricerca di annunci eseguita da un utente 
        if($this->esisteView(views::searchpage)){
            $fromgiorno=substr($fromgiorno,0,2); $frommese=substr($frommese,0,2); $fromanno=substr($fromanno,0,4);
            $togiorno=substr($togiorno,0,2);$tomese=substr($tomese,0,2);$toanno=substr($toanno,0,4);
            $fromgiornopub=substr($fromgiornopub,0,2); $frommesepub=substr($frommesepub,0,2); $fromannopub=substr($fromannopub,0,4);
            $togiornopub=substr($togiornopub,0,2);$tomesepub=substr($tomesepub,0,2);$toannopub=substr($toannopub,0,4);
            
            $fromgiorno=strlen($fromgiorno) == 1 ? '0'.$fromgiorno:$fromgiorno; $frommese=strlen($frommese) == 1 ? '0'.$frommese:$frommese;
            $togiorno=strlen($togiorno) == 1 ? '0'.$togiorno:$togiorno; $tomese=strlen($tomese) == 1 ? '0'.$tomese:$tomese;
            $fromgiornopub=strlen($fromgiornopub) == 1 ? '0'.$fromgiornopub :$fromgiornopub; $frommesepub=strlen($frommesepub) == 1 ? '0'.$frommesepub:$frommesepub;
            $togiorno=strlen($togiornopub) == 1 ? '0'.$togiornopub :$togiornopub; $tomesepub=strlen($tomesepub) == 1 ? '0'.$tomesepub:$tomesepub;
            
            
            $datafrom = ($fromgiorno != '' ? $fromgiorno : '01').'/'.($frommese != '' ? $frommese : '01').'/'.($fromanno != '' ? $fromanno : '0001');
            $dataTO = ($togiorno != '' ? $togiorno : '31').'/'.($tomese != '' ? $tomese : '12').'/'.($toanno != '' ? $toanno : '9999');
            $datafrompub = ($fromgiornopub != '' ? $fromgiornopub : '01').'/'.($frommesepub != '' ? $frommesepub : '01').'/'.($fromannopub != '' ? $fromannopub : '0001');
            $dataTOpub = ($togiornopub != '' ? $togiornopub : '31').'/'.($tomesepub != '' ? $tomesepub : '12').'/'.($toannopub != '' ? $toannopub : '9999');
            
            $eventi = evento::specificEvents($type, $categ, $keyword, $city, $publisher, $datafrom, $dataTO,$datafrompub,$dataTOpub, ($this->isUserConnected() && $_SESSION['user']->giveUserName() === $publisher));
            $tot = sizeof($eventi);
            $links = ($tot > 0 ? '<p class="mobilegodown viewinmobilelink"><a href="#foundannunciolist">Vai agli annunci trovati</a></p>' : '')."\n".'<p class="mobilegodown viewinmobilelink"><a href="#searchbox">Fai una nuova ricerca di annunci</a></p>'."\n".($tot > 0 ? '<p class="mobilegodown viewinmobilelink"><a href="#gotopagina">Salta questa pagina dei risultati e vai ad una pagina specifica</a></p>'."\n": '');
            
            $str = '<p><strong> Sono stati trovati '.$tot.' annunci di eventi.</strong></p>'.$links.'<p> <a href="#riassuntoricerca" class="mobilegodown">Informazioni sugli annunci che sono stati trovati. <span class="hidden">&Egrave; un riassunto della ricerca che hai eseguito per arrivare a questi annunci trovati.</span></a></p>'
                    ."\n";
            $totpage = floor($tot / views::maxRecordPerPage);
            if($tot % views::maxRecordPerPage || $totpage == 0)
                ++$totpage;
            $str = $str.($tot > 0 ? "<p class=\"paginacorrente\"><strong>Pagina $page su $totpage</strong></p>\n": '');
            $list = $tot > 0 ? "<ul id=\"foundannunciolist\">\n" : '';
            $count = 0;
            $vaia = $tot > 0 ? $this->vaiaPaginaform('archivio.php',$totpage,$page) : '';
            $ar = $this->buildhiddensearchInput($type, $categ, $keyword, $city, $publisher, $fromgiorno,
                                                                                                $frommese,$fromanno,$togiorno,$tomese,$toanno,
                                                                                                $fromgiornopub,$frommesepub,$fromannopub,$togiornopub,$tomesepub,$toannopub);
            $vaia = str_replace('<span id="hiddeninput"></span>',$ar[0],$vaia);
            $vaia = str_replace('&undefined','&amp;'.$ar[1],$vaia);
            for($i = ($page-1)*views::maxRecordPerPage; $i <$tot && $count < views::maxRecordPerPage; ++$i){
                $index = $i+1;
                if(!$eventi[$i]->isBlocked())
                $list = $list.'<li><span class="linker"><a href="annuncio.php?view='.$eventi[$i]->giveId().'">Annuncio '.($index).' di evento trovato pubblicato da '.$eventi[$i]->giveOwner().' che si terr&agrave; il giorno '.$eventi[$i]->dataEvento().' : '. $eventi[$i]->giveTipo().' - '.$eventi[$i]->Titolo().'</a></span>'.
                              "\n".$eventi[$i]->givebreveDesc(true)."\n"."<p><a href=\"annuncio.php?view=".$eventi[$i]->giveId()."\">Leggi tutto l'annuncio $index trovato</a></p></li>\n";
                ++$count;
            }
            $list = $list.( $tot > 0 ? "</ul>\n" : '' );
            $db = new DB_handler;
            $rias = '<div id="riassuntoricerca"><h4>Informazioni sulla ricerca eseguita:</h4> <p>Hai cercato :</p>'."<ul>\n".
                   ($keyword != '' ? '<li>Annunci di eventi con contesti simili alla parola <em>"'.$keyword."\"</em> </li>\n" : '').
                  ($publisher != '' ? '<li>Autore dell\'annuncio il cui nome utente, cognome o nome contiene la parola <em>"'.$publisher."\"</em> </li>\n" : '')."\n".
                   '<li>Annunci di eventi pubblicati '.($datafrompub =='01/01/0001' && $dataTO == '31/12/9999' ? 'in qualsiasi intervallo di tempo': ($datafrompub =='01/01/0001' ? 'da un giorno qualsiasi ':"dal giorno $datafrompub " ).
                  ($dataTOpub == '31/12/9999' ? 'ad un giorno qualsiasi':"al giorno $dataTOpub"))."</li>\n".
                  '<li>'.($categ != -1 ? ' nella categoria '.$db->giveNomeCatEvent($categ) : ' In tutte le categorie ').'</li>'."\n".
                  '<li>Eventi'.($type != -1 ? ' di tipo '.$db->giveNomeTypeEvent($type) : ' di Tutti i tipi').'</li>'."\n".
                  '<li>'.($city != '' ? ' Eventi nella citt&agrave; di '.$city : ' Eventi in qualsiasi citt&agrave;').'</li>'."\n".
                  '<li>Eventi che si svolgeranno, oppure che si sono svolti, '.($datafrom =='01/01/0001' && $dataTO == '31/12/9999' ? 'in qualsiasi intervallo di tempo': ($datafrom =='01/01/0001' ? 'da un giorno qualsiasi ':"dal giorno $datafrom " ).
                  ($dataTO == '31/12/9999' ? 'ad un giorno qualsiasi':"al giorno $dataTO"))."</li>\n</ul> <p><a href =\"#results\" class=\"mobilegoup\">Torna agli annunci trovati</a></p>\n<p class=\"mobilegodown viewinmobilelink\"><a href =\"#searchbox\">Fai una nuova ricerca avanzata</a></p></div>\n";
            $str = $str.$list.($tot > 0 ? "<p class=\"paginacorrente\"><strong><span class=\"hidden\">Fine </span>Pagina $page su $totpage</strong></p>\n<p><a class=\"hidden\" href=\"#searchbox\"> salta tutte le pagine e fai una nuova ricerca avanzata</a></p>\n": '').$vaia.$rias;
            $risultato = str_replace('<span id="formSearch"></span>', $this->buildsearchBox($type,$categ,$keyword, $city, $publisher, $fromgiorno,
                                                                                            $frommese,$fromanno,$togiorno,$tomese,$toanno,
                                                                                            $fromgiornopub,$frommesepub,$fromannopub,$togiornopub,$tomesepub,$toannopub),
                                 file_get_contents(views::searchpage));
            $risultato = str_replace('<span id="contentLoad"></span>', $str,$risultato);
            $risultato = $this->putInContenuto($risultato, '<a href="index.php">Home</a> > Cerca un annuncio di evento: pagina '.$page." su $totpage dei risultati");
            $head = str_replace("id=\"search\"","id=\"search\" value =\"$keyword\"", $this->giveHeader($tot.' Annunci di eventi trovati, pagina '.$page.' su '.$totpage.' dei risultati'));
            echo $head.$risultato.file_get_contents(views::footer);
        }
    }
    public function faq($id=''){
        if($this->esisteView(views::FAQ)){
           header('Location: '.views::FAQ . ($id !='' ? "#$id":''));
        }
    }
    public function eventsInCityPage(){//Metodo che carica la pagina dedicata agli annunci in città
        $str = '<div id="citysearch"><form action="archivio.php?search" method="get">'."\n".
           '<fieldset>'."\n".'<legend>Trova gli annunci di eventi nella tua citt&agrave;</legend>'."\n".
           '<label for="city">Inserisci il nome della tua citt&agrave;,  al massimo 30 caratteri, <em> Ad Esempio: Treviso</em></label>'."\n".
            '<input type="text" name="city" maxlength="30" id="city"/>'."\n".
            '<input type="submit" value="cerca"/>'."\n".
            '</fieldset>'."\n</form>\n</div>";
        $str = $this->putInContenuto($str,'<a href="index.php">Home</a> > Eventi nella tua citt&agrave;');
    
        $head = $this->giveHeader('Scopri gli eventi nella tua citt&agrave;',3);
        echo $head.$str.file_get_contents(views::footer);
    }
    private function searchUserForm($desc, $admin=false,$userType = -1, $keyword = ''){//Metodo che carica, in base all'utente(admin o no), la form di ricerca di un utente iscritto con valori di default $userType, $keyword e $desc
        $str = "<form action=\"spaziopersonale.php\" method=\"get\">\n";
        $select = new select('typeusers','typeusers');
        $select->addOption('Tutti gli utenti iscritti',-1);
        $select->addOption('Solo gli utenti premium',0);
        $select->addOption('Solo gli utenti non premium',1);
        if(!$admin){
            $select->addOption('Gli utenti che ti seguono',2);
            $select->addOption('Gli utenti che segui',3);
        }
        else{
            $select->addOption('Tutti gli utenti bloccati',2);
            $select->addOption('Gli utenti che chiedono di essere premium',3);
        }
        $select->setValueFromIndex($userType);
        $inputsearch = new input('searchuser','searchuser', $keyword);
        $inputsearch->setMaxlength(views::maxLengthSearchinput);
        $s = $desc ?'<input type ="hidden" name="desc"/>' : '';
        $submit = $s."\n".'<input type ="submit" value="cerca"/>'; 
        $formTags = array( $select, $inputsearch);
        $label = array('Seleziona il tipo di utente che cerchi. Modifica la voce corrente scegliendo una delle opzioni','Inserisci il nome utente, il cognome o il nome dell\'utente che cerchi:');
        $str = "\n<div id=\"searchUserform\">\n".$str.$this->stampaFieldsetFormTagswithLabels('Cerca un utente iscritto',$formTags, $label, $submit)."\n</form></div>\n";
        return $str;
    }
    
    public function utentiIscritti($desc,$userType = 0, $keyword = '', $page = 1){//Metodo che mostra i risultati della ricerca, ordinati in  modo $desc, di utenti iscritti di tipo $userType, con parola chiave $keyword
        if($this->isUserConnected()){
                    $admin = $_SESSION['user']->isAdmin();
                    $utenti = $_SESSION['user']->utentiIscritti($desc,$userType, $keyword);
                    $str = '';
                    $totRecords = sizeof($utenti);
                    $s = $totRecords.' '.$_SESSION['user']->catdiutenti($userType);
                    $str = $str."\n <div id = \"risultaticontent\">\n<strong>Sono stati trovati $s";
                    if($keyword != '')
                        $str=$str."\n con nome utente, o cognome, o nome contenente la parola \"<em>$keyword</em>\"";
                    $str = $str."\n</strong>\n";
                    $totPage = floor($totRecords / views::maxRecordPerPage);
                    if($totRecords % views::maxRecordPerPage != 0 || $totPage == 0)
                    ++$totPage;
                    $p1 = $totRecords > 0 ? "\n<p class=\"paginacorrente\">Pagina $page su $totPage</p>\n" : '';
                    $p2 = $totRecords > 0 ? "\n<p class=\"paginacorrente\"><span class=\"\">Fine </span>Pagina $page su $totPage</p>\n" : '';
                    $links = ( $totRecords > 0 ? '<p class="mobilegodown viewinmobilelink"><a accesskey="j" href="#lista_SUresults">Vai alla lista degli utenti trovati</a></p>'."\n" : '');
                    $head = $totRecords > 0 ? $this->vaiaPaginaform('spaziopersonale.php',$totPage,$page) : '';
                            $head = str_replace('<span id="hiddeninput"></span>', '<input type="hidden" name="typeusers" value="'.$userType.'"/>'."\n".
                            '<input type="hidden" name="searchuser" value="'.$keyword.'"/>'."\n".($desc===true ? '<input type="hidden" name="searchuser"/>': ''), $head);
                            $head = str_replace('&undefined', "&amp;searchuser=$keyword&typeusers=$userType".($desc===true ? '&desc': ''), $head);
                    $s = $s." trovati, pagina $page su $totPage dei risultati";
                    
                    if(!$admin)
                    $links = $links.'<p class="mobilegodown viewinmobilelink">';
                    else
                        $links = $links.'<p class="mobilegoup viewinmobilelink">';
                    $links = $links.'<a accesskey="k" href="#searchUserform">Fai una nuova ricerca di utente</a></p>'."\n".
                         '<p class="mobilegodown viewinmobilelink"><a accesskey="l" href="#gotopagina">Vai ad una pagina specifica della lista dei tuoi annunci pubblicati</a></p>'."\n";
                    $breadcrumb = '';
                    if($admin){
                        if($this->esisteView(views::spaziopersonaleAdmin)){
                           
                        $str = $str.$links.$p1; 
                        if($totRecords > 0){
                            $count = 0;
                                $str = $str."<ol id=\"lista_SUresults\">\n";
                            for($i = ($page - 1)*views::maxRecordPerPage; $i < $totRecords && $count < views::maxRecordPerPage; ++$i){
                                $blocked = utente::isUtenteBlocked($utenti[$i]) ? '<strong>(utente bloccato)</strong> ' : '';
                                $rendipremium = utente::isUtentePremium($utenti[$i]) ? '[<a href="spazioadmin.php?makepremium='.$utenti[$i].'">togli a '.$utenti[$i].' i privileggi <span xml:lang="en" lang="en">premium</span></a>]' : 
                                        '[<a href="spazioadmin.php?makepremium='.$utenti[$i].'&conf">rendi '.$utenti[$i].' utente <span xml:lang="en" lang="en">premium</span></a>]';
                                $str = $str. '<li><p><a href="profilo.php?utente='.$utenti[$i]."\">Utente ".($i+1)." trovato $blocked : $utenti[$i]". ' - Utente '.(utente::isUtentePremium($utenti[$i]) ==true ?
                                '<span xml:lang="en" lang="en">premium</span>':'generico').
                                '</a></p><p class="linkGest">'.( $blocked =='' ? ' [<a href="spazioadmin.php?bloccauser='.$utenti[$i].'&amp;block">blocca '.$utenti[$i].'</a>]' : ' [<a href="spazioadmin.php?bloccauser='.$utenti[$i].'">sblocca '.$utenti[$i].'</a>]').
                                "$rendipremium</p></li>\n";
                                ++$count;
                            }
                            $str = $str."</ol>\n$p2 <p class=\"mobilegoup viewinmobilelink\"><a href=\"#searchUserform\">Salta tutte le altre pagine e fai una nuova ricerca utente</a></p>\n$head";
                        }
                            $str = $str."</div>\n".$this->searchUserForm($desc,$admin, $userType, $keyword)."\n";
                            $breadcrumb = '<a href="index.php">Home</a> > Spazio amministratore: Gestione utenti - Lista in ordine alfabetico '.($desc ? 'decrescente': 'crescente').' degli utenti iscritti trovati';
                            $str = $this->buildUtenteNav($str, !$desc ? 1 : 2);
                        }
                    }
                    else{
                        if($this->esisteView(views::contentonly)){
                             $str = $str.$links.$p1; 
                            if($totRecords > 0){
                            $count = 0;
                            $str = $str."<ul id=\"lista_SUresults\">\n";
                            for($i = ($page - 1)*views::maxRecordPerPage; $i < $totRecords && $count < views::maxRecordPerPage; ++$i){
                                $followers = $_SESSION['user']->isSeguace($utenti[$i]) ? ' <span class="linkGest">[<a href="spaziopersonale.php?segui='.$utenti[$i].'">smetti di seguire '.$utenti[$i].'</a>]</span>' : 
                                            ($_SESSION['user']->tiSegue($utenti[$i]) ? " ( $utenti[$i] ti segue )" : ($_SESSION['user']->giveUserName() != $utenti[$i] ?
                                            ' [<a href="spaziopersonale.php?segui='.$utenti[$i].'&amp;conf">segui '.$utenti[$i].'</a>]' : ' ( questo sei tu )'));
                                $str = $str. '<li><a href="profilo.php?utente='.$utenti[$i].'">Utente '.($i+1).' trovato : '.$utenti[$i]. ' - Utente '.(utente::isUtentePremium($utenti[$i])?'<span xml:lang="en" lang="en">premium</span> ':'generico ').'</a> '.$followers."</li>\n";
                                ++$count;
                            }
                            
                            $str = $str."</ul>\n$p2 <p class=\"mobilegodown viewinmobilelink\"><a href=\"#searchUserform\">Salta tutte le altre pagine e fai una nuova ricerca utente</a></p>$head";
                            }
                            $str = $str."</div>\n<p class=\"mobilegoup viewinmobilelink\"><a href=\"#risultaticontent\">Torna all'inizio della lista degli utenti trovati</a></p>\n".
                            $this->searchUserForm($desc,$admin, $userType, $keyword)."\n";
                            $breadcrumb ='<a href="index.php">Home</a> >  <a href="spaziopersonale.php?mysocial">Il tuo spazio personale: Cerca un utente iscritto </a> > Risultati della ricerca';
                        }
                        
                    }
                    $str = $this->putInContenuto($str,$breadcrumb);
                    echo (!$admin ? $this->giveHeader("$s trovati") :$this->giveHeader("$s trovati",6)).
                    $str.file_get_contents(views::footer);
        }
        else $this->loginPage();
    }
    public function eventiSegnalati(){//Metodo che mostra gli annunci di eventi segnalati dagli utenti
        if($this->isUserConnected()){
            if($_SESSION['user']->isAdmin()){
                if($this->esisteView(views::spaziopersonaleAdmin)){
                    $tot = DB_handler::totSegna();
                    $str = "\n<div id=\"risultaticontent\">\n<strong>Sono stati segnalati $tot annunci di eventi</strong>\n";
                    $eventi = evento::eventiSegnalati();
                    if($tot > 0){
                    $str =$str."<p class=\"mobilegoup viewinmobilelink\"><a href=\"#navUtente\">Salta l'elenco degli annunci segnalati e Torna nel men&ugrave; del tuo spazio amministratore</a></p>\n<ul>\n";
                    $index = 1;
                    foreach($eventi as $ev){
                        $str = $str. '<li><a href="spazioadmin.php?evsegnalato='.$ev->giveId().'">Annuncio '.$index.' segnalato ('.DB_handler::totSegna($ev->giveId()).
                                    ' segnalazioni): '.$ev->giveTipo().' - '.$ev->Titolo()."</a></li>\n";
                                    ++$index;
                    }
                    $str = $str."</ul>";
                    }
                    $str = $str."\n<p class=\"mobilegoup viewinmobilelink\"><a href=\"#navUtente\">Torna nel men&ugrave; del tuo spazio amministratore</a></p></div>\n";
                    $str = $this->buildUtenteNav($str, 0);
                    if($str !=''){
                        $str = $this->putInContenuto($str,'<a href="index.php">Home</a> > Spazio amministratore: Annunci segnalati dagli utenti');
                        echo $this->giveHeader("Annunci segnalati dagli utenti ($tot annunci)",6).$str.file_get_contents(views::footer);
                    }
                }
           }
           else $this->errore(1);
        }
        else $this->loginPage();
    }
    public function eventoSegnalato($idev){ //Metodo che mostra i dettagli delle segnalazioni avvenute sull'annuncio $idev
        if($this->isUserConnected()){
            if($_SESSION['user']->isAdmin()){
                if($this->esisteView(views::spaziopersonaleAdmin)){
                    $ev = new evento($idev);
                    $str = '<p>L\'annuncio &egrave; stato <a href="profilo.php?utente='.$ev->giveOwner().
                        '"> pubblicato dall\' utente '.$ev->giveOwner().'(visita il suo profilo)</a>,  il giorno '.$ev->dataPub()."</p>\n";
                    $tot = DB_handler::totSegna($ev->giveId());
                    $str = $str.'<p>Sono state ricevute '.$tot." segnalazioni sull'annuncio</p>\n";
                    if($tot > 0){
                        $str = $str."\n<ul>\n";
                        $motivi = DB_handler::motividelsegnalato($ev->giveId());
                        $bmotivi = array_fill(0, sizeof($motivi), FALSE);
                        foreach(DB_handler::motivisegnalato as $mot){
                            $count = 0;$x =0;
                            foreach($motivi as $m){
                                if($mot === $m){
                                    $count = DB_handler::totUgualiSegna($m, $idev); $bmotivi[$x] = TRUE;
                                }
                                ++$x;
                            }
                            if($count > 0)
                                $str = $str. '<li>'.$count.' segnalazioni con motivo : '.$mot."</li>\n";
                        }
                        $other = ''; $tot = sizeof($bmotivi);
                        $count = 0;
                        for($i = 0; $i < $tot; ++$i){
                            if($bmotivi[$i] == false){
                                if($other == '')
                                    $other = "\n<li>".($tot - count(array_filter($bmotivi)))." altri motivi di segnalazione che sono: \n<ul>\n";
                                $other = $other. "<li>$motivi[$i]</li>\n";
                            }
                        }
                        if($other != '')
                            $other = $other."</ul>\n</li>\n";
                        $str = $str.$other."</ul>\n";
                    }
                    $str = $str.'<p>[<a href="annuncio.php?view='.$ev->giveId().'">Visita l\'annuncio</a>]  [<a href="spazioadmin.php?quarantine='.
                    $ev->giveId().'&amp;block">Metti subito l\'annuncio in quarantena</a>]   [<a href="spazioadmin.php?rmsegnalato='.$ev->giveId().
                    '">Rimuovi l\'annuncio dai segnalati</a>]'.
                    (!utente::isUtenteBlocked($ev->giveOwner())?'[<a href="spazioadmin.php?bloccauser='.$ev->giveOwner().
                    '&amp;block">blocca chi ha pubblicato l\'annuncio</a>]' : '[<a href="spazioadmin.php?bloccauser='.$ev->giveOwner().
                    '">sblocca chi ha pubblicato l\'annuncio</a>]' ).'</p>'."\n<p class=\"mobilegoup viewinmobilelink\"><a href=\"#navUtente\">Torna nel men&ugrave; del tuo spazio amministratore</a></p>";
                    $str = $this->buildUtenteNav($str);
                    if($str != ''){
                        $str = $this->putInContenuto($str,'<a href="index.php">Home</a> > <a href="spazioadmin.php?listevsegnalati">Spazio amministratore : Annunci segnalati dagli utenti</a> > Dettagli segnalazioni sull\'annuncio: '.$ev->Titolo());
                        echo $this->giveHeader('Dettagli delle segnalazioni sull\'annuncio dal titolo '.$ev->Titolo(),6).$str.file_get_contents(views::footer);
                    }
                }
            }else $this->errore(1);
        }
        else $this->loginPage();
    }
    public function profiloUtente($idu){//Metodo che mostra il profilo dell'utente $idu se esiste, altrimenti segnala al richiedente che l'utente è stato bloccato, oppure si è disiscritto al sito
    $checkpremium = utente::isUtentePremium($idu);
        if($this->esisteView(views::contentonly)){
            $str ='';
            if(!utente::isUtenteBlocked($idu) || ($this->isUserConnected() && $_SESSION['user']->isAdmin())){  
                $str = utente::profiloUtente($idu);
            }
            else{ $this->messaggioImportante('<p><strong>Questo profilo &egrave; stato bloccato per violazione delle nostre <a href="index.php?action=faq">regole utenti</a></strong> </p>'.
                                          '<p><a href="index.php">Torna nella pagina principale</a></p>');
                                          return;
            }
            if($str == ''){
                $str = '<p><strong>Non &egrave; stato possibile trovare l\'utene da lei selezionato. L\'utente potrebbe essersi cancellato da questo sito.</strong></p>';
            }
            else{ 
                $link = utente::isUtentePremium($idu) ? '<p class="mobilegodown viewinmobilelink"><a href="#published">Vai agli annunci pubblicati da '.$idu.'</a></p>'."\n": '';
                $link = $link .($this->isUserConnected() && ($_SESSION['user']->giveUserName() == $idu ||$_SESSION['user']->isAdmin() )? 
                '<p class="mobilegodown viewinmobilelink"><a href="#gestUt">Vai a opzioni profilo</a></p>'."\n": '');
                $str = str_replace("<span id=\"navigazione\"></span>", '<p class="mobilegodown viewinmobilelink"><a href="#infoUt">Vai a informazioni su '.$idu.'</a></p>'."\n".
                '<p class="mobilegodown viewinmobilelink"><a href="#social">Vai al sociale di '.$idu.'</a></p>'."\n $link", $str);
                if(utente::isUtentePremium($idu))
                    $str = str_replace("<span id=\"spiega\"></span>", '<a href="index.php?action=faq&amp;id=whatisit"> Scopri che cos\'&egrave; un utente <span xml:lang="en" lang="en">premium</span>. </a>', $str);
                else $str = str_replace("<span id=\"spiega\"></span>", '', $str);
                if(!$this->isUserConnected()){
                   $str = str_replace('<span id="gestUt"></span>','', $str); 
                }
                if($this->isUserConnected() && $_SESSION['user']->giveUserName() == $idu){
                    $str = str_replace('<span id="gestUt"></span>','<div id="gestUt"><h3>Opzioni profilo</h3>'."\n".'<a href="spaziopersonale.php?action=accountsetting">Modifica il tuo profilo</a></div>', $str);
                    $str = str_replace('<span id="first"></span>', "<div id=\"messaggio\"><p>Ecco come si presenta il tuo profilo agli altri utenti.</p></div>\n", $str);
                }
                
                $utenti1 = utente::listSocial($idu, TRUE);
                $utenti2 = utente::listSocial($idu, FALSE);
                $s = '<h3>Sociale dell\'utente '.$idu." :</h3>\n".
                     '<p><a href="profilo.php?social='.$idu.'">Lista di utenti seguiti da '.$idu.' : '.sizeof($utenti2)." utenti</a></p>\n".
                     '<p><a href="profilo.php?social='.$idu.'&amp;followers">Lista di utenti che seguono '.$idu.' : '.sizeof($utenti1)." utenti</a></p>\n";
                if($this->isUserConnected() && $_SESSION['user']->isAdmin()){
                    $str = str_replace('<span id="gestUt"></span>', '<div id="gestUt"><h3>Opzioni profilo</h3>'."\n".
                                       (!utente::isUtenteBlocked($idu) ? '[<a href="spazioadmin.php?bloccauser='.$idu.'&amp;block">blocca '.$idu.'</a>]':
                                       '[<a href="spazioadmin.php?bloccauser='.$idu.'">Sblocca '.$idu.'</a>]').'</div>',$str);
                }
                else{
                    $str = str_replace('<span id="gestUt"></span>','', $str); 
                    if($this->isUserConnected() && $_SESSION['user']->isSeguace($idu))
                        $s = $s.'<p><span id="seguiut"><a href="spaziopersonale.php?segui='.$idu.'"> smetti di seguire '.$idu."</a></span></p>\n";
                    elseif(!$this->isUserConnected() || $_SESSION['user']->giveUserName() != $idu)
                        $s = $s.'<p><span id="seguiut"><a href="spaziopersonale.php?segui='.$idu.'&amp;conf"> segui '.$idu."</a></span></p>\n";
                }
                $str = str_replace('<div id="social"></div>', '<div id="social">'.$s.'</div>',$str);
                if($checkpremium){
                    $eventi = utente::EventiPubblicati($idu,'', $this->isUserConnected() && $idu == $_SESSION['user']->giveUserName());
                    $tot = sizeof($eventi);
                    $str = $str."<div id=\"published\">\n<h3>Annunci pubblicati dall'utente $idu - $tot annunci:</h3>\n";
                    if($tot > 0){
                        $str = $str."<a class=\"hidden\" href=\"<linker>\">Salta gli annunci pubblicati da $idu</a>\n<ul id = \"foundannunciolist\">\n";
                        for($i = 0; $i < $tot && $i < views::maxRecordPerPage; ++$i)
                            $str = $str.'<li><a href="annuncio.php?view='.$eventi[$i]->giveId().'">Annuncio '.($i+1)." su $tot pubblicato da $idu: ".$eventi[$i]->giveTipo().' - '.$eventi[$i]->Titolo()."</a></li>\n";
                        $str = $str."</ul>\n";
                        if($tot > views::maxRecordPerPage){
                            $str = $str.'<a id="linkToAll" href="archivio.php?publisher='.$idu."\">Scopri tutti gli annunci pubblicati da $idu</a>\n";
                            $str = str_replace('<linker>','#linkToAll', $str);
                        }
                    }
                    $str = $str."</div>\n";
                }
            }
                $str = $this->putInContenuto($str,'<a href="index.php">Home</a> > <a href="spaziopersonale.php?searchuser">Spazio personale: Cerca un utente iscritto</a> > profilo di '.$idu);
                echo $this->giveHeader('profilo dell\'utente '.($checkpremium ? 'premium ': 'iscritto ').$idu) .$str.file_get_contents(views::footer);
        }
    }
    public function myprofile(){//Metodo che permette all'utente connesso di visualizzare il suo profilo 
        if($this->isUserConnected()){
            $this->profiloUtente($_SESSION['user']->giveUserName());
        }
        else $this->loginPage();
    }
    public function mettirimuoviInQuanrantena($idev, $value, $motivo=''){//Metodo che permette di bloccare($value=true) con moivi $motivo, o sbloccare($value=false ) l'anuncio $idev
       if($this->isUserConnected()){
           if($value == TRUE && $motivo == ''){
                $this->messaggioImportante($this->segnalazioneEvFormBuild('spazioadmin.php',$idev));
           }
           elseif($_SESSION['user']->isAdmin()){ 
                if($_SESSION['user']->setQuanrantenaEv($idev, $value,$motivo)){
                    $ev = new evento($idev);
                    $messaggio = $value ? 'Il tuo annuncio di '.$ev->giveTipo().' pubblicato con il titolo: '.
                                 $ev->Titolo().'. Risulta bloccato  per il seguente motivo: '.$motivo:
                                 'Il tuo annuncio di '.$ev->giveTipo().' pubblicato con il titolo: '.
                                 $ev->Titolo().'. Precedentemente bloccato, risulta ora sbloccato e accessibile agli utenti';
                    $_SESSION['user']->notificaUtente($ev->giveOwner(),$messaggio,'annuncio.php?view='.$idev,'C');
                    $this->messaggioImportante('<p><strong>Operazione avvenuta con successo!</strong> <a href="spazioadmin.php?listevblocked">Torna alla lista degli eventi in quarantena</a></p>');
                }                    
            }
            else $this->errore(1);
        }
        else $this->loginPage(); 
    }
    public function segnalaAnnuncio($idev,$motivo=''){//Metodo che permette di segnalare l'annuncio $idev con motivi $motivo
        if($motivo == ''){
            $this->messaggioImportante($this->segnalazioneEvFormBuild('annuncio.php',$idev));
           }
        elseif(DB_handler::segnalaev($idev, $motivo) || DB_handler::totSegna($idev) >= DB_handler::maxSegnalazioni ){
            $this->messaggioImportante('<p>Grazie per averci inviato la tua segnalazione, provederemo a verificarla nei prossimi giorni.</p>'.
                                       '<p><a href="annuncio.php?view='.$idev.'">Torna a visitare l\'annuncio</a></p>'.
                                       '<p><a href="index.php">Torna nella pagina principale</a></p>');  
           }
    }
    
    public function messaggioImportante($messagioinHtml){//Metodo che comunica un messaggio importante in forma HTML, all'utente. potrebbe per esempio essere un messaggio di successo o insuccesso di un'operazione fatta
        if($this->esisteView(views::headernonavfragment)){
            $messagioinHtml = $this->putInContenuto('<div id="messaggioImp">'.$messagioinHtml.'</div>','');
            echo file_get_contents(views::headernonavfragment) .$messagioinHtml.file_get_contents(views::footer);
        }
    }
    public function eventiBloccati($modified, $keyword, $page = 1){// Metodo che elenca in formato HTML, gli annunci bloccati modificati recentemente se $modified=0, o non modificati recentemente $modificati=1, altrimenti tutti. Con parola chiave $keyword
        if($this->isUserConnected()){
            if($_SESSION['user']->isAdmin()){
                if($this->esisteView(views::spaziopersonaleAdmin)){
                    $eventi = evento::blockedEvents();
                    $select1 = new select('events','events');
                    $select1->addOption('Tutti gli annunci di eventi in quarantena',-1);
                    $select1->addOption('Solo gli annunci di eventi modificati recentemente',0);
                    $select1->addOption('Solo gli annunci di eventi che non sono stati modificati recentemente',1);
                    switch($modified){
                        case 0 :  $select1->setValue('Solo gli annunci di eventi modificati recentemente'); break;
                        case 1 : $select1->setValue('Solo gli annunci di eventi che non sono stati modificati recentemente'); break;
                    }
                    $inputsearch = new input('searchev','searchev', $keyword);
                    $inputsearch->setMaxlength(views::maxLengthSearchinput);
                    $submit = '<input type ="hidden" name="blockedevents"/>'."\n".'<input type ="submit" value="cerca"/>'; 
                    $formTags = array($select1, $inputsearch);
                    $label = array('Seleziona gli eventi in quarantena','Inserisci il titolo di un evento in quarantena che cerchi');
                    $form = "\n<div id=\"searchUserform\">\n<form action=\"spazioadmin.php\" method=\"get\">\n".$this->stampaFieldsetFormTagswithLabels('Filtra i risultati della ricerca',$formTags, $label, $submit)."\n</form></div>\n";
                    $str = "\n".'<div id="risultaticontent">'."\n";
                    $trovato = sizeof($eventi);
                    
                    $count = 0;
                    $eventi2 = array();
                    for($i = 0; $i < $trovato; ++$i ){
                        $show = false;
                        $edit = DB_handler::isDateOld($eventi[$i]->giveLastModifiedDate(), $eventi[$i]->giveLastQuarantineDate());
                        
                        if($modified == -1)
                            $show = true;
                        if($modified == 0 && $edit == false)
                            $show = true;
                        if($modified == 1 && $edit == true)
                                $show = true;
                        if($keyword != ''){
                            if($show && (strpos(mb_strtoupper($eventi[$i]->Titolo(),'UTF-8'), mb_strtoupper($keyword,'UTF-8')) !== false || 
                            strpos(mb_strtoupper($eventi[$i]->giveTipo(),'UTF-8'), mb_strtoupper($keyword,'UTF-8')) !== false ||
                            strpos(mb_strtoupper($eventi[$i]->givebreveDesc(),'UTF-8'), mb_strtoupper($keyword,'UTF-8')) !== false ||
                            strpos(mb_strtoupper($eventi[$i]->giveDesc(),'UTF-8'), mb_strtoupper($keyword,'UTF-8')) !== false))
                            $show = true;
                            else $show = false;
                        }
                        if($show){
                            $eventi2[]= $eventi[$i];
                        }
                    }
                    $list = '';
                    $tot = sizeof($eventi2);
                    for($i = ($page-1) * views::maxRecordPerPage; $i < $tot && $count < views::maxRecordPerPage; ++$i ){
                        $show = false;
                        $edit = DB_handler::isDateOld($eventi2[$i]->giveLastModifiedDate(), $eventi2[$i]->giveLastQuarantineDate()) ? '<em>annuncio non ancora modificato</em>':'<strong>annuncio modificato</strong>';
                        $list = $list. '<li><a href="annuncio.php?view='.$eventi[$i]->giveId().'">'.$edit. ': '.$eventi[$i]->giveTipo().' - '.$eventi[$i]->Titolo().". Pubblicato da ".$eventi[$i]->giveOwner()."</a></li>\n";          
                        ++$count;
                    }
                    $type = '';
                    $totPage = floor($tot / views::maxRecordPerPage);
                    if($tot % views::maxRecordPerPage != 0 || $totPage == 0)
                    ++$totPage;
                    switch($modified){
                        case 0: $type = 'che sono stati modificati recentemente'; break;
                        case 1: $type = 'che non sono stati modificati recentemente'; break;
                    }
                    if($keyword != '')
                        $type = $type.", che contengono la parola \"<em>$keyword</em>\"";
                    if($tot == 0)
                        $str = $str."<h4>Nessun annuncio di evento in quarantena $type</h4></div>\n<p class=\"mobilegoup viewinmobilelink\"><a href=\"#navUtente\">Torna nel men&ugrave; del tuo spazio amministratore</a></p>$form\n";
                    else {
                    $links = '<p class="mobilegodown viewinmobilelink"><a accesskey="j" href="#lista_SUresults">Vai alla lista degli annunci in quarantena</a></p>'."\n".
                        '<p class="mobilegoup viewinmobilelink"><a accesskey="k" href="#searchUserform">Vai a Cerca tra gli annunci in quarantena</a></p>'."\n".
                         '<p class="mobilegodown viewinmobilelink"><a accesskey="l" href="#gotopagina">Vai ad una pagina specifica degli annunci in quarantena</a></p>'."\n";
                    $str = $str."\n<strong>Sono stati trovati $tot annunci di eventi in quarantena $type</strong>\n$links";
                    $head = $this->vaiaPaginaform('spazioadmin.php',$totPage, $page) ;
                    $head = str_replace('<span id="hiddeninput"></span>', '<input type="hidden" name="listevblocked"/>'.
                                        "\n".'<input type="hidden" name="events" value="'.$modified.'"/>'.
                                        "\n".'<input type="hidden" name="searchev" value="'.$keyword.'"/>', $head);
                    $head = str_replace('&undefined', '&amp;listevblocked&events='.$modified.'&searchev='.$keyword, $head);
                    $str= $str."<p class=\"paginacorrente\">Pagina $page su $totPage</p><ul id=\"lista_SUresults\">\n $list";
                    $str = $str."</ul>\n<p class=\"paginacorrente\"><span class=\"hidden\">Fine </span>Pagina $page su $totPage</p>\n<p class=\"mobilegoup viewinmobilelink\"><a href=\"#searchUserform\">Salta le altre pagine degli annunci in quarantena trovati e vai a cerca tra gli annunci in quarantena</a></p>\n".
                    $head."<p class=\"mobilegoup viewinmobilelink\"><a href=\"#navUtente\">Torna nel men&ugrave; del tuo spazio amministratore</a></p></div>".$form;
                    }
                    $str = $this->buildUtenteNav($str,3);
                    $str = $this->putInContenuto($str,'<a href="index.php">Home</a> > Spazio amministratore: Annunci messi in quarantena');
                    echo $this->giveHeader("Annunci messi in quarantena ($tot annunci), pagina $page su $totPage dei risultati",6).$str.file_get_contents(views::footer);
                }
            }
            else $this->errore(1);
        }
        else $this->loginPage();
    }
    public function removeSegnalato($idev){//metodo che permette all'utente amministratore  di togliere l'annuncio $idev dai segnalati 
        if($this->isUserConnected()){
            if($_SESSION['user']->isAdmin()){
                if($_SESSION['user']->removeSegnalato($idev))
                    $this->messaggioImportante('<p><strong>Operazione avvenuta con successo!</strong></p>'.
                            '<p><a href="spazioadmin.php?listevsegnalati">Torna alla lista degli annunci segnalati</a></p>');  
            }
            else $this->errore(1);
        }
        else $this->loginPage();
    }
    public function blockUser($idu,$value, $motivo='', $blockevent=false, $visited=false){//metodo che permette di bloccare($value=true) con motivo $motivo, o sbloccare ($value=false) l'utente $idu
    //Se $blockevent= true && $value=true allora vengono bloccati assieme all'utente anche i suoi eventuali annunci pubblicati
    //Se $blockevent= false && $value=true allora non vengono bloccati assieme all'utente, i suoi eventuali annunci pubblicati
    //Se $blockevent= false && $value=false allora vengono sbloccati assieme all'utente anche i suoi eventuali annunci pubblicati bloccati
    //Se $blockevent= true && $value=false allora non vengono sbloccati assieme all'utente , i suoi eventuali annunci pubblicati bloccati
    //Se $visited=true allora è stato chiesto all'amministrato di scegliere una delle opzioni elencate sopra, altrimenti gli viene richiesto
        if($this->isUserConnected()){
            if($_SESSION['user']->isAdmin()){
                if($value == true && $motivo == '')
                    $this->messaggioImportante($this->reasonblockUserFormBuild($idu));
                else{
                    if(utente::isUtentePremium($idu) && !$visited){
                       if($value == true){
                        $this->messaggioImportante('<p>'.$idu.' risulta essere un utente <span xml:lang="en" lang="en">premium</span>.</p>'.
                                                  '<p><a href="spazioadmin.php?bloccauser='.$idu.'&amp;block&amp;motivo='.$motivo.'&amp;blockevent&amp;visited">Blocca '.
                                                  $idu.' e tutti i suoi annunci pubblicati</a>, oppure '.
                                                  '<a href="spazioadmin.php?bloccauser='.$idu.'&amp;block&motivo='.$motivo.'&amp;visited">Blocca solo '.$idu.' senza bloccare i suoi annunci pubblicati</a>'.'</p>');
                       }
                        else {
                            $this->messaggioImportante('<p>'.$idu.' risulta essere un utente <span xml:lang="en" lang="en">premium</span>.</p>'.
                                                  '<p><a href="spazioadmin.php?bloccauser='.$idu.'&amp;visited">Sblocca '.
                                                  $idu.' e tutti i suoi annunci bloccati</a>, oppure '.
                                                  '<a href="spazioadmin.php?bloccauser='.$idu.'&amp;blockevent&amp;visited">Sblocca solo '.$idu.' senza sbloccare i suoi annunci bloccati</a>'.'</p>');
                        }
                    }
                    elseif($_SESSION['user']->bloccaUtente($idu,$value,$motivo, $blockevent)){
                      $this->messaggioImportante('<p><strong>Operazione avvenuta con successo!</strong></p>'.
                                       '<p><a href="spaziopersonale.php?searchuser">Torna alla lista degli utenti iscritti</a></p>');
                    }
                    else $this->errore(2);
                } 
            }
            else $this->errore(1);
        }
        else $this->loginPage();
    }
    public function mettiinprimopiano($idev, $value){//Metodo che permette di aggiungere($value=true) o rimuovere($value=false) l'annuncio $idev in primo piano
        if($this->isUserConnected()){
            if($_SESSION['user']->isAdmin()){
                if($_SESSION['user']->mettiinprimopiano($idev, $value)){
                    $this->messaggioImportante('<p><strong>Operazione avvenuta con successo!</strong></p>'.
                                             '<p><a href="annuncio.php?view='.$idev.'">Torna all\'annuncio</a></p>'.
                                             '<p><a href="spazioadmin.php">Torna nel tuo spazio amministratore</a></p>'.
                                              '<p><a href="archivio.php">Torna a tutti gli annunci pubblicati</a></p>');
                    $ev = new evento($idev);
                    $messaggio = $value ? 'Il tuo annuncio di '.$ev->giveTipo().' dal titolo: '.$ev->Titolo().
                    '. Si trova adesso in primo piano':'Il tuo annuncio di '.$ev->giveTipo().' dal titolo: '.$ev->Titolo().
                    '. Risulta esser stato rimosso dal primo piano';
                    $_SESSION['user']->notificaUtente($ev->giveOwner(),$messaggio,'index.php','D' );
                }
                else{
                    if($value && DB_handler::totinprimopiano() >= DB_handler::maxPrimoPiano){
                        $this->messaggioImportante('<p><strong>&Egrave; stato raggiunto il limite massimo di annunci che puoi mettere in primo piano</strong></p>'.
                                             '<p><a href="annuncio.php?view='.$idev.'">Torna all\'annuncio</a></p>'.
                                        '<p><a href="archivio.php">Torna a tutti gli annunci pubblicati</a></p>');
                    }
                    else{
                        $this->errore(2);
                    }
                }
            }
            else $this->errore(1);
        }
        else $this->loginPage();
    }
    public function listaPartecipanti($idev){ //Metodo che mostra la lista dei partecipanti all'evento $idev
        if($this->esisteView(views::contentonly)){
            $ev = new evento($idev);
            $part = $ev->listaPartecipanti();
            $tot = sizeof($part);
            $connected = $this->isUserConnected();
            $check = $connected && $_SESSION['user']->giveUserName() == $ev->giveOwner();
            $str = '<h3>Partecipanti  all\'evento '.$ev->giveTipo().' : '.$ev->Titolo()."</h3>\n";
            $str = $str."<h4>Totale partecipanti :</h4><p> $tot utenti </p>\n";
            $db = new DB_handler;
            if($tot > 0){
                $str = $str."<h4>Lista dei partecipanti </h4>\n";
                $str = $str.'<p>Nella seguente lista i partecipanti vengono elencati con il loro nome utente '.($check ? ' [ cognome, nome ]': '')."</p>\n";
                $str = $str."\n<ol>\n";
                $index = 1;
                foreach($part as $ut){
                    $str = $str."<li>\n";
                    $tu = $connected && $_SESSION['user']->giveUserName() == $ut ? ' ( Questo sei tu )' : '';
                    $str = $str. '<a href="profilo.php?utente='.$ut."\">Partecipante $index: ".$ut;
                    if($check){
                        $str = $str. ' [ '.$db->userCognome($ut).', '.$db->userNome($ut)." ]\n";
                    }
                    $str = $str."$tu</a></li>\n";
                    ++$index;
                }
                $str = $str."</ol>\n";
            }
            $str = $this->putInContenuto($str, '<a href="index.php">Home</a> > <a href="archivio.php?search&amp;type='.$db->giveIdTypeEvent($ev->giveTipo()).'">'.
                                                                $ev->giveTipo().'</a> > <a href="annuncio.php?view='.$ev->giveId() .'">Dettagli annuncio dal titolo: '.$ev->Titolo().'</a> > Lista dei partecipanti');
            echo $this->giveHeader('Lista dei partecipanti all\'evento: '.$ev->Titolo()).$str.file_get_contents(views::footer);
        }
    }
    public function eliminaUtente($idu, $conf){//Metodo che permette di eliminare l'utente $idu se confermato($conf), altrimenti viene richiesta una conferma
        if($this->isUserConnected()){
            if($conf == false)
                $this->messaggioImportante('<p><strong>Attenzione : Verrano eliminati tutte le informazioni personali, gli annunci pubblicati e il profilo.</strong></p>'.
                                           '<p>Sei sicuro di voler proseguire con l\'eliminazione ?</p>'.
                                           '<p><a href="spaziopersonale.php?deleteuser='.$idu.'&amp;conf">Prosegui con l\'eliminazione.</a></p>'.
                                           '<p><a href="spaziopersonale.php">Annulla e torna nel tuo spazio personale</a></p>');
            else{
            if($_SESSION['user']->isAdmin()){
                if($_SESSION['user']->giveUserName()==$idu){
                    $this->messaggioImportante('<p><strong>Errore : Non &egrave; possibile cancellare il profilo amministratore.</strong></p>'.
                                       '<p><a href="spaziopersonale.php?searchuser">Torna alla lista degli utenti iscritti</a></p>');
                }
                elseif($_SESSION['user']->eliminautente($idu, $_SESSION['user']->giveUserName())){
                    $this->messaggioImportante('<p><strong>Operazione avvenuta con successo!</strong></p>'.
                                       '<p><a href="spaziopersonale.php?searchuser">Torna alla lista degli utenti iscritti</a></p>');
                }
                else $this->errore(2);
            }
            elseif($_SESSION['user']->giveUserName()==$idu){
                if($_SESSION['user']->eliminautente($idu)){
                    unset($_SESSION['user']);
                    $this->messaggioImportante('<p><strong>Operazione avvenuta con successo!</strong></p>'.
                                       '<p><a href="index.php">Torna nella pagina principale</a></p>');
                }
                else $this->errore(2);
            }else $this->errore(1);
            }
        }
        else $this->loginPage();
    }
    public function makepremium($idu, $value){//Metodo che permette di rendere $idu premium($value=true) o di togliergli i privileggi se lo era già($value=false)
        if($this->isUserConnected()){
            if($_SESSION['user']->isAdmin()){
               if($_SESSION['user']->makepremium($idu, $value)){
                   $this->messaggioImportante('<p><strong>Operazione avvenuta con successo!</strong></p>'.
                                              '<p><a href="spaziopersonale.php?searchuser">Torna alla lista degli utenti iscritti</a></p>');
                    if($value)
                        $_SESSION['user']->notificaUtente($idu,'<strong>Sei diventato un utente <span xml:lang="en" lang="en">Premium !</span> e potrai adesso pubblicare annunci di eventi</strong>','spaziopersonale.php?action=nuovoannunciopagina','G');
                    else $_SESSION['user']->notificaUtente($idu,'<strong>Non sei più un utente <span xml:lang="en" lang="en">Premium </span> e non potrai più pubblicare annunci di eventi</strong>','index.php?action=faq&amp;id=nomorepremium','G');
               } 
            }
            else $this->errore(1);
        }
        else $this->loginPage();
    }
    public function bepremium($conf=false,$annulla = false){//metodo che permette all'utente connesso di effettuare una richiesta di passaggio premium. Se conf=false allora l'utente deve ancora confermare la sua richiesta, e viene ridiretto alla pagina di conferma
        if($conf==false){
            if($annulla == true){
                $this->messaggioImportante('<p>Sei sicuro di voler annulare la tua domanda per diventare utente <span xml:lang="en" lang="en">premium</span> ?</p>'."\n".
                                               '<p><a href="index.php?action=bepremium&amp;conf&amp;annulla">Sono sicuro di voler annullare la mia richiesta <span xml:lang="en" lang="en">premium</span></a></p>'."\n".
                                               '<p><a href="spaziopersonale.php">Non annulare la domanda e torna nel tuo spazio personale</a></p>'."\n".
                                               '<p><a href="index.php">Non annulare la domanda e  torna alla pagina principale</a></p>'."\n");
            }
            elseif($this->esisteView(views::premiumads)){
               echo file_get_contents(views::premiumads);
            }
            else $this->errore(0);
        }
        else{
        if($this->isUserConnected()){
            if(!$_SESSION['user']->isAdmin()){
              if($annulla == false){
               if($_SESSION['user']->isPremium())
                   $this->messaggioImportante('<p>Sei gi&agrave; un utente <span xml:lang="en" lang="en">premium</span>, non puoi pi&ugrave; fare richiesta.</p>'."\n".
                                               '<p><a href="spaziopersonale.php">Torna nel tuo spazio personale</a></p>'."\n".
                                               '<p><a href="index.php">Torna nella pagina principale</a></p>'."\n");
                elseif($_SESSION['user']->haChiestoPremium())
                   $this->messaggioImportante('<p>Hai già una richiesta in sospensione. Ti contatterò via <span xml:lang="en" lang="en">mail</span> nei prossimi giorni</p>'."\n".
                                               '<p><a href="index.php?action=bepremium&amp;annulla">Vuoi annullare la tua richiesta?</a></p>'."\n".
                                               '<p><a href="spaziopersonale.php">Torna nel tuo spazio personale</a></p>'."\n".
                                               '<p><a href="index.php">Torna nella pagina principale</a></p>'."\n");
                    elseif($_SESSION['user']->premiumrequest())
                        $this->messaggioImportante('<p><strong>La tua richiesta &egrave; stata ricevuta !</strong>Verrai contattato via <span xml:lang="en" lang="en">mail</span> nei prossimi giorni per rispondere ad alcune domande.
                                                    Ti invitiamo a controllare spesso la <span xml:lang="en" lang="en">mail</span> con cui ti sei iscritto a questo sito</p>'."\n".
                                               '<p><a href="spaziopersonale.php">Torna nel tuo spazio personale</a></p>'."\n".
                                               '<p><a href="index.php">Torna nella pagina principale</a></p>'."\n");
                        else{
                            $this->errore(2);
                        }
              }
              else{
                  if($_SESSION['user']->removefromrequest($_SESSION['user']->giveUserName()))
                  $this->messaggioImportante('<p><strong>La tua domanda per diventare utente <span xml:lang="en" lang="en">premium</span> &egrave; stata annullata con successo !</strong>. </p>'."\n".
                                            '<p>Potrai rifare la richiesta quando desideri cliccando al <span xml:lang="en" lang="en">link</span> apposito nel tuo men&ugrave; di navigazione dello spazio personale . </p>'."\n".
                                               '<p><a href="spaziopersonale.php">Torna nel tuo spazio personale</a></p>'."\n".
                                               '<p><a href="index.php">Torna nella pagina principale</a></p>'."\n");
                  else{
                      $this->errore(2);
                  }
              }
            }
            else $this->errore(1);
        }
        else{
            $this->messaggioImportante('<p><strong>Devi accedere al tuo spazio personale prima di effettuare una richiesta <span xml:lang="en" lang="en">premium</span></strong>.</p>'."\n".
                                               '<p><a href="index.php?action=login">Accedi al tuo spazio personale</a></p>'."\n".
                                               '<p><a href="index.php?action=signup">iscriviti se non sei ancora iscritto</a></p>'."\n".
                                               '<p><a href="index.php">Torna nella pagina principale</a></p>'."\n");
        }
        }
    }
    public function recupPass($mail = ''){//Metodo che simula il recupero password dell'utente che ha dimenticato la password
        if($mail == ''){
            $str = '<span id="hide"></span><p><strong>Benvenuto nella pagina di recupero password.</strong></p> Se hai dimenticato la tua password e vuoi recuperarla, devi inserire la tua 
             <span xml:lang="en" lang="en">mail</span> con ti sei iscritto a questo modo. Se la <span xml:lang="en" lang="en">mail</span> che inserirai risulta corretta, ti invieremo 
             una posta elettoronica che contiene tutte le istruzioni per recuperare la tua password. Compila la form che segue: </p>';
            $inp = new input('recuppass','recuppass');
            $inp->setMaxlength(70);
            $form = "<form action=\"index.php\" method=\"post\" >\n".
                    $this->stampaFieldsetFormTagswithLabels('Recupero della tua password',array($inp),array('Inserisci il tuo indirizzo di posta elettronica con cui ti sei iscritto (al massimo 70 caratteri). <em>Esempio: mario.rossi&#64;gmail.com</em>'),
                    '<input type="submit" name="submit" value="Invia la mail"/>')."</form>\n
                    <p><a class=\"annulla\" href=\"index.php\">Annulla tutto e torna alla pagina principale</a></p>\n";
            $this->messaggioImportante($str.$form);
        }
        else{
            $db = new DB_handler;
            if($db->esisteMail($mail)){
                $this->messaggioImportante('<p><strong>Ti abbiamo appena inviato una posta elettronica delle istruzioni per il recupero della password  alla <span xml:lang="en" lang="en">mail</span>: '.$mail.' . </strong> </p>');
            }
            else {
                $this->messaggioImportante('<p><strong>La <span xml:lang="en" lang="en">mail</span>: '.$mail.' . Non risulta registrata su questo sito </strong> </p>'.
                                            '<p><a href="index.php?action=getpass">Riprova il procedimento di recupero password</a></p>'.
                                            '<p><a class="annulla" href="index.php">Annulla tutto e torna alla pagina principale</a></p>');
            }
        }
    }
    public function showFullFoto($idev, $index=0, $editmode=false){//Il metodo restituisce le immagini non ridimensionate dell'annuncio $idev, a partire dall'immagine $index. Se $editmode = true, allora si tra del pubblicatore stesso che sta visualizzando le foto in Gestione foto
        $ev = new evento($idev);
        if($ev->esiste()){
           $totFoto = $ev->countFoto();
            if($index < $totFoto){
                $torna = '';
                $editmode = $editmode == true && $this->isUserConnected() && $_SESSION['user']->giveUserName() == $ev->giveOwner();
                if($editmode)
                    $torna = '<a class="annulla" href="spaziopersonale.php?action=editfoto&amp;event='.$idev.'">Torna alla gestione delle foto del tuo annuncio</a>';
                else
                    $torna = '<a href="annuncio.php?view='.$idev.'" class="annulla">Torna all\'annuncio</a>';
                $str = '<strong>Foto '.($index+1)." su $totFoto</strong>\n";
                $str = $str.($index > 0 ? '<span class="prevpage"><a href="annuncio.php?showfoto='.$idev.'&amp;index='.($index-1).'">Vai a foto precedente</a></span>': '');
                $str = $str. ( $index+1 < $totFoto ? '<span class="nextpage"><a href="annuncio.php?showfoto='.$idev.'&amp;index='.($index+1).'">Vai a foto seguente</a></span>': '');
                $str = $str."\n".$torna;
                $str = "\n<div id=\"viewfullfoto\">\n$str\n".$ev->giveFoto($index)."\n".$str."\n</div>\n";
                $db = new DB_handler;
                echo $this->giveHeader('Foto pubblicate dell\' annuncio non ridimensionate').$this->putInContenuto($str, '<a href="index.php">Home</a> > <a href="archivio.php?search&amp;type='.$db->giveIdTypeEvent($ev->giveTipo()).'">'.
                                                                $ev->giveTipo().'</a> > <a href="annuncio.php?view='.$idev.'"> Annuncio con titolo: '.$ev->Titolo().
                                                                '</a> > Foto non ridimensionate ').file_get_contents(views::footer);
                
                return;
            }
        }
        $this->errore(0);
    }
    
}

?>

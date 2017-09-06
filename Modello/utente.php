<?php
require_once("DB_handler.php");
require_once("evento.php");

//LA CLASSE UTENTE E' UNA CLASSE I CUI OGGETTI RAPPRESENTANO UTENTI ISCRITTI CONNESSI
class utente {
      
    private $dB;//oggetto di tipo DB_handler
    private $nome_utente;//username dell'utente
    private $password;//password dell'utente per svolgere delle operazioni confidenziali che richiedono la password
    private $ultimoAccesso;//stringa che rappresenta la data di ultima connessione dell'ultente, utile per caricare le ultime notizie
    private $filexml;//path al file xml privato dell'utente
    private $newsxml;//path al file xml delle ultime notizie per l'utente
    private $social;//path al file xml del social dell'utente
    private $admin = false;//dice se l'utente connesso è amministratore
    const tipidiNotizie = array('A'=>'Nuovi seguaci','B'=>'Nuovi partecipanti ai tuoi eventi',
                                'C'=>'I tuoi annunci bloccati',
                                'D'=>'I tuoi annunci messi in primo piano',
                                'E'=>'Nuove pubblicazioni degli utenti che segui',
                                'F'=>'Partecipazioni ad eventi degli utenti che segui',
                                'G' => 'Il tuo passaggio ad utente premium');   
    public function __construct($idu, $password){//Costruttore che inizializza l'utente $idu con password=$password, se l'utente esiste, altrimenti non viene inizializzato e non potrà avere le funzionalità offerte agli utenti iscritti
            $this->dB = new DB_handler();
            $ids = !$this->dB->isUtenteBlocked($idu,$password)? $this->dB->esisteUtenteConPassword($idu, $password): array('','');
            if( $ids[0] != ''){
                $this->nome_utente = $ids[0];
                $this->password = $ids[1];
                $this->ultimoAccesso = $this->dB->ultimoaccesso($ids[0]);
                $this->admin = $this->dB->esisteAdmin($ids[0],$ids[1]);
                $this->filexml = $this->dB->giveUtentePathFilexml($ids[0], $ids[1]);
                $this->newsxml = $this->dB->giveUtenteNewsFilexml($ids[0]);
                $this->social = $this->dB->giveUtenteSocialFilexml($ids[0]);
                $this->dB->updateUltimoAccesso($ids[0],$ids[1],date('d\/m\/Y'));
            }      
        }
    private function validHour($o,$min){//ritorna true se l'ora $o:$min inserito è ben formattati
        if(is_numeric($o) && is_numeric($min)){
            $o = (int)$o;
            $min = (int)$min;
           if($o >= 0 && $o < 24){
               if($min >= 0 && $min<60)
                   return true;
           } 
               
        }
        return false;
    }
    private function checkInputEventForms($tit, $b_desc, $desc, $g, $m, $a,$o,$min, $denom, $via, $comune, $prov){//Metodo di supporto che verifica se titolo $tit, introduzione $b_desc, descrizione $desc, data $g/$m/$a e indirizzo
    // $denom $via $comune $prov, di un annuncio di evento sono validi e conformi alle regole di pubblicazione
            $errore = array();
            if(!preg_match('/.{2,}[^\s]/',$tit))
                $errore[] = 'Inserire un titolo chiaro e comprensibile con almeno 2 caratteri. Ad esempio: NUOVI CORSI DI CINESI';
            if(strlen($tit) > DB_handler::maxLengthTitolo)
                $errore[] = 'Il titolo inserito &egrave; troppo lungo, il numero massimo di caratteri permesso &egrave; '.DB_handler::maxLengthTitolo .
                        ', mentre il titolo inserito conta '.strlen($tit).' caratteri';
            if(preg_match('/^[^a-zA-Z]?$/',$b_desc))
                $errore[] = 'Inserire una introduzione chiara e comprensibile dell\'annuncio';
            if(strlen($b_desc) > DB_handler::maxLengthBrevDesc)
                $errore[] = 'L\'introduzione dell\'annuncio inserita &egrave; troppo lunga, il numero massimo di caratteri permesso &egrave; '.DB_handler::maxLengthBrevDesc .
                        ', mentre l\'introduzione inserita conta '.strlen($b_desc).' caratteri';
            if(strlen($desc) > DB_handler::maxLengthDesc)
                $errore[] = 'La descrizione dell\'evento &egrave; troppo lunga, il numero massimo di caratteri permesso &egrave; '.DB_handler::maxLengthDesc .
                        ', mentre la descrizione inserita conta '.strlen($desc).' caratteri';
            if(preg_match('/^[^a-zA-Z]?$/',$desc))
                $errore[] =  'Inserire una descrizione chiara e comprensibile di almeno 10 caratteri dell\'evento';
            if(!checkdate((int)$m,(int)$g, (int)$a))
                $errore[] = 'Inserire una data corretta: Inserire il giorno  in formato numerico GG, da 1 a 31. Esempio: 03 se è il giono tre, oppure 24 se è il giorno ventiquattro, 
                Inserire il mese in formato numerico MM da 1 a 12. Esempio: 09 se è a settembre, Inserire l\'anno in formato numerico AAAA. Esempio: 2017';
            if(!$this->validHour($o,$min))
                $errore[] = "l'ora o il minuto inserito  inserito non è ben formattato: Inserire le ore in formato numerico HH,da 0 a 23. Esempio: 14 se è alle quattordici, 
            oppure 09 se è alle nove, e Inserire i minuti in formato numerico MM, da 0 a 59. Esempio: 15";
            $check = self::isValidIndirizzo($denom, $via, $comune, $prov);
            if($check != '')
                $errore[] = $check;
            return $errore;
            
        }
    public function isConnected(){ //metodo che ritorna true se l'utente è connesso, false altrimenti
            return isset($this->nome_utente);
        }
    public function isAdmin(){//metodo che ritorna true se l'utente è amministratore
            return $this->admin;
        }
    public function isPremium(){//metodo che ritorna true se l'utente è premium
            return $this->dB->utentePremium($this->nome_utente);
        }
    public function giveUserName(){//metodo che ritona il nome utente dell'utente
            return $this->nome_utente;
        }
    public function giveCognome(){//metodo che ritorna il cognome dell'utente
            return $this->dB->utenteCognome($this->nome_utente);
    }
    public function giveNome(){//metodo che ritorna il nome dell'utente
            return $this->dB->utenteNome($this->nome_utente);
    }
    public function giveDataNascita(){//metodo che ritorna la data di nascita dell'utente
            return $this->dB->utenteDataNascita($this->nome_utente);
    }
    public function giveMail(){//metodo che ritorna la mail dell'utente
            return $this->dB->utenteMail($this->nome_utente, $this->password);
    }
    public static function EventiPubblicati($idu, $keyword='', $isOwner=false){//funzione che ritorna gli annunci pubblicati da $idu, ordinati per data di pubblicazione($datapub), ordinati in modo decrescente($desc), contenenti la parola $keyword
            $eventiPub = array();
            if(self::isUtentePremium($idu)){
                $dB = new DB_handler;
                $list = $dB->giveListEventsIds($idu,$keyword, $isOwner);
                foreach($list as $value)
                    $eventiPub[] = new evento($value,$idu);
            }
            return $eventiPub;
                
     }
    public function partecipazione($passati){ //metodo che ritorna l'array di eventi futuri($passati=false) o passati, alle quali l'utente partecipa
            $dom = new domoperations($this->filexml);
            $parte = $dom->textoftags('partecipazioni',0,'partecipazione');
            $eventiPub = array();
            foreach($parte as $ev){
                $event = new evento($ev);
                if(!$event->esiste()){
                    $this->ritiraPartecipazione($event->giveId());
                }
                elseif($event->isOld()){
                    if($passati === true)
                       $eventiPub[] = $event; 
                }
                elseif($passati === false)
                    $eventiPub[] = $event;
                
            }
           
            return $eventiPub;
                
        }
    public static function eliminaWhiteSpaceFirstAndAfter($stringa){//funzione che elimina gli spazi e tab all'inizio o alla fine della stringa $stringa e la ritorna
           $stringa = preg_replace('/^\s+/', '', $stringa);
           $stringa = preg_replace('/\s+$/', '', $stringa);
           return $stringa;
        }
    public static function eliminatoomuchWhiteSpaceBetween($stringa){//funzione che elimina sequenze di spazi o tab nella stringa $stringa e la ritorna
           $stringa = preg_replace('/\s\s+/', ' ', $stringa);
           return $stringa;
        }
    public static function isValidName($nome){//funzione che ritorna true se $nome potrebbe essere un nome o dei nomi di una persona
         return preg_match_all('/[a-zA-Z]+\'?[a-zA-Z]+/',$nome) > 0 && preg_match('/[^a-zA-Z\'\s]/',$nome)== 0;
        }
    public static function isValidNomeUtente($idu){//funzione che ritorna la stringa vuota se $idu è un nome utente corretto e che non esiste, altrimenti restituisce un errore
            if(DB_handler::MaxlengthUserName < strlen($idu))
                return 'Il nome utente scelto è troppo lungo. Sono richiesti un massimo di '.DB_handler::MaxlengthUserName .'.';
            if(preg_match('/^[^a-zA-Z0-9]?$/',$idu))
                return 'Nome utente non valido. Il nome utente deve contenere almeno un carattere alfabetico, e può contenere cifre.';
            return '';
        }
    public static function isValidPassword($pass){//funzione che ritorna la stringa vuota se $pass è una password corretta da memorizzare, altrimenti restituisce un errore 
            if(preg_match('/\s/',$pass))
                return 'la password non può contenere spazi';
            if(DB_handler::MinlengthPassword > strlen($pass))
                return 'La password deve avere almeno '.DB_handler::MinlengthPassword .' caratteri';
            if(DB_handler::MaxlengthPassword < strlen($pass))
                return 'La password scelta è troppo lunga. Sono richiesti un massimo di '.DB_handler::MinlengthPassword .'.';
            return '';
        }
    public static function isValidIndirizzo($denom, $via, $comune, $prov){//funzione che ritorna la stringa vuota se l'indirizzo di un evento composto dalla denominazione urbanistica $denom, nome di indirizzo $via, comune $comune
// e provincia $prov sono corretti sintaticamente, altrimenti restituisce un messaggio di errore    
            if(is_numeric($denom) || is_numeric($via) || is_numeric($comune) || is_numeric($prov))
                return 'Inserire un indirizzo corretto. L\'indirizzo non pu&ograve; essere solo numerico.';
             if($denom == '')
                return 'Inserire una denominazione urbanistica corretta. Esempio: Via';
            if($via == '')
                return 'Inserire un indirzzo corretto. Esempio: Paolotti 13 A';
            if($comune == '')
                return 'Inserire una città o comune italiano. Esempio: Padova';
            if($prov == '')
                return 'Inserire una provincia italiana. Esempio: Treviso';
            $db = new DB_handler;
            if(!$db->esisteComune($comune))
                return $comune.' non è un comune italiano';
            if(!$db->esisteProvincia($prov))
                return $prov.' non è una provincia italiana';
            return '';
        }
        
           
    public function addNewEvent($tit, $b_desc, $desc, $tipo, $categ, $g, $m, $a,$o, $min, $denom,  $via, $comune, 
                                    $prov,$foto,$fotoName,$fotoDesc, $etich){//metodo che permette all'utente premium di aggiungere un nuovo annuncio di evento
        //restituisce un array i cui elementi sono due, il primo rappresenta un boolean che è false se è l'annuncio è stato aggiunto correttamente, in questo caso il secondo elemento rappresenta l'id dell'annuncio
        //che potrà essere utilizzato per visualizzare l'annuncio, altrimenti è il messaggio di errore se il primo elemento dell'array è true
            if($this->isPremium()){
                $errore = array();
                
                $tit = self::eliminatoomuchWhiteSpaceBetween($tit);
                $b_desc = self::eliminatoomuchWhiteSpaceBetween($b_desc);
                $desc = self::eliminatoomuchWhiteSpaceBetween($desc);
                $via = self::eliminatoomuchWhiteSpaceBetween($via);
                $comune = self::eliminatoomuchWhiteSpaceBetween($comune);
                $prov = self::eliminatoomuchWhiteSpaceBetween($prov);
                
                $errore = $this->checkInputEventForms($tit, $b_desc, $desc, $g, $m, $a,$o, $min, $denom,$via, $comune,$prov); 
                if(sizeof($errore) != 0)
                   return array(true,$errore);
                $o = strlen($o) == 1? '0'.$o : $o; 
                $min = strlen($min) == 1? '0'.$min : $min;
                $g = strlen($g) == 1? '0'.$g : $g; 
                $m = strlen($m) == 1? '0'.$m : $m;
                $id = $this->dB->aggiungiEvento($this->nome_utente,$this->password, $tit,"$g/$m/$a","$o : $min", $b_desc, $tipo, $categ,$comune);
                if($id == '')
                    return array(TRUE, 'Errore nell\'inserimento');
                $event = new evento($id, $this->nome_utente);
                $check = $event->UploadFoto($this->password,$foto,$fotoName,$fotoDesc); 
                if($check !=''){
                    $event->rimuoviEventodaDB($this->password);
                $errore[] =  $check;
                }
                if(sizeof($errore) == 0 ){
                $event->editInfoEvent($this->password,$tit, $b_desc, $desc, $tipo, $categ, $g, $m, $a,$o, $min, strtoupper($denom), 
                                  strtoupper($via), strtoupper($comune), strtoupper($prov), $etich);
                    return  array(false, $id);
                }
                return array(TRUE,$errore);
            }
            return array(true,'ACCESSO VIETATO');
    }
    public function editEventInfo($idev, $tit, $b_desc, $desc, $tipo, $categ, $g, $m, $a, $o, $min, $denom, $via, $comune,$prov,$etich){
        //metodo che permette all'utente premium di aggiornare le informazioni dell'annuncio $idev di cui deve essere proprietario. Ritorna la stringa vuota se l'operazione ha avuto successo altrimenti ritorna l'errore avvenuto
        if($this->isPremium()){
            
            $tit = self::eliminatoomuchWhiteSpaceBetween($tit);
            $b_desc = self::eliminatoomuchWhiteSpaceBetween($b_desc);
            $desc = self::eliminatoomuchWhiteSpaceBetween($desc);
            $via = self::eliminatoomuchWhiteSpaceBetween($via);
            $comune = self::eliminatoomuchWhiteSpaceBetween($comune);
            $prov = self::eliminatoomuchWhiteSpaceBetween($prov);
            
            $errore = $this->checkInputEventForms($tit, $b_desc, $desc, $g, $m, $a,$o, $min, $denom, $via, $comune,$prov);
            if(sizeof($errore) != 0)
                return array(true,$errore);
            $event = new evento($idev, $this->nome_utente);
            $o = strlen($o) == 1? '0'.$o : $o; 
            $min = strlen($min) == 1? '0'.$min : $min;
            $g = strlen($g) == 1? '0'.$g : $g; 
                $m = strlen($m) == 1? '0'.$m : $m;
            if(!$this->dB->updateEvento($idev, $this->nome_utente,$this->password, $tit, "$g/$m/$a", "$o : $min",$b_desc, $tipo, $categ, $comune)){
                return array(true,'Errore nell\'inserimento');
            }
            if(!$event->editInfoEvent($this->password,$tit, $b_desc, $desc, $tipo, $categ, $g, $m, $a,$o, $min,strtoupper($denom), 
                                  strtoupper($via), strtoupper($comune), strtoupper($prov), $etich, true)){
           $this->dB->updateEvento($idev, $this->nome_utente,$this->password, $event->Titolo(), $event->dataEvento(), $event->givebreveDesc(),
                                    $event->giveTipo(), $event->giveCateg(), $event->giveCitta());
            return array(true,'Errore nell\'inserimento');
                                      
        }
            return array(false, array());
        }
        return array(true,'ACCESSO VIETATO');        
    }
    public static function checkInfoPersonali($cogn, $nom, $mail, $idu){//Funzione che permette di controllare se il cognome $cogn, il nome $nom, la mail $mail, il nome utente $idu, sono corretti sintaticamente, ritorna la stringa vuota
    //in caso affermativo, altrimenti ritorna l'errore avvenuto
        if(!self::isValidName($cogn))
            return 'Il cognome inserito non è corretto';
        if(strlen($cogn) > DB_handler::MaxlengthCognomeUtente)
            return 'Il cognome inserito è troppo lungo. Sono richiesti un massimo di '.DB_handler::MaxlengthCognomeUtente.' caratteri.';
        if(!self::isValidName($nom))
            return 'Il nome inserito non è corretto';
       if(strlen($nom) > DB_handler::MaxlengthNomeUtente)
            return 'Il nome inserito è troppo lungo. Sono richiesti un massimo di '.DB_handler::MaxlengthNomeUtente.' caratteri.';
        if(!filter_var($mail, FILTER_VALIDATE_EMAIL))
                return 'la mail inserita non è corretta';
        if(strlen($mail) > DB_handler::MaxlengthMail)
            return 'La mail inserita è troppo lunga. Sono richiesti un massimo di '.DB_handler::MaxlengthMail.' caratteri.';
        
        $errore = self::isValidNomeUtente($idu);
        if($errore != '')
            return $errore;
        return '';
    }
    public static function addNewUser($cogn, $nom, $mail, $idu, $pass1, $pass2){//funzione che permette di aggiungere un nuovo utente, ritorna la stringa vuota in caso di successo, altrimenti ritorna l'errore avvenuto
            $cogn = self::eliminaWhiteSpaceFirstAndAfter($cogn);
            $cogn = self::eliminatoomuchWhiteSpaceBetween($cogn);
            $nom = self::eliminaWhiteSpaceFirstAndAfter($nom);
            $nom = self::eliminatoomuchWhiteSpaceBetween($nom);
            $idu = self::eliminaWhiteSpaceFirstAndAfter($idu);
            
            $errore = self::checkInfoPersonali($cogn, $nom, $mail, $idu);
            if($errore != '')
                return $errore;
            if($pass1 != $pass2)
                return 'La <span xml:lang="en" lang="en">password</span> inserita e la <span xml:lang="en" lang="en">password</span> reinserita non coincidono';
            $errore = self::isValidPassword($pass1);
            if($errore != '')
                return $errore;
            $cogn = strtolower($cogn);
            $nom = strtolower($nom);
            
            $db = new DB_handler();
            
            if($db->esisteNomeUtente($idu))
                return "Il nome utente scelto esiste già";
            
            if(!$db->addNewUser($cogn, $nom, $mail, $idu, $pass1))
                return 'E\' avvenuto un errore nella registrazione';
            unset($db);
            return '';
        }
    public function isParticipant($idev){//Metodo che ritona true se l'utene partecipa all'evento $idev
            $dom = new domoperations($this->filexml);
            return $dom->esistesimpletag('partecipazioni',0,'partecipazione', $idev);
        }
    public function partecipa($idev){//Metodo che aggiunge $idev alle sue partecipazioni ad eventi, ritorna true in caso di successo
        if(!$this->isParticipant($idev)){
            $dom = new domoperations($this->filexml);
            $str = "\n<partecipazione>".$idev.'</partecipazione>';
            $ev = new evento($idev);
            return $dom->appendChild('partecipazioni',0,$str) && $ev->nuovaPartecipazione($this->nome_utente); 
        }
        return false;
        }
    public function ritiraPartecipazione($idev){//metodo che permette all'utente di ritirare la sua partecipazione all'evento $idev, ritorna true in caso di successo
            $dom = new domoperations($this->filexml);
            $ev = new evento($idev);
            return $dom->eliminaSimpleTags('partecipazioni',0,'partecipazione', $idev) && $ev->togliPartecipazione($this->nome_utente);
        }
    public function caricaNuoveFoto($idev,$foto,$fotoName,$fotoDesc){//metodo che permette all'utente premium $idev, di aggiungere le nuove foto $foto con nomi $fotoName e descrizione $fotoDesc
        if($this->isPremium()){
            $event = new evento($idev, $this->nome_utente);
            return $event->UploadFoto($this->password,$foto,$fotoName,$fotoDesc, true);
        }
        return false;
        }
    public function deleteFotoEv($idev, $indexfoto){//Metodo che permette all'utente premium di eliminare la foto $indexfoto del suo annuncio $idev, ritorna true in caso di successo
        if($this->isPremium()){
            $event = new evento($idev, $this->nome_utente);
            return $event->eliminaFoto($this->password,$indexfoto);
        }
        return false;
        }
    public function deleteEvento($idev){//Metodo che permette all'utente premium premium di eliminare il suo annuncio $idev, ritorna true in caso di successo
        if($this->isPremium()){
            $event = new evento($idev, $this->nome_utente); 
            return $event->rimuoviEventodaDB($this->password);
        }
        return false;        
     }
    public function cambioPassword($oldpassword, $newPassword){//Metodo che permette all'utente di cambiare la sua password di accesso, ritorna la stringa vuota in caso di successo, altrimenti ritorna l'errore avvenuto
        if(password_verify($oldpassword, $this->password)){
            $errore = self::isValidPassword($newPassword);
                if($errore != '')
                    return $errore;
                if ($this->dB->updatePasswordUtente($this->nome_utente, $this->password, $newPassword)){
                  $this->password = $newPassword;
                   return '';
                } 
                return 'Impossibile cambiare la password. &Egrave; avvenuto un errore fatale';
            }
        return 'La vecchia password inserita non è corretta';
    }
    public function updateInfo($cogn, $nom, $mail){//metodo che permette all'utente di aggiornare il suo cognome, nome e la sua mail, ritorna la stringa vuota incaso di successo, altrimenti ritorna l'errore avvenuto
        $errore = self::checkInfoPersonali($cogn, $nom, $mail, $this->nome_utente);
        if($errore != '')
            return $errore;
        return $this->dB->updateInfo($this->nome_utente,$this->password, $cogn, $nom, $mail) ? '':
            'Impossibile cambiare aggiornare il tuo profilo';
         
    }
    public function utentiIscritti($desc, $userType=-1, $keyword = ''){//metodo che permette all'utente amministratore di trovare un utente iscritto di tipo $userType, con nome, cognnome o nome utente contenente $keyword
    //e ottenere i risultati ordinati in modo decrescente o non ($desc).
    //Se l'utente non è amministratore allora la ricerca  effettuata sugli utenti di tipo 2 e 3 sono rispettivamente i suoi seguaci e quelli che segue
        $db = new DB_handler;
        $check = $this->admin && $userType == 2;
            if(!$check && ($userType == 2 || $userType==3)){
                $utenti = !$this->admin ? self::listSocial($this->nome_utente, $userType == 2) : $this->listRequests();
                    if($keyword != ''){
                        $utenti2 = array();
                        $keyword = mb_strtoupper($keyword, 'UTF-8');
                        foreach($utenti as $ut){
                            $utbis = mb_strtoupper($ut, 'UTF-8');
                            if(strpos($utbis,$keyword)!== false || strpos($this->dB->userCognome($ut),$keyword)!== false ||
                            strpos($this->dB->userNome($ut),$keyword)!== false)
                                $utenti2[] = $ut;
                        }
                        return $utenti2;
                    }
                return $utenti;
           }
            return $db->utentiIscritti($desc, $userType, $keyword);
        
        
        
    }
    public function catdiutenti($codice){//Metodo che ritorna la categoria di utenti che identificata dal codice $codice
        switch($codice){
            case 0 : return 'utenti premium';
            case 1 : return 'utenti non premium';
            case 2 : return $this->admin ? 'utenti bloccati' : 'utenti che ti seguono';
            case 3 : return !$this->admin ?'utenti che segui' : 'utenti che chiedono di essere premium';
            default : return 'utenti iscritti in totale';
            }
    }
    public static function isUtentePremium($ut){//funzione che ritorna true se $ut è un utente premium
        $db = new DB_handler;
        return $db->isUtentePremium($ut);
    }
    public static function profiloUtente($nickname){ //funzione che restituisce in frammento HTML, il profilo dell'utente $nickname se esiste, con vari span e div con id verranno modificati dal controller per mostrare ulteriori informazioni
        $db = new DB_handler;
        $str = '';
        if(!$db->esisteNomeUtente($nickname))
            return $str;
        
        $str = $str."\n<span id=\"first\"></span>\n<h2>Profilo dell'utente $nickname</h2>\n<span id=\"navigazione\"></span><div id=\"infoUt\">\n";
        $str = $str."<h3>Informazioni su $nickname </h3><ul>\n";
        $str = $str."<li><h4>Nome utente :</h4>\n <p>".$nickname."</p></li>\n";
        $str = $str."<li><h4>Cognome :</h4>\n <p>".$db->userCognome($nickname)."</p></li>\n";
        $str = $str."<li><h4>Nome :</h4>\n <p>".$db->userNome($nickname)."</p></li>\n";
        $str = $str."<li><h4>Tipo utente :</h4>\n <p>".( self::isUtentePremium($nickname) ? 'utente premium' : 'utente non premium'). ". <span id=\"spiega\"></span> </p> </li>\n";
        $str = $str."</ul></div>";
        $str = $str."\n<div id=\"social\"></div>\n<span id=\"gestUt\"></span>\n";
        return $str;
    }
    public function totNews($tipoNotizia=''){
        $dom = new domoperations($this->newsxml);
        $tot = $dom->totTag('notizia');
        $tot2 = 0;
        $tot3=0;
        while($tot2 < $tot){
            $data = $dom->textoftags('notizia',$tot2,'data', 0);
            if(!DB_handler::isDateOld($data, $this->ultimoAccesso)){
                $aggiungi = true;
                if(($tipoNotizia != ''&& $dom->textoftags('notizia',$tot2,'tipo', 0) == $tipoNotizia) || $tipoNotizia == '')
                        ++$tot3;
                 ++$tot2; 
            }
            else {
                $dom->eliminaTag('lista',0,'notizia',$tot2);
                --$tot;  
            }
        }
        return $tot3;
    }
    public function lastNewsHtmlString($tipoNotizia = ''){//Metodo che restituisce in frammento HTML, la lista delle ultime notizie per l'utente connesso, e il numero totale delle notizie trovate. Se $tipoNotizia !='' vengono mostrate le notizie di tipo $tipoNotizia, altrimenti tutte
        $dom = new domoperations($this->newsxml);
        $tot = $this->totNews();
        $tot2 = 0;
        $str = '';
        for($i = 0; $i < $tot; ++$i){
            $aggiungi = true;
                if($tipoNotizia != ''){
                    if($dom->textoftags('notizia',$i,'tipo', 0) != $tipoNotizia)
                        $aggiungi = false;
                }
                if($aggiungi){
                    $Tdesc = $dom->giveNodesofmixedTagsasXml('notizia',$i,'desc', 0);
                    $Tdesc = str_replace('<count/>', $tot2+1, $Tdesc);
                    $str = $str."<li>\n".$Tdesc."\n</li>\n";
                    ++$tot2;
                }
        }
        $tipo = '';
        if( array_key_exists ($tipoNotizia ,utente::tipidiNotizie )){
            $tipo = 'di tipo: <em>'. utente::tipidiNotizie[$tipoNotizia].'</em>';
        }
        else $tipo = 'di tipo: <em>qualsiasi</em>';
        $com = '<span id="comment"></span>';
        if($tot2 == 0)
            return array("<p><strong>Non ci sono nuove notizie $tipo, dal tuo ultimo accesso del giorno $this->ultimoAccesso</strong></p>\n$com", $tot2);
        $str = "\n<p><strong>Ci sono $tot2 nuove notizie $tipo, che risalgono dal tuo ultimo accesso del giorno $this->ultimoAccesso</strong></p>\n$com\n<ul id=\"risultlist\">\n".$str;
        $str = $str . "</ul>\n"; 
        return array($str, $tot2);
    }
    public function setQuanrantenaEv($idev, $value, $motivo){//Metodo che permette all'utente amministratore di blocare o sbloccare (variabile $value a true o a false), l'annuncio $idev, con motivi $motivo, ritorna true in caso di successo
        if($this->admin){
            if($this->dB->updateblockedevent($idev, $this->nome_utente,$this->password,$value,$motivo)){
                $ev = new evento($idev);
                $ev->setLastQuarantine(date('d\/m\/Y'), $this->nome_utente, $this->password);
                return true;
            }       
        }
        return false;
    }
    public function notificaUtente($idu, $messaggio,$href, $tiponotifica = -1){//Metodo che permette all'utente connesso,  di notificare l'utente $idu con $messaggio su una certa sua attività ti tipo $tiponotifica, ritorna true in caso di successo
        if($this->isConnected()){
            $filexml = $this->dB->giveUtenteNewsFilexml($idu);
            $dom = new domoperations($filexml);
            $data = date('d\/m\/Y');
            $messaggio = "<notizia>\n<tipo>$tiponotifica</tipo>\n<data>$data</data>\n<desc><p><a href=\"$href\"><strong>In data $data - Notizia <count/>:</strong> ".$messaggio."</a></p></desc>\n</notizia>\n";
            return $dom->appendChild('lista', 0, $messaggio);
        }
        return false;
    }
    public function notificaSeguaci($messaggio,$href, $tiponotifica = -1){//Metodo che permette all'utente connesso,  di notificare tutti i suoi seguaci con $messaggio su una certa sua attività ti tipo $tiponotifica, ritorna true in caso di successo
        if($this->isConnected()){
            $dom = new domoperations($this->social);
            $text = $dom->textoftags('seguaci',0,'user');
            foreach($text as $user)
            $this->notificaUtente($user,$messaggio,$href,$tiponotifica);
            return true;
        }
        return false;
    }
    public function seguiUtente($idu){//Metodo che permette all'utente connesso di seguire l'utente $idu, aggiungendolo quindi alla sua lista degli utenti che segue, ritorna true in caso di successo
        if($this->isConnected() && $this->nome_utente != $idu){
            if(!$this->isSeguace($idu)){
            $filexml = $this->dB->giveUtenteSocialFilexml($idu);
            $dom = new domoperations($filexml);
            $dom2 = new domoperations($this->social); 
            return $dom->appendChild('seguaci',0,"<user>$this->nome_utente</user>\n") && 
            $dom2->appendChild('segue',0,"<user>$idu</user>\n");
            }
            return true;
        }
        return false;
    }
    public function smettiSeguire($idu){//Metodo che permette all'utente connesso di smettere di seguire l'utente $idu, togliendolo quindi alla sua lista degli utenti che segue, ritorna true in caso di successo
        if($this->isConnected()){
            $filexml = $this->dB->giveUtenteSocialFilexml($idu);
            $dom = new domoperations($filexml);
            $dom2 = new domoperations($this->social); 
            $dom->validateOnParse = true;
            $dom2->validateOnParse = true;
            $dom->eliminaSimpleTags('seguaci',0,'user', $this->nome_utente);
            return $dom2->eliminaSimpleTags('segue',0,'user', $idu);
        }
        return  false;
    }
    public function isSeguace($idu){//Metodo che ritorna true se l'utente connesso segue l'utente $idu
        if($this->isConnected()){
            $dom = new domoperations($this->social);
            return $dom->esistesimpletag('segue',0, 'user', $idu);
        }
        return false;
    }
    public function tiSegue($idu){//Metodo che ritorna true se l'utente $idu segue l'utente connesso
        if($this->isConnected()){
            $dom = new domoperations($this->social);
            return $dom->esistesimpletag('seguaci',0, 'user', $idu);
        }
        return false;
    }
    public static function listSocial($idu, $followers){//funzione ritorna la lista dei seguaci, se $followers=true, altrimenti degli utenti seguiti, dell'utente $idu
            $dB = new DB_handler;
            $dom = new domoperations($dB->giveUtenteSocialFilexml($idu));
            return $dom->textoftags($followers ? 'seguaci' : 'segue',0,'user');
    }
    public function removeSegnalato($idev){//Metodo che permette all'utente amministratore di rimuove l'annuncio $idev dai segnalati. Ritorna true in caso di successo
        if($this->admin){
            return $this->dB->Toglidasegnalati($idev, $this->nome_utente, $this->password);
        }
        return false;
    }
    public function bloccaUtente($idu,$value,$motivo, $bloccaev){//Metodo che permette all'utente amministratore di bloccare($value=true) con motivo $motivo, o sbloccare ($value=false) l'utente $idu
    //Se $blockeve= true && $value=true allora vengono bloccati assieme all'utente anche i suoi eventuali annunci pubblicati
    //Se $blockeve= false && $value=true allora non vengono bloccati assieme all'utente, i suoi eventuali annunci pubblicati
    //Se $blockevev= false && $value=false allora vengono sbloccati assieme all'utente anche i suoi eventuali annunci pubblicati bloccati
    //Se $blockev= true && $value=false allora non vengono sbloccati assieme all'utente , i suoi eventuali annunci pubblicati bloccati
     if($this->admin && $this->dB->bloccaUtente($idu,$value,$motivo, $this->nome_utente, $this->password)){
                $ids = $this->dB->giveListEventsIds($idu);
                foreach($ids as $id){
                    $mot = $value ? 'Il pubblicatore di questo annuncio è stato bloccato per violazione delle nostre regole.':'';
                    if($value && $bloccaev)
                    $this->setQuanrantenaEv($id, TRUE, $mot);
                    if(!$value && !$bloccaev)
                        $this->setQuanrantenaEv($id, FALSE, $mot);
                }
            return $value == true ? $this->removefromrequest($idu) : true;
        }
        return false;
    }
    public static function isUtenteBlocked($idu, $password=''){//funzione che ritorna true  se l'utente $idu con eventuale $password è stato bloccato
        $db = new DB_handler;
        return $db->isUtenteBlocked($idu, $password);
    }
    public static function MotivoUtenteBlocked($idu){//funzione che ritorna il motivo del blocco dell'utente $idu
        $db = new DB_handler;
        return $db->motivoBlockedUtente($idu);
    }
    public function mettiinprimopiano($idev, $value){//Metodo che permette all'utente amministratore di aggiungere($value=true) o rimuovere($value=false) l'annuncio $idev in primo piano, ritorna true in caso di successo
        return $value ? $this->dB->mettiinprimopiano($idev, $this->nome_utente, $this->password):
        $this->dB->ToglidaPrimoPiano($idev, $this->nome_utente, $this->password);
    }
    public function eliminautente($idu, $idadmin=''){//Metodo che permette all'utente connesso di autoeliminarsi, oppure all'amministratore di eliminare un utente, ritorna true in caso di successo
        if($this->admin || $this->nome_utente == $idu){
            $utenti = self::listSocial($idu,false);
            foreach($utenti as $ut){
                $this->smettiSeguire($ut);
            }
            if($this->dB->isUtentePremium($idu)){
                $eventi = self::EventiPubblicati($idu);
                foreach($eventi as $ev){
                    $ev->rimuoviEventodaDB($this->password);
                }
                rmdir($this->dB->giveEventiUploadsFolder($idu));
            }
            return $this->dB->deleteUtente($idu, $this->password, $idadmin) && $this->removefromrequest($idu);
        }
        return false;
    }
    public function makepremium($idu,$value){//metodo che permette all'utente amministratore di rendere $idu premium($value=true) o di togliergli i privileggi se lo era già($value=false), ritorna true in caso di successo
        return $this->admin && $this->removefromrequest($idu)&&$this->dB->makepremium($idu,$value,$this->nome_utente,$this->password);
    }
    public function haChiestoPremium(){//Ritorna true se l'utente ha una richiesta premium in sospeso
        $dom = new domoperations($this->dB->giveFilePremiumrichiesta($this->nome_utente));
        return $dom->esistesimpletag('richieste',0,'utente', $this->nome_utente);
    }
    public function premiumrequest(){//metodo che permette all'utente non premium di fare richiesta
        $dom = new domoperations($this->dB->giveFilePremiumrichiesta($this->nome_utente));
        if(!$dom->esistesimpletag('richieste',0,'utente', $this->nome_utente) && !$this->isPremium())
            return $dom->appendChild('richieste', 0, "<utente>$this->nome_utente</utente>\n");
        return false;
    }
    public function removefromrequest($idu){//annulla la richiesta premium dell'utente $idu se l'ha fatta, e ritorna true in caso di successo
        $dom = new domoperations($this->dB->giveFilePremiumrichiesta($this->nome_utente));
        if($dom->esistesimpletag('richieste',0,'utente', $idu) && ($this->nome_utente == $idu || $this->admin))
            return $dom->eliminaSimpleTags('richieste',0,'utente', $idu);
        return true;
    }
    public function listRequests(){//ritorna in un array la lista degli utenti che hanno fatto domanda per essere premium, se e solo se l'utente connesso che la chiede è un utente amministratore
        if($this->admin){
        $dom = new domoperations($this->dB->giveFilePremiumrichiesta($this->nome_utente));
        return $dom->textoftags('richieste',0,'utente');
        }
        return array();
    }
    
        
}

?>

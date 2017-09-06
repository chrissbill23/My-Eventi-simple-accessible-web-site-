<?php
require_once('DB_handler.php');
require_once('domoperations.php');
/*LA CLASSE EVENTO E' UNA CLASSE I CUI OGGETTI RAPPRESENTANO ANNUNCI DI EVENTI PUBBLICATI*/
class evento {
      
    private $dB;
    private $id;
    private $filexml = '';
    private $idowner = '';
    private $datapubl = '';
    private $dataev = '';
    private $titolo = '';
    private $esiste = false;
    private $blocked = false;
    private $motivoblocked = '';
    public static $maxFoto = 5;
    public static $allowedFoto = array('gif','png' ,'jpg','jpeg');
    public static $formatiFotoconabbr = array('gif' =>'Graphics Interchange Format','png'=>'Portable Network Graphics' ,
                                        'jpg'=>'Joint Photographic Group','jpeg'=>'Joint Photographic Experts Group');
    public static $maxSizeFoto = 1000000;
    public static $maxLengthDescFoto = 70;
    public static $maxEtichette = 5;
    public static $maxDaysInQuarantine = 15;
    public function __construct($id, $idowner = ''){
        $this->dB = new DB_handler;
        $this->id = $id;
        $this->idowner = $idowner != '' ? $idowner : $this->dB->giveOwnerOfEvent($id); 
        $this->filexml =  $this->dB->giveEventoPathFilexml($id, $this->idowner);
        
        if($this->idowner != '' && $this->filexml != ''){ 
            $this->esiste = true;
            $this->blocked =  $this->dB->isEventBlocked($id);
            $this->motivoblocked =  $this->dB->giveMotiveEventBlocked($id);
            $dom = new domoperations($this->filexml);
            $this->titolo = $dom->uniquesimpletagTextValue('title');
            $this->dataev = $dom->simpletagbyidTextValue('dataSvolgimento');
            $this->datapubl = $dom->simpletagbyidTextValue('pubblicazione');
        }
        
        
    }
    public function dataPub(){//metodo che ritorna la data di pubblicazione dell'annuncio
        return $this->datapubl;
    }
    public function dataEvento(){//memetodo che ritorna la data di svolgimento dell'evento
        return $this->dataev;
    }
    public function isBlocked(){ //metodo che ritorna true se l'annuncio è stato vloccato
        return $this->blocked;
    }
    public function MotivoBlocked(){//metoto che ritorna il motivo percui l'annuncio è stato bloccato
        return $this->motivoblocked;
    }
    public function Titolo(){//metodo che ritorna il titolo dell'annuncio
        return $this->titolo;
    }
    public function giveId(){//metodo che ritorna l'id  dell'annuncio
        return $this->id;
    }
    public function giveOwner(){//metodo che ritorna il pubblicatore dell'annuncio
        return $this->idowner;
    }
    public function giveTipo(){//metodo che ritorna il tipo di evento trattato dall'annuncio
        if(!$this->esiste)
            return '';
        $dom = new domoperations($this->filexml);
        return  $dom->uniquesimpletagTextValue('tipo');
    }
    public function giveOra(){//metodo che ritorna l'ora dell'evento
        if(!$this->esiste)
            return '';
        $dom = new domoperations($this->filexml);
        return  $dom->uniquesimpletagTextValue('ora');
    }
    public function giveMinuto(){//metodo che ritorna il minuto in cui avverrà l'evento
        if(!$this->esiste)
            return '';
        $dom = new domoperations($this->filexml);
        return  $dom->uniquesimpletagTextValue('minuto');
    }
    public function giveCateg(){//metodo che ritorna la categoria  di evento trattato dall'annuncio
        if(!$this->esiste)
            return '';
        $dom = new domoperations($this->filexml);
        return  $dom->uniquesimpletagTextValue('categ');
    }
    public function givebreveDesc($paragrafOn = false){//metodo che ritorna l'introduzione  dell'annuncio
        if(!$this->esiste)
            return '';
        $dom = new domoperations($this->filexml);
        if(!$paragrafOn)
            return $dom->uniquemixedtagTextValue('breveDesc'); 
        return $dom->uniquemixedtagValueasXml('breveDesc');
    }
    public function giveDesc($paragrafOn = false){//metodo che ritorna la descrizione  dell'annuncio
        if(!$this->esiste)
            return '';
        $dom = new domoperations($this->filexml);
        if(!$paragrafOn)
            return $dom->uniquemixedtagTextValue('desc'); 
        return $dom->uniquemixedtagValueasXml('desc');
       
    }
    public function givedDenomurb(){//metodo che ritorna la denominazione urabana dove avrà luogo l'evento
        if(!$this->esiste)
            return '';
        $dom = new domoperations($this->filexml);
        return  $dom->simpletagbyidTextValue('denomvia');
    }
    public function giveVia(){//metodo che ritorna il nome dell'indirizzo dove avrà luogo l'evento
        if(!$this->esiste)
            return '';
        $dom = new domoperations($this->filexml);
        return  $dom->simpletagbyidTextValue('viaevento');
    }
    public function giveCitta(){//metodo che ritorna la città dove avrà luogo l'evento
        if(!$this->esiste)
            return '';
        $dom = new domoperations($this->filexml);
        return  $dom->simpletagbyidTextValue('comevento');
    }
    public function giveProv(){//metodo che ritorna la provincia dove avrà luogo l'evento
        if(!$this->esiste)
            return '';
        $dom = new domoperations($this->filexml);
        return  $dom->simpletagbyidTextValue('provevento');
    }
    
    public function countEtich(){//metodo che ritorna il totale di etichette dell'annuncio
        if(!$this->esiste)
            return 0;
        $dom = new domoperations($this->filexml);
        return  $dom->totTag('etichetta');
    }
    public function giveEtich($index){//metodo che restituisce l'etichetta $index
        if(!$this->esiste)
            return 0;
        $dom = new domoperations($this->filexml);
        return  $dom->textoftags('etichette',0,'etichetta', $index);
    }
    public function countFoto(){//metodo che restituisce il totale foto dell'annuncio
        if(!$this->esiste)
            return 0;
        $dom = new domoperations($this->filexml);
        return  $dom->totTag('img');
    }
    public function giveFoto($index){//metodo che restituisce in tag img la foto $index dell'annuncio
        $dom = new DOMDocument;
        $dom->validateOnParse = true;
        $dom->load($this->filexml);
        $foto = $dom->getElementsByTagName('img')->item($index);
        return $dom->saveXML($foto);
    }
    public function giveLastModifiedDate(){//metodo che restituisce la data di ultima modifica del'annuncio
        if(!$this->esiste)
            return '';
        $dom = new domoperations($this->filexml);
        return  $dom->uniquesimpletagTextValue('lastmodified');
    }
    public function giveLastQuarantineDate(){//metodo che restituisce la data dell'ultimo blocco dell'annuncio
        if(!$this->esiste)
            return '';
        $dom = new domoperations($this->filexml);
        return  $dom->uniquesimpletagTextValue('lastquarantine');
    }
    public function esiste(){//metodo che restittuisce true se l'annuncio esiste
        return $this->esiste;
    }
    
    public function isOld(){//metodo che restittuisce true se l'annuncio è vecchio
       return DB_handler::isDateOld($this->dataev, date('d\/m\/Y'));
    }
    public function rimuoviEventodaDB($password, $idadmin=''){//metodo che elimina l'annuncio da parte del proprietario o dell'amministratore. restituisce true in caso di successo
        if($this->dB->isOwnerOfEvent($this->idowner, $password, $this->id) ){
            $dom = new domoperations($this->filexml);
            return $dom->eliminaTagimg('foto',0) && $this->dB->deleteEvento($this->id, $this->idowner,$password, $this->filexml);
        }
        if($this->dB->esisteadmin($idadmin,$password)){
            $dom = new domoperations($this->filexml);
            return $dom->eliminaTagimg('foto',0) && $this->dB->deleteEvento($this->id, $idadmin,$passwordOwner, $this->filexml); 
        }
        return false;
    }
    private function modificaData($g, $m, $a, $dom){//metodo che modifica la data di svolgimento dell'evento, restituisce true in caso di successo
        if(checkdate((int)$m, (int)$g, (int)$a)){ 
        if($dom->isOpen()){
            return $dom->modificaSimpleTagById('dataSvolgimento',"$g/$m/$a");
        }
        }
        return false;
    }
    private function modificaIndirizzo($denom, $via, $comune, $prov, $dom){//metodo  che modifical'indirizzo dove avrà luogo l'evento, restituisce true in caso di successo
        
        if($dom->isOpen()){
        return $dom->modificaSimpleTagById('denomvia',$denom) &&
        $dom->modificaSimpleTagById('viaevento',$via)
        && $dom->modificaSimpleTagById('comevento', $comune)
        && $dom->modificaSimpleTagById('provevento',$prov);
        }
        return false;
        
    }
    
    
    public function editInfoEvent($passwordOwner, $tit, $b_desc, $desc, $tipo, $categ, $g, $m, $a,$o, $min,$denom, $via, $comune, $prov, $etich, $edit=false){
        //metodo che permette al pubblicatore di modificare le informazioni relative all'annuncio.
        
       if($this->dB->isOwnerOfEvent($this->idowner, $passwordOwner, $this->id)){ 
        $db = new DB_handler;
        $tit = DB_handler::escapeCharacters($tit);
        $b_desc = DB_handler::escapeCharacters($b_desc);
        $desc = DB_handler::escapeCharacters($desc);
        $denom = DB_handler::escapeCharacters($denom);
        $via = DB_handler::escapeCharacters($via);
        $comune = DB_handler::escapeCharacters($comune);
        $prov = DB_handler::escapeCharacters($prov);
        $b_desc = self::InsertParagrafIntostring($b_desc);
        $desc = self::InsertParagrafIntostring($desc);
        $tipo = $db->giveNomeTypeEvent($tipo);
        $categ = $db->giveNomeCatEvent($categ);
        
        $dom = new domoperations($this->filexml);
        
        $dom->modificaElementTag('breveDesc',$b_desc);
        $dom->modificaElementTag('desc',$desc,0); 
        $dom->modificaSimpleTag('title',$tit,0);
        $dom->modificaSimpleTag('tipo',$tipo,0);
        $dom->modificaSimpleTag('categ',$categ,0);
        $dom->modificaSimpleTag('ora',$o,0);
        $dom->modificaSimpleTag('minuto',$min,0);
        $dom->modificaSimpleTag('lastmodified',date('d\/m\/Y'),0);
        if(!$edit)
        $dom->modificaSimpleTagById('pubblicazione',date('d\/m\/Y'));
        
        $str = ''; $tag ='';
        foreach($etich as $value){
            if($value != ''){
                $value = substr($value, 0, DB_handler::maxLengthEtichetta);
                $str = $str."<etichetta>$value</etichetta>\n";
                $tag = $tag." $value ";
            }
        }
        if($str != ''){
           $dom->modificaElementTag('etichette',$str);
           $this->dB->updateTagEvento($this->id, $this->idowner,$passwordOwner,$tag);
        }
       
        $this->modificaData($g,$m,$a,$dom);
        $this->modificaIndirizzo($denom,$via, $comune, $prov, $dom);
        $dom->modificaSimpleTag('lastmodified',date('d\/m\/Y'),0);
        return true;
       }
       else echo 'FORBIDDEN ACCESS';
       return false;
        
    }
    public static function InsertParagrafIntostring($str){//funzione che sostituisce ai caratteri tab, i tag <p>
        $str = "<p>$str";
        $str = preg_replace('/\n/', '</p><p>', $str);
        $str = "$str</p>";
        $str = preg_replace('/<p><\/p>/', '', $str);
        return $str;
    }
    public function viewHtmlStringInfoEvent($id){//metodo che restituisce un frammento HTML che rappresenta i detagli sull'annuncio
        
        if(!$this->esiste)
            return '';
        $db = new DB_handler;
        $dom = new domoperations($this->filexml);
        
        if(!$dom->isOpen())
            return '';
        $denom = $dom->simpletagbyidTextValue('denomvia'); 
        $via = $dom->simpletagbyidTextValue('viaevento');
        $com = $dom->simpletagbyidTextValue('comevento');
        $prov = $dom->simpletagbyidTextValue('provevento');
        $tipo = $dom->uniquesimpletagTextValue('tipo');
        $categ = $dom->uniquesimpletagTextValue('categ');
        $bdesc = $dom->uniquemixedtagValueasXml('breveDesc');
        $descr = $dom->uniquemixedtagValueasXml('desc');
        $listFoto = $dom->uniquemixedtagValueasXml('foto');    
        if($listFoto == '')
            $listFoto = '<p>Nessuna foto fornita.</p>';
        $str = "<div id=\"titolo\">\n<h2>Titolo: $this->titolo</h2>\n</div>\n".
               "<div id=\"bdesc\">\n<h3>Introduzione :</h3>\n".$bdesc."\n</div>\n".
               "<div id=\"desc\">\n<h3>Descrizione dell'evento:</h3>\n".$descr."\n</div>\n".
               "<div id=\"infoev\"><h3>Informazioni</h3>\n".
               "<span id=\"datapub\"></span>\n".
               "<h4>Tipo di evento : </h4><p>$tipo</p>\n".
               "<h4>Data e Ora dell'evento:</h4> <p> giorno $this->dataev alle ore ".$this->giveOra().' : '.$this->giveMinuto()."</p>\n".
               "<h4>Indirizzo</h4>\n".
               "<p>L'evento si terr&agrave; a : $denom $via ,  $com , in provincia di $prov</p>\n".
               "<div id=\"partecipazioni\"></div>\n</div>\n".
               "<div id=\"fotoev\"><h3>Alcune foto:</h3>\n".
               $listFoto."\n <span id=\"messfoto\"></span>\n</div>\n</div>\n ";
       $str ="\n<span id=\"navigazione\"></span>\n".$str;
       if($this->isBlocked()){
         $str = '<span id="blockedevent"></span>'. $str;   
       }
       elseif( $this->isOld() ){
           $str = "\n <p><strong>Attenzione:</strong>". 
                    'questo evento ha gi&agrave; avuto luogo. Non &egrave; pi&ugrave; possibile partecipare.</p>'."\n<span id=\"navigazione\"></span>\n". $str;
       }
       $str = '<div id="eventDetail">'.$str;
            return $str;
       
    }
    public static function formatiFotoWithAbbr(){//funzione che restituisce i formati foto supportati dentro tag abbr
        $str = '';
        foreach(self::$formatiFotoconabbr as $key=>$value){
            $str = $str.', <abbr xml:lang="en" lang="en" title="'.$value."\">$key</abbr> ";
        }
        return $str;
    }
    public static function maxsizeFoto(){//funzione che restituisce in stringa la dimensione massima supporta per ogni foto
        
        return (self::$maxSizeFoto / 1000000).' <span xml:lang="en" lang="en" >Megabyte</span>';
    }
    public function UploadFoto($passwordOwner,$foto,$fotoName,$fotoDesc, $editmode = false){
        //Metodo che carica nuove foto $foto con nomi $fotoName e descrizione $fotoDesc, all'annuncio di foto, Se e solo se il chiamante è un utente proprietario.
        //se $editmode=true allora siamo in fase modifica e il metodo carica le foto conformi alle regole, e ritorna un array vuoto se tutte le foto sono state caricate, o un array di stringhe di errori rilevati sulle foto non caricate
        //se $editmode=false allora siamo in fase di creazione di un nuovo annuncio, il metodo carica le foto se sono tutte conformi alle regole, e in questo caso ritorna la stringa vuota, altrimenti non carica nessuna e ritorna il messaggio avvenuto
        if($this->dB->isOwnerOfEvent($this->idowner, $passwordOwner, $this->id)){ 
        if(!isset($foto))
            return '';
        $tot = $this->countFoto(); 
        if( $tot >= evento::$maxFoto)
            return $editmode ? array('limite massimo di foto raggiunto') : 'limite massimo di foto raggiunto';
        $totale = sizeof($fotoName);
        if($totale == 0){
            return $editmode ? array('Non ha caricato nessuna foto') : '';
        }
        $i= 0; 
        $errore = array();
        $errori2 = array_fill ( 0 , sizeof($fotoName) , FALSE);
        $target_file = array();
        foreach($fotoName as $value){
        if($tot + $i < evento::$maxFoto){
            $check = true;
            if(preg_match('/^[^a-zA-Z]?$/',$fotoDesc[$i])){
                if($editmode){
                    $errore[] = 'Alcune foto non sono state caricate per mancata descrizione chiara e comprensibile';
                    $check = false;
                    $errori2[$i] = true;
                }
                else return 'E\' richiesta una descrizione chiara e comprensibile per ogni foto caricata';
            }
            if($check == true && self::$maxLengthDescFoto < strlen($fotoDesc[$i]) ){
                if($editmode){
                    $errore[] = 'Alcune foto non sono state caricate per descrizione descrizione troppo lunga. Ricordiamo che il massimo numero di caratteri è '.self::$maxLengthDescFoto;
                    $check = false;
                    $errori2[$i] = true;
                }
                else return 'La descrizione di alcune foto è troppo lunga. Ricordiamo che il massimo numero di caratteri è '.self::$maxLengthDescFoto;  
            }
            if($check == true){
                $check = getimagesize($foto[$value]['tmp_name']);
                if($check == false || !in_array(pathinfo($foto[$value]['name'], PATHINFO_EXTENSION),self::$allowedFoto)){
                    if($editmode){
                    $errore[] = 'Alcune foto non sono state caricate per formato non corretto. I formati accettati sono '.(self::formatiFotoWithAbbr());
                    $check = false;
                    $errori2[$i] = true;
                    } 
                    else return 'Alcune foto caricate non hanno il formato corretto. I formati accettati sono '.(self::formatiFotoWithAbbr());
                    
                } 
                if ($foto[$value]["size"] > self::$maxSizeFoto){
                    if($editmode){
                    $errore[] = 'Alcune foto non sono state caricate perché troppo grandi. Ricordiamo che la dimensione massima è di '.self::maxsizeFoto();
                    $check = false;
                    $errori2[$i] = true;
                    }
                    else
                    return 'Alcune foto caricate pesano troppo . Ricordiamo che la dimensione massima è di '.self::maxsizeFoto();
                }
                if($check == true)
                $target_file[] = $this->dB->giveEventiUploadsFolder($this->idowner).$this->id . '_' .($tot+$i).".".
                                pathinfo($foto[$value]['name'], PATHINFO_EXTENSION);
            }
        ++$i; 
        }
        }
    $ind = $index2 = 0;
    $str=''; $error = false;
        foreach($fotoName as $value){
            if($ind < $i){
                if($editmode){
                    if($errori2[$ind] == false){
                        if(!move_uploaded_file($foto[$value]['tmp_name'], $target_file[$index2])){
                            $errori2[$ind]= true;
                            $errore[]='Non è stato posibile salvare alcune foto per problemi tecnici. Ci scusiamo.';
                        }
                        else $str = $str."<img src=\"$target_file[$index2]\" alt=\"$fotoDesc[$ind]\"/>\n";
                        ++$index2;
                    }
                        ++$ind;
                }
                elseif ($error ==false){
                if(!move_uploaded_file($foto[$value]['tmp_name'], $target_file[$ind]))
                    $errore = true;
                else{
                    $str = $str."<img src=\"$target_file[$ind]\" alt=\"$fotoDesc[$ind]\"/>\n";
                }
                
            ++$ind;
            }
            }
        } 
        if($error == true){
            foreach($target_file as $value)
                unlink($value);
            return 'E\' avvenuto un errore nel caricamento';
        }
        else{
            $dom = new domoperations($this->filexml);
            if($str != '')
            $dom->appendChild('foto',0,$str);
            $dom->modificaSimpleTag('lastmodified',date('d\/m\/Y'),0);
            return $editmode ? $errore : '';
        }
        }
        return 'FORBIDDEN ACCESS';
    }
    public function eliminaFoto($passwordOwner,$index){//metodo che permette al pubblicatore di eliminare la foto $index del'annuncio, restituisce true in caso di successo
        if($this->dB->isOwnerOfEvent($this->idowner, $passwordOwner, $this->id)){
            $dom = new domoperations($this->filexml);
            return $dom->eliminaTagimg('foto',0,$index) && $dom->modificaSimpleTag('lastmodified',date('d\/m\/Y'),0);
        }
        return false;
    }
    public static function primoPianoEvents(){//funzione che restituisce un array di oggetti evento che sono gli annunci in primo piano
        $eventi = DB_handler::primopiano();
        $ids = array();
        foreach($eventi as $value){
            $ids[] = new evento($value);
        }
        return $ids;
    }
    public static function blockedEvents(){//funzione che restituisce un array di oggetti evento che sono gli annunci bloccati
        $db = new DB_handler;
        $eventi = $db->giveListBlockedEventsIds();
        $ids = array();
        foreach($eventi as $value){
            $ids[] = new evento($value);
        }
        return $ids;
    }
    public static function specificEvents($type, $categ, $keyword, $city, $publisher,$datafrom, $dataTO,$datafrompub,$dataTOpub, $searchinmyannunci=false){
       //funzione che restituisce un array di eventi in base al tipo $type, la categoria $categ, la città $city, il pubblicatore $publisher con data di svolgimento da $datafrom a $dataTo, e data di pubblicazione da $datafrompub a $dataTOpub
        $eventi = array();
        $db = new DB_handler;
        $d1 = DateTime::createFromFormat('d/m/Y', $datafrom);
        $d2 = DateTime::createFromFormat('d/m/Y', $dataTO);
        $d3 = DateTime::createFromFormat('d/m/Y', $datafrompub);
        $d4 = DateTime::createFromFormat('d/m/Y', $dataTOpub);
        if($d1 === false || $d2 ===false || $d3 === false || $d4 ===false || DB_handler::isDateOld($dataTO, $datafrom) ){
            return $eventi;   
        }
        $ids = $db->searchEvents($type, $categ, $keyword, $city, $publisher, $searchinmyannunci);
        foreach($ids as $value){
            $ev = new evento($value);
            $aggiungi = true;
            if(DB_handler::isDateOld($ev->dataEvento(), $datafrom) || DB_handler::isDateOld($dataTO, $ev->dataEvento()) ||
               DB_handler::isDateOld($ev->dataPub(), $datafrompub) || DB_handler::isDateOld($dataTOpub, $ev->dataPub()))
                $aggiungi = false;
            if($aggiungi)
                $eventi[] = $ev;
        }
        return $eventi;
    }
    public function setLastQuarantine($date, $idadmin, $passwordadmin){//metodo che permette all'admin di aggiornare la data di ultimo blocco dell' annuncio. restituisce true in caso di successo
        if($this->dB->esisteAdmin($idadmin, $passwordadmin)){
        $dom = new domoperations($this->filexml);   
        $dom->modificaSimpleTag('lastquarantine',$date,0);
        }  
    }
    public static function eventiSegnalati(){//funzione che restituisce gli eventi segnalati in un array
        $db = new DB_handler;
        $ids = $db->eventisegnalati();
        $ev = array();
        foreach($ids as $value){
            $ev[] = new evento($value);
        }
        return $ev;
    }
    public function totPartecipazioni(){//metodo che restittuisce il numero di partecipazioni all'evento
        $dom = new domoperations($this->filexml);
        return $dom->totTag('partecipazione');
    }
    public function nuovaPartecipazione($idu){//metodo che aggiunge una nuova partecipazione, quella dell'utente $idu. Restituisce true in caso di successo
        $dom = new domoperations($this->filexml);
        return $dom->appendChild('partecipazioni',0,"\n<partecipazione>$idu</partecipazione>");
    }
    public function togliPartecipazione($idu){//metodo che toglie una  partecipazione, quella dell'utente $idu. Restituisce true in caso di successo
            $dom = new domoperations($this->filexml);
            return $dom->eliminaSimpleTags('partecipazioni',0,'partecipazione', $idu);
        }
    public function listaPartecipanti(){//metodo ristorna un array che rappresenta la lista dei partecipanti
        $dom = new domoperations($this->filexml);
        return $dom->textoftags('partecipazioni',0,'partecipazione');
    }
    public static function ordinaeventi($eventiarray, $datasvolg = true, $desc=true){//funzione statica che permette di ordinare gli eventi dentro l'array $eventiarray, in base alla data di svolgimento($datasvolg=true),
    //o pubblicazione($datasvolg=false), in modo decrescente o non($desc), e restituisce l'array ordinato
        $tot = sizeof($eventiarray);
        $data = array();
        for($i = 0; $i < $tot; ++$i){
            if($datasvolg)
            $data += array("$i" => $eventiarray[$i]->dataEvento());
            else  $data += array($i => $eventiarray[$i]->dataPub());
        }
        $eventssorted = array();
        $check = $desc ? uasort($data, array('DB_handler', 'isDateOld')) : uasort($data, array('DB_handler', 'isDateYoungOrEqual'));
        if($check){
            foreach($data as $key => $value){
                $eventssorted[] = $eventiarray[(int)$key];
            }
            return $eventssorted;
        }
        return $eventiarray;
    }
}

?>

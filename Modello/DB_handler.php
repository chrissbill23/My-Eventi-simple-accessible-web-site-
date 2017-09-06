<?php
require_once("config.php");
/*
LA CLASSE DB_handler E' UNA CLASSE I CUI OGGETTI SONO DEGLI INTERMEDIARI TRA IL DATABASE E GLI UTENTI DEL DATABAsE. PERMETTONO GRAZIE AI LORO METODI DI SVOLGERE OPERAZIONI DI LETTURA O DI MODIFICA( SE L'UTENTE HA I PERMESSI NECESSARI) DEL DATABASE
*/
class DB_handler {
      
    private static $conn;//connessione al database
    private $folderFilexmlUser = 'Modello/UsersFolder/';//path alla cartella che contiene le cartelle utenti
    private $tables = array('utentetable' =>'utente',
                            'eventoTable' => 'evento',
                            'tipoEventoTable' => 'tipoevento',
                            'categTable' => 'categoria');//nomi delle tabelle principali
    const MaxlengthCognomeUtente = 20;//Massima lunghezza della stringa per il cognome dell'utente
    const MaxlengthNomeUtente = 30;//Massima lunghezza della stringa per il nome dell'utente
    const MaxlengthUserName = 20;//Massima lunghezza della stringa per il nome utente dell'utente
    const MaxlengthPassword = 20;//Massima lunghezza della stringa password dell'utente
    const MinlengthPassword = 8;//Minima lunghezza della stringa password dell'utente
    const MaxlengthMail = 70;//Massima lunghezza della stringa per la mail dell'utente
    const maxLengthTitolo = 100;//Massima lunghezza della stringa per il titolo di un annuncio
    const maxLengthBrevDesc = array(50, 4);//Massima lunghezza in colonne per righe, dell'introduzione di un annuncio
    const motivisegnalato = array('Contenuto incomprensibile', 'Contenuto diffamatorio, razzista o sessista', 'Contenuto non adatto a minorenni',
                                    'Attività fraudolenta');//Principali motivi di segnalazione di un annuncio
    const maxLengthDesc = array(50, 20);//Massima lunghezza della descrizione di un annuncio
    const maxLengthEtichetta = 20;//Massima lunghezza della stringa per l'etichetta di un annuncio
    const maxSegnalazioni = 20;//Massimo numero di segnalazioni accettate su un annuncio
    const maxPrimoPiano = 6; // Massimo annunci che l'admin può mettere in primo piano
    
    private function filepersonaliUtente($idu, $password, $admin){//metodo di supporto che ritorna tutti i path ai tutti i file creati per l'utente $idu durante la sua prima registrazione. questa operazione è permessa all'utente $idu stesso e all'amministratore
        $a = array();
        $a[] = $this->giveUtentePathFilexml($idu,$password, $admin);
        $a[] = $this->giveUtenteNewsFilexml($idu);
        $a[] = $this->giveUtenteSocialFilexml($idu);
        return $a;
    }
    public static function init($db){//funzione per inizializzare la connessione al database
    self::$conn = $db;
    }
    private function userFolder($idu){//metodo di supporto che ritorna la cartella privata dell'utente $idu. questa operazione è permessa all'utente $idu stesso e all'amministratore
        if($this->esisteNomeUtente($idu))
            return $this->folderFilexmlUser.$idu;
        return '';
    }
    public static function isDateOld($date, $newdata){//funzione che ritorna true se la data $date è stretamente più vecchia della data $newdata
        $data = DateTime::createFromFormat("d/m/Y", $date);
        $newdate = DateTime::createFromFormat("d/m/Y", $newdata);
        if($data && $newdate){
            $old = false;
            if(((int)$data->format('Y') ) < ((int)$newdate->format('Y') ))
                return true;
            if(((int)$data->format('Y') ) == ((int)$newdate->format('Y') )){
                if(((int)$data->format('m') ) < ((int)$newdate->format('m') ))
                    return true;
                if(((int)$data->format('m') ) == ((int)$newdate->format('m') )){
                    if(((int)$data->format('d') ) < ((int)$newdate->format('d') ))
                    return true;
                }
                    
            }
            
        }
       return false;
    }
    public static function isDateYoungOrEqual($date, $newdata){//funzione che ritorna true se la data $date è stretamente più vecchia della data $newdata
        return !self::isDateOld($date,$newdata);
    }
    public static function escapeCharacters($stringa){ //funzione che inserisce gli escape characters nella $stringa e la restituisce
        $stringa = preg_replace('/&/', '&amp;', $stringa);//Questa istruzione deve essere prima delle succesive per non impedire il parsing degli altri escape
        $stringa = preg_replace('/"/', '&quot;', $stringa);
        $stringa = preg_replace('/\'/', '&apos;', $stringa);
        $stringa = preg_replace('/</', '&lt;', $stringa);
        $stringa = preg_replace('/>/', '&gt;', $stringa);
        return $stringa;
    }
    public function esisteUtenteConPassword($idu, $password, $blocked = FALSE, $admin = false){//metodo che ritorna nome utente e password cifrata dell'utente $idu se esiste con la password non cifrata $password.
//Altrimenti restituisce un array di stringhe vuote. Questa operazione è permessa agli utenti iscritti
        $idu = self::$conn->real_escape_string($idu);
        $password = self::$conn->real_escape_string($password);
        $blocked = $blocked ? 'TRUE' : 'FALSE';
        $q2 = $admin ? 'isAdmin = TRUE AND' : '';
          $query = "SELECT nickname, email, password FROM ".$this->tables['utentetable'].
                    " WHERE $q2 nickname IN (SELECT nickname FROM ".$this->tables['utentetable'].
                    " WHERE nickname='$idu' or email ='$idu') ";
		  $result = self::$conn->query($query); 
          if(!$result || $result->num_rows == 0)
              return false;
          $nickname = '';
          while ( $nickname == '' && $row = $result->fetch_assoc()) {
            $ok = false;
            if($row['nickname'] == $idu)
                $ok = true;
            elseif($row['email'] == $idu)
                $ok = true;
            if($ok)
                $ok =  password_verify($password, $row['password']);
            $nickname = $ok ? $row['nickname'] : '' ;
            $password = $ok ? $row['password'] : '';
            
            }
           $result->free();
            return array($nickname, $password);
        }
    public function isOwnerOfEvent($idu, $password, $idev){//metodo che restituisce true se $idu con password cifrata $password è proprietario dell'annuncio $idev. questa operazione è permessa solo agli'utenti iscritti e anche agli oggetti della classe evento
        $idu = self::$conn->real_escape_string($idu);
        $password = self::$conn->real_escape_string($password);
        $idev = self::$conn->real_escape_string($idev);
        $q = "SELECT nickname FROM ".$this->tables['utentetable'].
                    " WHERE nickname = '$idu' AND password = '$password'";
        $result = self::$conn->query($q); 
        if($result &&  $result->num_rows == 1){
            $row = $result->fetch_array(MYSQLI_NUM);
            $idu = $row[0];
            $query = "SELECT id FROM ".$this->tables['eventoTable'].
                    " WHERE creator = '$idu' AND id = '$idev'";
            $result = self::$conn->query($query);
            return $result && $result->num_rows == 1;
        }
        return false;
    }
    public function giveOwnerOfEvent($id){//Metodo che restituisce il nome utente del proprietario dell'annuncio $id. Questa operazione è permessa a tutti
        if($id != ''){
            $id = self::$conn->real_escape_string($id);
            $query = "SELECT creator FROM ".$this->tables['eventoTable'].
                    " WHERE id = '$id'";
            $result = self::$conn->query($query);
            if($result){
            if($result->num_rows == 1){
                $row = $result->fetch_array(MYSQLI_NUM);
                return $row[0];
                }
            }
                
        }
        return '';
    }
    public function esisteNomeUtente($idu){//Metodo che restituisce true se l'utente $idu è iscritto. Questa operazione è concessa a tutti
          $idu = self::$conn->real_escape_string($idu);
          $query = "SELECT nickname FROM ".$this->tables['utentetable'].
                    " WHERE nickname='$idu'";
		  $result = self::$conn->query($query);
          return $result && $result->num_rows > 0;
        }
    public function esisteUtente($keyword){//metodo che restituisce true se esiste un utente iscritto il cui nome, cognome o nome utente contiene la parola $keyword. Questa operazione è concessa a tutti
        $keyword = mb_strtoupper($keyword, 'UTF-8');
        $keyword = self::$conn->real_escape_string($keyword);
        $str = $str.($keyword != '' ? 'AND nickname IN ( SELECT nickname FROM '.$this->tables['utentetable'].
                    " WHERE UCASE(nickname) LIKE '%$keyword%' OR UCASE(cognome) LIKE '%$keyword%' OR UCASE(nome) LIKE '%$keyword%' )" : '');
        $query = "SELECT nickname FROM ".$this->tables['utentetable']." WHERE isAdmin=FALSE  $str";
		$result = self::$conn->query($query);
        return $result && $result->num_rows > 0;
    }
    public function userCognome($idu){//metodo che restituisce il cognome dell'utente con nome utente $idu. Operazione permessa a tutti
        $idu = self::$conn->real_escape_string($idu);
        $query = "SELECT cognome FROM ".$this->tables['utentetable'].
                    " WHERE nickname='$idu'";
		$result = self::$conn->query($query);
        if($result){
            $row = $result->fetch_array(MYSQLI_NUM);
            return $row[0];
        }
        return '';
    }
    public function userNome($idu){//metodo che restituisce il nome dell'utente con nome utente $idu. Operazione permessa a tutti
        $idu = self::$conn->real_escape_string($idu);
        $query = "SELECT nome FROM ".$this->tables['utentetable'].
                    " WHERE nickname='$idu'";
		$result = self::$conn->query($query);
        if($result){
            $row = $result->fetch_array(MYSQLI_NUM);
            return $row[0];
        }
        return '';
    }
    public function esisteAdmin($idu, $password){//metodo che restituisce true se esiste un admin $idu con password cifrata=$password. Operazione concessa solo agli utenti iscritti
        $idu = self::$conn->real_escape_string($idu);
        $password = self::$conn->real_escape_string($password);
          $query = "SELECT nickname FROM ".$this->tables['utentetable'].
                    "  WHERE nickname = '$idu' AND password = '$password' AND isAdmin = TRUE ";
		  $result = self::$conn->query($query); 
          return $result && $result->num_rows == 1;
        }
    public function utentePremium($idu){//metodo che ritorna true se nome utente $idu è un utente premium. Operazione permessa a tutti
          $idu = self::$conn->real_escape_string($idu);
          $query = "SELECT nickname FROM ".$this->tables['utentetable'].
                    " WHERE nickname='$idu' AND isPremium = TRUE";
		  $result = self::$conn->query($query);
          return $result && $result->num_rows == 1;
        }
    public static function TipiDiEventi(){//metodo che ritorna tutte le tipologie di eventi esistenti nel database. Operazione permessa a tutti
        $query = 'SELECT nome FROM tipoevento ORDER BY nome ASC';
		$result = self::$conn->query($query);
        $tipi = array();
        if($result){
          while ($row = $result->fetch_assoc()) 
            $tipi[] = $row['nome'];
        $result->free();
        }
        return $tipi;         
     }
    public static function CategorieEventi(){// metodo che ritorna tutte le categorie di eventi presenti nel database. Operazione permessa a tutti
        $query = 'SELECT nome FROM categoria ORDER BY nome ASC';
		$result = self::$conn->query($query);
        $tipi = array();
        if($result){
          while ($row = $result->fetch_assoc()) 
            $tipi[] = $row['nome'];
        $result->free();
        }
        return $tipi;        
     }
    public static function TotTypeEvents($type){//funzione che restituisce il numero totale di eventi di tipo $type
        $type = self::$conn->real_escape_string($type);
        $query = "SELECT id FROM tipoevento WHERE nome='$type'";
        $result = self::$conn->query($query);
        if($result->num_rows != 1)
            return -1;
        $row = $result->fetch_array(MYSQLI_NUM);
        $type = $row[0];
        $query = "SELECT count(*) FROM evento WHERE tipo='$type' AND blocked=FALSE";
		$result = self::$conn->query($query);  
        if($result){
        $row = $result->fetch_array(MYSQLI_NUM);
        return (int)$row[0]; 
        }
        return 0;
     }
    public static function TotEventsCategoria($categ){//funzione che restituisce il numero totale di eventi nella categoria $categ
        $categ = self::$conn->real_escape_string($categ);
        $query = "SELECT id FROM categoria WHERE nome='$categ'";
        $result = self::$conn->query($query);
        if($result->num_rows != 1)
            return -1;
        $row = $result->fetch_array(MYSQLI_NUM);
        $categ = $row[0];
        $query = "SELECT count(*) FROM evento WHERE categ='$categ' AND blocked=FALSE";
		$result = self::$conn->query($query);         
        if($result){
        $row = $result->fetch_array(MYSQLI_NUM);
        return (int)$row[0]; 
        }
        return 0;      
     }
    public function addNewUser($cogn, $nom, $mail, $idu, $pass){//metodo che permette di registrare un nuovo utente. restituisce true in caso di successo. Operazione permessa a tutti
        $cogn = self::$conn->real_escape_string($cogn);
        $nom = self::$conn->real_escape_string($nom);
        $mail = self::$conn->real_escape_string($mail);
        $pass = password_hash($pass,PASSWORD_BCRYPT);
        $filexml = $idu.'.xml';
        $cogn = mb_strtoupper($cogn, 'UTF-8');
        $nom = mb_strtoupper($nom, 'UTF-8');
        $today = date('d\/m\/Y');
        $query = "INSERT INTO `utente` (`nome`, `cognome`,`nickname`,`password`,`email`,`filexml`,`ultimoaccesso`)".
                "VALUES('$nom','$cogn','$idu','$pass','$mail','$filexml','$today')";
		if(self::$conn->query($query) && mkdir($this->folderFilexmlUser .$idu)){
         //CREAZIONE DEL FILEXML DELL'UTENTE
            $file = str_replace('<utente></utente>',"<utente>$idu</utente>", file_get_contents('Modello/UserFile_model.xml'));
            $file2 = str_replace('<utente></utente>',"<utente>$idu</utente>", file_get_contents('Modello/lastnewsmodello.xml'));
            $file3 = str_replace('<utente></utente>',"<utente>$idu</utente>", file_get_contents('Modello/social_model.xml'));
            file_put_contents($this->folderFilexmlUser ."$idu/".$filexml, $file);
            file_put_contents($this->folderFilexmlUser ."$idu/lastnews.xml", $file2);
            file_put_contents($this->folderFilexmlUser ."$idu/social.xml", $file3);
            return true;
        }
        return false;   
    }
    public function giveListEventsIds($idu,$keyword='', $showBlocked=true){//metodo che restituisce gli id degli annunci pubblicati da $idu ordinati in data di pubblicazione o data di svolgimento, in modo decrescente o non, 
    //contenenti la parola $keyword. Operazione permessa a tutti
        $idu = self::$conn->real_escape_string($idu);
        $keyword = self::$conn->real_escape_string($keyword);
        $keyword = mb_strtoupper($keyword,'UTF-8');
        $bl = !$showBlocked ? 'AND blocked=FALSE': '';
        $q = '';
        if($keyword != ''){
        $categ = self::CategorieEventi();
        $types = self::TipiDiEventi();
        $tipo = -1;
        $cat = -1;
        $tot = sizeof($types);
        for($i = 0; $i < $tot && $tipo == -1; ++$i){ 
            if(strpos (mb_strtoupper($types[$i],'UTF-8'), $keyword) !== false)
                $tipo = $this->giveIdTypeEvent($types[$i]);
        }
        $tot = sizeof($categ);
        for($i = 0; $i < $tot && $cat == -1; ++$i){
            if(strpos (mb_strtoupper($categ[$i],'UTF-8'), $keyword) !== false)
                $cat = $this->giveIdCatEvent($categ[$i]);
        }
        $q =  "AND id IN (SELECT id FROM ".$this->tables['eventoTable'].
                    " WHERE UCASE(titolo) LIKE '%$keyword%' OR UCASE(breveDesc) LIKE '%$keyword%' OR tipo = $tipo OR categ = $cat)";
        }
        $query = "SELECT id FROM ".$this->tables['eventoTable'].
                    " WHERE creator = '$idu' $bl $q"; 
		$result = self::$conn->query($query);
        $list = array();
        if($result){
        if($result->num_rows == 0)
              return $list;
        while ($row = $result->fetch_assoc()) 
            $list[] = $row['id'];
        $result->free();
        }
        return $list;
    }
    public function giveListBlockedEventsIds(){//metodo che restituisce gli id degli annunci bloccati. Operazione permessa a tutti
        $query = "SELECT id FROM ".$this->tables['eventoTable'].
                    " WHERE blocked = TRUE";
		$result = self::$conn->query($query);
        $list = array();
        if($result->num_rows == 0)
              return $list;
         if($result){
        while ($row = $result->fetch_assoc()) 
            $list[] = $row['id'];
        $result->free();
         }
        return $list;
    }
    public function aggiungiEvento($idu,$password, $tit, $data, $ore, $b_desc, $tipo, $categ, $city){//metodo che aggiunge un nuovo annuncio e ritorna il suo id creato in caso di successo, operazione permessa agli utenti premium
    if($this->esisteUtenteConPassword($idu,$password) && $this->isUtentePremium($idu)){
        if($tit!='' && $data !='' && $b_desc!=''&& isset($tipo)&& isset($categ) && $city!=''){
        $data = self::$conn->real_escape_string($data);
        $ore = self::$conn->real_escape_string($ore);
        $city = self::$conn->real_escape_string($city);
        $t = $this->giveNomeTypeEvent($tipo); 
            if($t != ''){
                $c = $this->giveNomeCatEvent($categ);
                if($c != ''){
                $id = uniqid('ev');
                $filexml = $id.'.xml';
                
                $tit = self::escapeCharacters($tit);
                $tit = self::$conn->real_escape_string($tit);
                $b_desc = self::escapeCharacters($b_desc);
                $b_desc = self::$conn->real_escape_string($b_desc);
                
                $city = mb_strtoupper($city, 'UTF-8');
                $query = "INSERT INTO `evento` (`id`,`dataEv`,`ora`,`dataPub`,`creator`,`titolo`,`breveDesc`,`tipo`,`categ`,`city`,`filexml`) ".
                "VALUES('$id','$data','$ore','".date('d\/m\/Y')."','$idu','$tit','$b_desc',$tipo,$categ,'$city','$filexml')";
                if(self::$conn->query($query)){
                    //CREAZIONE DEL FILEXML DELL'EVENTO
                    $file = str_replace('<evento></evento>',"<evento>$id</evento>", file_get_contents('Modello/EventoFile_model.xml'));
                    file_put_contents($this->folderFilexmlUser ."$idu/".$filexml, $file);
                    return $id;
                } 
            }
        }
        }
    }
        return '';
    }
    
    public function giveEventoPathFilexml($idev, $idowner){//metodo che restituisce il path al file xml informazioni del'annuncio $idev. operazione permessa a tutti
        $idev = self::$conn->real_escape_string($idev);
        $idowner = self::$conn->real_escape_string($idowner);
        $query = "SELECT filexml FROM ".$this->tables['eventoTable'].
                    " WHERE creator='$idowner' AND id = '$idev'";
		$result = self::$conn->query($query);
        if(!$result || $result->num_rows != 1)
            return '';         
        $row = $result->fetch_array(MYSQLI_NUM);
        return $this->folderFilexmlUser ."$idowner/".$row[0];
    }
    public function giveUtentePathFilexml($idu, $password, $admin = ''){//metodo che restituisce il path al file privato dell'utente $idu e password cifrata $password. se il chiamante è l'amministratoreallora $password è la password dell'amministratore.
    //operazine concessa all'utente $idu e a l'amministratore
        $query = '';
        $idu = self::$conn->real_escape_string($idu);
        $password = self::$conn->real_escape_string($password);
        $admin = self::$conn->real_escape_string($admin);
        if($admin == '')
        $query = "SELECT filexml FROM ".$this->tables['utentetable'].
                    " WHERE nickname = '$idu' AND password = '$password'";
        elseif($this->esisteAdmin($admin,$password))
            $query = "SELECT filexml FROM ".$this->tables['utentetable'].
                    " WHERE nickname = '$idu'";
		$result = self::$conn->query($query);
        if(!$result || $result->num_rows != 1)
            return '';          
        $row = $result->fetch_array(MYSQLI_NUM);
        return $this->folderFilexmlUser ."$idu/".$row[0];
    }
    public function giveUtenteNewsFilexml($idu){//metodo che restituisce il path al file xml delle ultime notizie di $idu. Operazione permessa agli utenti iscritti
        $idu = self::$conn->real_escape_string($idu);
        $query = "SELECT nickname FROM ".$this->tables['utentetable'].
                    " WHERE nickname = '$idu' AND isAdmin=FALSE";
		$result = self::$conn->query($query);
        if(!$result || $result->num_rows != 1)
            return '';          
        return $this->folderFilexmlUser ."$idu/lastnews.xml";
    }
    public function giveUtenteSocialFilexml($idu){//metodo che restituisce il path al file xml del social di $idu. Operazione permessa agli utenti iscritti
        $idu = self::$conn->real_escape_string($idu);
        $query = "SELECT nickname FROM ".$this->tables['utentetable'].
                    " WHERE nickname = '$idu' AND isAdmin=FALSE";
		$result = self::$conn->query($query);
        if(!$result || $result->num_rows != 1)
            return '';          
        return $this->folderFilexmlUser ."$idu/social.xml";
    }
    public function giveEventiUploadsFolder($idowner){//restitusce il path alla cartella degli uploads effettuali da $idowner. Operazione permessa agli utenti iscritti
        return $this->isUtentePremium($idowner) ? "Modello/UsersFolder/$idowner/uploads/" : '';
    }
    public static function esisteComune($comune){//funzione che restituisce true se $comune è un comune italiano.
        $comune = self::$conn->real_escape_string($comune);
        $query = "SELECT nome FROM comuni WHERE nome LIKE \"$comune%\"";
		$result = self::$conn->query($query);
        return $result && $result->num_rows > 0;
    }
    public static function esisteProvincia($prov){//funzione che restituisce true se $prov è una provincia italiana.
        $prov = self::$conn->real_escape_string($prov);
        $query = "SELECT nome FROM province WHERE nome LIKE \"$prov%\"";
		$result = self::$conn->query($query);
        return $result && $result->num_rows > 0;
    }
    public function deleteEvento($id, $idowner, $passwordOwner, $filexml){//metodo che rimuove dal database assieme ai suoi file xml creati, l'annuncio $id.Ritorna true in caso di successo. Operazione concessa solo al pubblicatore dell'annuncio $idowner con password cifrata $password, o all'amministratore
        if(($this->isOwnerOfEvent($idowner, $passwordOwner, $id)||$this->esisteAdmin($idowner, $passwordOwner)) 
            && $this->Toglidasegnalati($id, $idowner, $passwordOwner)&&
        $this->ToglidaPrimoPiano($id, $idowner, $passwordOwner)){
        $query = 'DELETE FROM '.$this->tables['eventoTable'] ." WHERE id ='$id' AND creator='$idowner'";
		return self::$conn->query($query) && unlink($filexml);  
        }
        return false;
    }
    public function deleteUtente($idu, $password, $idadmin=''){//metodo che rimuove dal database assieme ai suoi file xml creatil'utente $idu. Ritorna true in caso di successo. Operazione concessa solo all'utente $idu con password cifrata $password, o all'amministratore
        if($idu != $idadmin && ($this->esisteUtenteConPassword($idu, $password)||$this->esisteAdmin($idadmin, $password))){
        $dir = $this->userFolder($idu);
        $files = $this->filepersonaliUtente($idu,$password, $idadmin);
        $query = ' DELETE FROM '.$this->tables['utentetable'] ." WHERE nickname ='$idu'"; 
            if(self::$conn->query($query)){ 
                foreach($files as $file){
                    if($file !== ''){
                    if(is_file($file))
                        unlink($file);
                    }
                }
                return rmdir($dir);
            }
            
        }
        return false;
    }
    public function updateEvento($idev, $idu,$passwordOwner, $tit, $data,$ora, $b_desc, $tipo, $categ, $city){//metodo che permetter di aggiornare le informazioni dentro al database relative all'annuncio $idev. Ritorna true in caso di successo. Operazione concesso solo al proprietario $idu
        if($this->isOwnerOfEvent($idu, $passwordOwner, $idev)){ 
        if($idev !='' &&  $tit!='' && $data !='' && $b_desc!=''&& isset($tipo)&& isset($categ) && $city!=''){
            $t = $this->giveNomeTypeEvent($tipo);
            if($t != ''){
                $c = $this->giveNomeCatEvent($categ);
                if($c != ''){
                    $city = mb_strtoupper($city, 'UTF-8');
                    $city = self::$conn->real_escape_string($idu);
                    $b_desc = self::escapeCharacters($b_desc);
                    $b_desc = self::$conn->real_escape_string($b_desc);
                    $tit = self::escapeCharacters($tit);
                    $tit = self::$conn->real_escape_string($tit);
                    $data = self::$conn->real_escape_string($data);
                    $ora = self::$conn->real_escape_string($ora);
                    $query = 'UPDATE '.$this->tables['eventoTable'] ." SET titolo='$tit', dataEv='$data'".
                                     ", breveDesc='$b_desc', tipo=$tipo, categ=$categ, city='$city' , ora='$ora' ".
                                    " WHERE id ='$idev' AND creator='$idu'";
                                   
                    return self::$conn->query($query) ; 
                }
            } 
        }            
        }
        return false;
    }
    public function updateTagEvento($idev, $idu,$passwordOwner,$tag){//metodo che aggiorna le etichette dell'annuncio $idev. ritorna true in caso di successo. Operazione permessa solo al proprietario $idu
        if($this->isOwnerOfEvent($idu, $passwordOwner, $idev)){
        $query = 'UPDATE '.$this->tables['eventoTable'] ." SET tag='".self::$conn->real_escape_string(mb_strtolower($tag, 'UTF-8'))."'".
                                    " WHERE id ='$idev' AND creator='$idu'";
                                   
        return self::$conn->query($query);
        }
        return false;
    }
    public function updateblockedevent($idev, $idu,$passwordadmin,$value,$motivo){//metodo che aggiorna le informazioni relative al blocco dell' annuncio $idev, assegnando il valore booleano $value e motivi $motivo. Operazione permessa all'utente admin 
        if($this->esisteAdmin($idu, $passwordadmin)){
        $motivo = self::$conn->real_escape_string($motivo);
        $value = $value == TRUE ? 'TRUE' : 'FALSE';
        $query = 'UPDATE '.$this->tables['eventoTable'] ." SET blocked = $value, motivoblocked = '$motivo'".
                " WHERE id = '$idev'";
        if(self::$conn->query($query)){
            if(self::isinprimopiano($idev) && $value == 'TRUE')
                return $this->ToglidaPrimoPiano($idev, $idu,$passwordadmin) &&
                        $this->Toglidasegnalati($idev, $idu,$passwordadmin);
                        
           return true; 
         }
        }
        return false;
    }
    public function updateUltimoAccesso($idu,$password,$data){//metodo che aggiorna l'ultimo accesso dell'utente $idu con data $data. Operazione permessa solo a $idu
        if($this->esisteUtenteConPassword($idu, $password)){
            $data = self::$conn->real_escape_string($data);
            $query = 'UPDATE '.$this->tables['utentetable'] ." SET ultimoaccesso='$data' WHERE nickname ='$idu'";
            self::$conn->query($query);
        }
    }
   
    public function ultimoaccesso($idu){//metodo che restituisce l'ultimo accesso di $idu. Operazione permessa a tutti
        $idu = self::$conn->real_escape_string($idu);
        $query = "SELECT ultimoaccesso FROM ".$this->tables['utentetable'].
                    " WHERE nickname='$idu'";
		$result = self::$conn->query($query);
        if(!$result || $result->num_rows != 1)
            return '';          
        $row = $result->fetch_array(MYSQLI_NUM);
        return $row[0];
    }
    public function utenteCognome($idu){//metodo che restituisce il cognome di $idu. Operazione permessa a tutti
        $idu = self::$conn->real_escape_string($idu);
        $query = "SELECT cognome FROM ".$this->tables['utentetable'].
                    " WHERE nickname='$idu'";
		$result = self::$conn->query($query);
        if(!$result || $result->num_rows != 1)
            return '';          
        $row = $result->fetch_array(MYSQLI_NUM);
        return $row[0];
    }
    public function utenteNome($idu){//metodo che restituisce il nome di $idu. Operazione permessa a tutti
        $idu = self::$conn->real_escape_string($idu);
        $query = "SELECT nome FROM ".$this->tables['utentetable'].
                    " WHERE nickname='$idu'";
		$result = self::$conn->query($query);
        if(!$result || $result->num_rows != 1)
            return '';          
        $row = $result->fetch_array(MYSQLI_NUM);
        return $row[0];
    }
    public function utenteDataNascita($idu){//metodo che restituisce la data di nascita di $idu. Operazione permessa a tutti
        $idu = self::$conn->real_escape_string($idu);
        $query = "SELECT dataNascita FROM ".$this->tables['utentetable'].
                    " WHERE nickname='$idu'";
		$result = self::$conn->query($query);
        if(!$result ||$result->num_rows != 1)
            return '';          
        $row = $result->fetch_array(MYSQLI_NUM);
        return $row[0];
    }
    public function utenteMail($idu,$password){ //metodo che restituiscela mail di $idu. Operazione permessa a tutti
        $idu = self::$conn->real_escape_string($idu);
        $password = self::$conn->real_escape_string($password);
        $query = "SELECT email FROM ".$this->tables['utentetable'].
                    " WHERE nickname='$idu' AND password ='$password'";
		$result = self::$conn->query($query);
        if(!$result || $result->num_rows != 1)
            return '';          
        $row = $result->fetch_array(MYSQLI_NUM);
        return $row[0];
    }
    public function esisteMail($mail){
        $mail = self::$conn->real_escape_string($mail);
        $query = "SELECT email FROM ".$this->tables['utentetable'].
                    " WHERE email='$mail'";
		$result = self::$conn->query($query);
        return $result && $result->num_rows == 1;
    }
    public function updatePasswordUtente($idu, $oldpassword, $newPassword){//metodo che aggiorna la password di $idu. Restituisce true in caso di successo. operazione permessa solo a $idu
        if($this->esisteUtenteConPassword($idu,$oldpassword)){
            $newPassword = password_hash($newPassword,PASSWORD_BCRYPT);
            $query = 'UPDATE '.$this->tables['utentetable'] ." SET password='$newPassword'".
                " WHERE nickname ='$idu'";                               
            return self::$conn->query($query) ; 
        }
        return false;
            
    }
    public function updateInfo($idu,$password, $cogn, $nom, $mail){//metodo che aggiorna le informazioni si $idu. Restituisce true in caso di successo. Operazione permessa solo a $idu
        if($this->esisteUtenteConPassword($idu,$password)){
        $cogn = mb_strtoupper($cogn, 'UTF-8');
        $nom = mb_strtoupper($nom, 'UTF-8');
        $cogn = self::$conn->real_escape_string($cogn);
        $nom = self::$conn->real_escape_string($nom);
        $mail = self::$conn->real_escape_string($mail);
        $query = 'UPDATE '.$this->tables['utentetable'] ." SET cognome='$cogn', ".
                "nome='$nom', email='$mail' ".
                " WHERE nickname ='$idu'";                               
        return self::$conn->query($query);
        }
        return false;
    }
    public function primopiano(){//funzione che ritorna tutti gli id degli annunci in primo piano. Operaione permessa a tutti
        $query = "SELECT idev, owner FROM primopiano";
		$result = self::$conn->query($query);
        $id =array();
        if($result){
        while($row = $result->fetch_assoc()){
            $id[] = $row['idev']; 
        }
        }
        return $id;
    }
    public static function totinprimopiano(){//funzione che ritorna tutti gli id degli annunci in primo piano. Operaione permessa a tutti
        $query = "SELECT count(idev) FROM primopiano";
		$result = self::$conn->query($query);
        $id =array();
        if($result){
        $row = $result->fetch_array(MYSQLI_NUM);
        return (int)$row[0];
        }
        return 0;
    }
    public static function isinprimopiano($idev){//funzione che restituisce true se l'annuncio $idev è in primo piano
        $idev = self::$conn->real_escape_string($idev);
        $query = "SELECT idev FROM primopiano WHERE idev='$idev'";
		$result = self::$conn->query($query);
        return $result && $result->num_rows > 0;
    }
    public function mettiinprimopiano($idev, $idadmin, $password){//metodo che permette solo all'utente admin di mettere un annuncio in primopiano. Ritorna true in caso di successo
        if($this->esisteAdmin($idadmin, $password) && !self::isinprimopiano($idev) && self::totinprimopiano() < DB_handler::maxPrimoPiano){  
            $owner = $this->giveOwnerOfEvent($idev);
            $query = "INSERT INTO `primopiano` (`idev`, `owner`) VALUES('$idev','$owner')";
            return self::$conn->query($query);
        }
        return false;
    }
    public function searchEvents($type, $categ, $keyword, $city, $publisher, $showblocked= false){// metodo che restituisce un array di id di eventi di tipo $type, con categoria $categ, conntenenti la parola $keyword, pubblicati da $publisher, che avrà luogo nella città city.
    //Operazione permessa a tutti
        $query = '';
        $type = self::$conn->real_escape_string($type);
        $categ = self::$conn->real_escape_string($categ);
        $keyword = mb_strtoupper($keyword, 'UTF-8');
        $s ='';
        if($type == '' && $categ == '' && $keyword == '' && $city == '' && $publisher == ''){
            $s = $showblocked ? '': ' WHERE blocked = FALSE ';
           $query = $query = 'SELECT id FROM '.$this->tables['eventoTable']."  $s"; 
        }
        else {
            $s = $showblocked ? ' TRUE': 'blocked = FALSE';
            $str = ($type != -1 ? "tipo=$type " : '').
                    ($categ != -1 ? ($type != -1 ? ' AND ': ' ' )."categ =$categ" : '') .
                    ($city != '' ? ($type != -1 || $categ != -1 ? ' AND ': ' ' )."city ='".self::$conn->real_escape_string(mb_strtoupper($city, 'UTF-8'))."'" : '');
            $str =($str != '' ? ' WHERE '. $str.' AND ' : " WHERE "). $s;
            $query = 'SELECT id, creator, tipo, categ, tag FROM '.$this->tables['eventoTable'].$str;
        }
		$result = self::$conn->query($query);
        $id = array();
        if($result){
        while($row = $result->fetch_assoc()){
            $aggiungi = true;
            if($publisher != ''){
                $publisher = mb_strtoupper($publisher, 'UTF-8');
                $nome = mb_strtoupper($this->utenteNome($row['creator']), 'UTF-8');
                $cnome = mb_strtoupper($this->utenteCognome($row['creator']), 'UTF-8');
                $idu = mb_strtoupper($row['creator'], 'UTF-8'); 
                $tipo = mb_strtoupper($this->giveNomeTypeEvent($row['tipo']), 'UTF-8');
                $tags = mb_strtoupper($row['tag'], 'UTF-8');  
                if(strpos($idu,$publisher)===false && strpos($nome,$publisher)===false && strpos($cnome,$publisher) === false 
                && strpos($tipo,$keyword) === false &&strpos($tags,$keyword) === false && strpos($idu,$keyword) === false  ){
                    $aggiungi = false;
                }
            }
            if($keyword != ''){
                $tipo = mb_strtoupper($this->giveNomeTypeEvent($row['tipo']), 'UTF-8');
                $cat = mb_strtoupper($this->giveNomeCatEvent($row['categ']), 'UTF-8');
                $tags = mb_strtoupper($row['tag'], 'UTF-8');  
                if(strpos($tipo,$keyword) === false && strpos($cat,$keyword) === false &&strpos($tags,$keyword) === false){
                    $aggiungi = false;
                }
            }
            
            if($aggiungi)
                $id[] = $row['id']; 
        }
        }
        return $id;
    }
    public function giveIdTypeEvent($str){//ritorna l'id del tipo di evento $str. Operazione permessa a tutti
        $str = self::$conn->real_escape_string(mb_strtoupper($str, 'UTF-8'));
        $query = "SELECT id FROM ".$this->tables['tipoEventoTable'].
                    " WHERE UCASE(nome)='$str'";
		$result = self::$conn->query($query);
        if(!$result || $result->num_rows != 1)
            return -1;          
        $row = $result->fetch_array(MYSQLI_NUM);
        return (int)$row[0];
    }
    public function giveNomeTypeEvent($id){//ritorna il nome del tipo di evento con id= $id. operazione permessa a tutti
        $id = self::$conn->real_escape_string($id);
        $query = "SELECT nome FROM ".$this->tables['tipoEventoTable'].
                    " WHERE id = $id"; 
		$result = self::$conn->query($query);
        if(!$result || $result->num_rows != 1)
            return '';          
        $row = $result->fetch_array(MYSQLI_NUM);
        return $row[0];
    }
    public function giveIdCatEvent($str){//ritorna l'id della di evento $str. Operazione permessa a tutti
        $str = self::$conn->real_escape_string($str);
        $str = mb_strtoupper($str, 'UTF-8');
        $query = "SELECT id FROM ".$this->tables['categTable'].
                    " WHERE UCASE(nome)='$str'";
		$result = self::$conn->query($query);
        if(!$result || $result->num_rows != 1)
            return -1;          
        $row = $result->fetch_array(MYSQLI_NUM);
        return (int)$row[0];
    }
    public function giveNomeCatEvent($id){//ritorna il nome della categoria di evento con id= $id. operazione permessa a tutti
        $id = self::$conn->real_escape_string($id);
        $query = "SELECT nome FROM ".$this->tables['categTable'].
                    " WHERE id = $id";
		$result = self::$conn->query($query);
        if(!$result || $result->num_rows != 1)
            return '';          
        $row = $result->fetch_array(MYSQLI_NUM);
        return $row[0];
    }
    public function utentiIscritti($desc, $userType, $keyword = ''){//ritorna gli username ordinati in modo ddecrescente o non ($desc), degli utenti iscritti di tipo $userType, con nome, cognome o nome utente contenente $keyword.
//operazione permessa a tutti
        $order = $desc ? ' DESC ' : ' ASC ';  
        $str ='';
        switch($userType){
            case 0 : $str = 'AND isPremium = TRUE '; break;
            case 1 : $str = 'AND isPremium = FALSE '; break;
            case 2 : $str = 'AND blocked = TRUE '; break;
        }
        $keyword = self::$conn->real_escape_string(mb_strtoupper($keyword, 'UTF-8'));
        $str = $str.($keyword != '' ? 'AND nickname IN ( SELECT nickname FROM '.$this->tables['utentetable'].
                    " WHERE UCASE(nickname) LIKE '%$keyword%' OR UCASE(cognome) LIKE '%$keyword%' OR UCASE(nome) LIKE '%$keyword%' )" : '');
        $query = "SELECT nickname FROM ".$this->tables['utentetable']." WHERE isAdmin=FALSE  $str ORDER BY nickname $order";
		$result = self::$conn->query($query); 
        $id = array();
        if($result){
        while($row = $result->fetch_assoc()){
            $id[] = $row['nickname']; 
        }
        }
        return $id;
    }
    
    public function isUtentePremium($ut){//ritorna true se $ut è un utente premium. Operazione permessa a tutti
        $ut = self::$conn->real_escape_string($ut);
        $query = "SELECT * FROM ".$this->tables['utentetable']." WHERE isPremium=TRUE AND nickname = '$ut'";
		$result = self::$conn->query($query);
        return $result && $result->num_rows == 1;
    }
    public function isEventBlocked($idev){//ritorna true se l'annuncio $idev è stato bloccato. Operazione permessa a tutti
        $idev = self::$conn->real_escape_string($idev);
        $query = "SELECT id FROM ".$this->tables['eventoTable']." WHERE id='$idev' AND blocked = TRUE";
		$result = self::$conn->query($query);
        return $result && $result->num_rows == 1;
    }
    public function giveMotiveEventBlocked($idev){// ritorna il motivo percui $idev è stato bloccato. Operazione permessa a tutti
        $idev = self::$conn->real_escape_string($idev);
        $query = "SELECT motivoblocked FROM ".$this->tables['eventoTable']." WHERE id='$idev'";
		$result = self::$conn->query($query);
        if($result){
        $row = $result->fetch_array(MYSQLI_NUM);
        return $row[0];
        }
        return '';
    }
    public static function segnalaev($idev, $motivo){//Inserisce una nuova segnalazione di annuncio. Ritorna true se ha avuto successo
        if(self::totSegna($idev) < DB_handler::maxSegnalazioni){
            $motivo = self::escapeCharacters($motivo);
            $motivo = self::$conn->real_escape_string($motivo);
            $idev = self::$conn->real_escape_string($idev);
            $query = "INSERT INTO `segnalazione` (`idev`, `motivo`) VALUES('$idev','$motivo')";
            return self::$conn->query($query);
        }
        return false;   
    }
    public static function totSegna($idev = ''){//Ritorna il numero di segnalazioni relative all'annuncio $idev
        $idev = self::$conn->real_escape_string($idev);
        $q = $idev != '' ? "count(*) FROM segnalazione WHERE idev = '$idev'" : 'count(DISTINCT idev) FROM segnalazione';
        $query = "SELECT $q";
		$result = self::$conn->query($query);
        if($result){
        $row = $result->fetch_array(MYSQLI_NUM);
        return (int)$row[0];
        }
        return 0;
    }
    public static function totUgualiSegna($motivo, $idev = ''){//restituisce il numero totale di segnalazioni = $motivo relativo all'annuncio $idev != '', altrimenti ritorna il numero di segnalazioni = $motivo che sono stati ricevuti
        $idev = self::$conn->real_escape_string($idev);
        $motivo = self::$conn->real_escape_string(mb_strtoupper($motivo, 'UTF-8'));
        $q = $idev != '' ? "count(*) FROM segnalazione WHERE idev = '$idev' AND UCASE(motivo) = '$motivo'" : 
        "count(*) FROM segnalazione WHERE UCASE(motivo) = '$motivo'";
        $query = "SELECT $q";
		$result = self::$conn->query($query);
        if($result){
        $row = $result->fetch_array(MYSQLI_NUM);
        return (int)$row[0];
        }
        return 0;
    }
    public function eventisegnalati(){//metodo che ritorna gli id degli annunci di eventi segnalati. Operazione permessa a tutti
        $query = "SELECT DISTINCT idev FROM segnalazione ";
		$result = self::$conn->query($query);
        $id = array();
        if($result){
        while($row = $result->fetch_assoc()){
            $id[] = $row['idev']; 
        }
        }
        return $id;
    }
    public static function motividelsegnalato($idev){//restituisce un array di stringhe che sono i motivi delle segnalazioni
        $idev = self::$conn->real_escape_string($idev);
        $query = "SELECT DISTINCT motivo FROM segnalazione WHERE idev = '$idev'";
		$result = self::$conn->query($query);
        $mot = array();
        if($result){
        while($row = $result->fetch_assoc()){
            $mot[] = $row['motivo']; 
        }
        }
        return $mot;
    }
    public function Toglidasegnalati($idev, $idu, $password){//Toglie l'annuncio $idev dai segnalati, ritorna true se ha avuto successo. Operazione permessa solo all'amministratore e all'utente che sta cancellando il suo annuncio
        if($this->esisteAdmin($idu,$password) || $this->isOwnerOfEvent($idu, $password, $idev)){
        $idev = self::$conn->real_escape_string($idev);
        $query = "DELETE FROM segnalazione WHERE idev ='$idev'";
		return self::$conn->query($query);
        }
        return false;   
    }
    public function ToglidaPrimoPiano($idev, $idu, $password){//Toglie l'annuncio $idev dal primo piano, ritorna true se ha avuto successo. Operazione permessa solo all'amministratore e all'utente che sta cancellando il suo annuncio
        if($this->esisteAdmin($idu,$password) || $this->isOwnerOfEvent($idu, $password,$idev)){
        $idev = self::$conn->real_escape_string($idev);
        $query = "DELETE FROM primopiano WHERE idev ='$idev'";
		return self::$conn->query($query);
        }
        return false;   
    }
    public function bloccaUtente($idu,$value,$motivo, $admin, $passwordadmin){// metodo che aggiorna le informazioni relative al blocco dell'utente  $idu assegnando il valore booleano $value con motivi=$motivo. Operazione permessa solo all'admin
        if($this->esisteAdmin($admin, $passwordadmin)){
            $value = $value == TRUE ? 'TRUE' : 'FALSE';
            $motivo = self::escapeCharacters($motivo);
            $motivo = self::$conn->real_escape_string($motivo);
            $idu = self::$conn->real_escape_string($idu);
            $query = 'UPDATE '.$this->tables['utentetable'] ." SET blocked = $value, motivoblocked = '$motivo'".
                " WHERE nickname = '$idu'";                      
            return self::$conn->query($query);
        }
        return false;
    }
    public function isUtenteBlocked($idu, $password=''){//metodo che restituisce true se $idu con eventuale password $password è stato bloccato. Operazione permessa a tutti
        $q = $password != '' ? $this->esisteUtenteConPassword($idu,$password) : true;
        if($q){
        $idu = self::$conn->real_escape_string($idu);
        $query = "SELECT nickname FROM ".$this->tables['utentetable'].
        " WHERE nickname='$idu' AND blocked = TRUE";
		$result = self::$conn->query($query);
        return $result && $result->num_rows == 1;
        }
        return false;
    }
    public function motivoBlockedUtente($idu){//metodo che ritorna il motivo del blocco di $idu. Operazione permessa a tutti
        $idu = self::$conn->real_escape_string($idu);
        $query = "SELECT motivoblocked FROM ".$this->tables['utentetable'].
        " WHERE nickname='$idu' AND blocked = TRUE ";
		$result = self::$conn->query($query);
        if($result){
        $row = $result->fetch_array(MYSQLI_NUM);
        return $row[0];
        }
        return '';
    }
    public function makepremium($idu, $value, $admin, $password){//metodo che aggiorna lo stato premium di $idu con la booleana $value. Operazione permessa solo all'amministratore
        if($this->esisteAdmin($admin, $password)){
            $idu = self::$conn->real_escape_string($idu);
            $value = $value == true ? 'TRUE': 'FALSE'; 
            $query = 'UPDATE '.$this->tables['utentetable'] ." SET isPremium = $value ".
                " WHERE nickname = '$idu'";                      
            if(self::$conn->query($query)){
                if($value == 'TRUE')
                return file_exists ( $this->folderFilexmlUser."$idu/uploads" ) || mkdir($this->folderFilexmlUser."$idu/uploads");
                return true;
            }
        }
    }
    public function giveFilePremiumrichiesta($idu){//funzione che restituisce il file per la richiesta premium di un utente. Operazione permessa agli utenti iscritti
        return $this->esisteNomeUtente($idu) ? 'Modello/UsersFolder/listadomandepremium.xml' : '';
    }
    
}
DB_handler::init($DB);
?>

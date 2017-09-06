<?php

require_once("formTags.php");
/*LA CLASSE VIEWS E' STATA CREATA AL FINE DI FORNIRE DELLE COSTANTI E DELLE FUNZIONI STATICHE, UTILI AL CONTROLLER PER CREARE PAGINE HTML */
class views {
    const maxRecordPerPage = 6;//costante che statbilisce quanti recorcords, informazioni, ad esempio i risultati di una ricerca, devovono apparire ad ogni pagina
    const maxLengthSearchinput = 30;//costante che stabilisce il massimo di caratteri permessi per fare una ricerca
    //Le costanti qui sotto sono i path ai file HTML
    const contentonly = 'View/contentOnly.html';
    const headerfragment = 'View/head.html';
    const headernonavfragment = 'View/headnonav.html';
    const footer = 'View/footer.html';
    const page_404 = 'View/404.html';
    const page_403 = 'View/403.html';
    const spaziopersonaleUtente = 'View/homepageUtente.html';
    const spaziopersonaleAdmin = 'View/homepageAdmin.html';
    const login = 'View/login.html';
    const signup = 'View/signup.html';
    const addEvent = 'View/newEventForm.html';
    const editFoto = 'View/editfotoPage.html';
    const accountSetting = 'View/impostazione_account.html';
    const searchpage = 'View/searchResult.html';
    const FAQ = 'View/faq.html';
    const startTabIndex = 200;
    const premiumads = 'View/premiumuserad.html';
    //Questa variabile statica privata rappresenta le denominazioni urbane accettate
    private static $denominazioneUrban = array('Calle','Contrada','Corso','Corte',
                                    'Fondamenta','Frazione','Galleria','Largo',
                                    'Località', 'Passo','Piazza','Piazzale','Piazzetta',
                                    'Rione','Rotonda','Sestiere','Strada','Strada Provinciale',
                                    'Strada Statale','Via','Viale','Vicolo'); 
    
    public static function denominazioneUrbanSelect(){//funzione che costruisce una select con le option che hanno come valore testuale i valori della variabie statica $denominazioneUrban,
//    e  attributo value con valore l'indice  del valore testuale dentro $denominazioneUrban
        $tipi = new select('denom','denom');
        $tot = sizeof(self::$denominazioneUrban );
        for($i = 0; $i < $tot; ++$i){
            $tipi->addOption(self::$denominazioneUrban[$i],$i);
        }
        return $tipi;
    }
    public static function giveDenomfromindex($index){//funzione che ritorna la denominazione urbana di indice $index se esiste, altrimenti restituisce la denominazione urbana 'Via'
        if($index < sizeof(self::$denominazioneUrban))
        return self::$denominazioneUrban[$index];
        return 'Via';
    }
    public static function giveIndexDenomfromName($nome){//funzione che restituisce l'indice della denominazione urbana che ha volore testuale $nome, se fallisce restituisce l'indice della denominazione urbana 'Via'
        $nome = mb_strtolower($nome,'UTF-8');
        $tot = sizeof(self::$denominazioneUrban );
        for($i = 0; $i < $tot; ++$i){
            $str = mb_strtolower(self::$denominazioneUrban[$i],'UTF-8');
            if(strpos($str, $nome) !== false )
                return $i;
        }
        return (int)array_search('Via', self::$denominazioneUrban[$i]);
    }
}

?>
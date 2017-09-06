<?php
//LA CLASSE formTags  E' UNA CLASSE ASTRATTA CHE RAPPRESENTA L'INSIEME DEI TAG INTERATTIVI DISCENDENTI DEL TAG FORM. AD ESEMPIO: INPUT, TEXTAREA, SELECT ECC..
abstract class formTags{
     private $id; // id del tag
     private $name;// name del tag
     private $value = '';// valore di defaul del tag
     private $classvalue = '';//attributo class 
     private $htmlMessage = '';//messaggio HTML valido da  stampare assieme al tag
     public function __construct($id, $name){//costruttore che richiede obbligatoriamente un id e un name del tag della form
        $this->id = $id;
        $this->name = $name; 
     }
    private function buildLabel($textforlabel){//metodo che costruisce la stringa da ritornare, del tag label con valore testuale $textforlabel, e attributo for= l'id del formTags con subito dopo 
    //il messaggio HTML valido dato $this->htmlMessage
        return '<label for="'.$this->id.'">'.$textforlabel."</label>\n". $this->htmlMessage."\n";
    }
    public function giveId(){//metodo che restituisce l'id del tag della form
        return $this->id;
    }
    public function giveClass(){//metodo che restituisce la classe del tag della form
        return $this->classvalue;
    }
    public function giveName(){//metodo che restituisce il name del tag della form
        return $this->name;
    }
    public function giveValue(){//metodo che restituisce il valore del tag della form
        return $this->value;
    }
    public function setClass($value){//metodo che aggiunge una classe al tag della form
        if($this->classvalue == '')
            $this->classvalue = $value;
        else{
            $this->classvalue = $this->classvalue. ' '.$value;
        }
    } 
    public function setValue($value){//metoto che setta il valore di default del tag della form
        $this->value = $value; 
    }
    public function setMessage($htmlmessage){//Metodo che aggiunge un messaggio html da stampare insieme al tag della form. Il messaggio viene stampato subito dopo il label. Potrebbe essere usato per dare un esempio di input all'utente
        $this->htmlMessage = $htmlmessage;
    }
    abstract public function printTag();//metodo astratto che ritorna la stringa stampabile del tag
    
    public function printWithLabel($textforlabel){//metodo che ritorna la stringa stampabile del tag insieme ad un label
        return $this->buildLabel($textforlabel)."\n".$this->printTag();
    }
}
//LA CLASSE INPUT E' UNA CLASSE DERIVATA DA formTags, E CONCRETA, I CUI OGGETTI RAPPRESENTANO UN TAG INPUT DI UN CERTO TIPO
class input extends formTags{
      
    private $type = 'text';//tipo dell'input
    private $maxlength = 524288;//caratteri massimi se è un input di tipo text
        
    public function __construct($id, $name, $value='', $type =''){//costruttore che costruisce il tag input con attributi id e name, ed eventualemente un valore di default. Se $type=='' allora l'input è di default di tipo text
        
        if($type != '')
            $this->type = $type;
        if($value != '')
            $this->setValue($value);
        parent::__construct($id,$name); 
           
        }
    public function setMaxlength($length){//metodo che stabilisce massimo di caratteri se l'input è di tipo text
        if($this->type == 'text')
            $this->maxlength = $length; 
    }
    public function printTag(){//metodo overridato che ritorna la stringa stampabile del tag input
        $class = $this->giveClass();
        $class = $class != '' ? "class=\"$class\"" : '';
        $stringa = '<input type="'.$this->type ."\" $class name=\"".$this->giveName() .'" id="'.$this->giveId() .'"';
        if($this->maxlength != 524288)
            $stringa = $stringa.' maxlength="'.$this->maxlength .'"';
        if($this->giveValue() != '')
            $stringa = $stringa.' value="'.$this->giveValue() .'"';
        $stringa = $stringa.' />';
        return $stringa;
    }
    
}
//LA CLASSE TEXTAREA E' UNA CLASSE DERIVATA DA formTags, E CONCRETA, I CUI OGGETTI RAPPRESENTANO UN TAG TEXTAREA 
class textarea extends formTags{
      
    private $rows;//numero di righe
    private $cols;//numero di colonne
        
    public function __construct($id, $name, $rows = 1,$cols = 1, $value=''){//costruttore che costruisce il tag textarea con attributi id, name, righe e colonne, ed eventualemente un valore di default.
        parent::__construct($id,$name);
            $this->rows = $rows;
            $this->cols = $cols;
            $this->setValue($value);
    }
    public function printTag(){//metodo overridato che ritorna la stringa stampabile del tag textarea
        $class = $this->giveClass();
        $class = $class != '' ? "class=\"$class\"" : '';
        $stringa = '<textarea rows="'.$this->rows .'" cols="'.$this->cols ."\" $class name=\"".$this->giveName() .'" id="'.$this->giveId() .'">';
        if($this->giveValue() != '')
            $stringa = $stringa.$this->giveValue();
        $stringa = $stringa."\n</textarea>\n<span id=\"".$this->giveId()."ReloadValue\"></span>\n";
        return $stringa;
    }   
}
//LA CLASSE OPTION E' UNA CLASSE I CUI OGGETTI RAPPRESENTANO UN TAG OPTION CON ATTRIBUTI VALUE, SELECTED(SE E' SELEZIONATO) E VALORE TESTUALE. LA CLASSE E' STATA DICHIARATA QUI, INVECE DI ANNIDARLA ALLA CLASSE SELECT, IN QUANTO PHP NON SUPPORTA LE CLASSI ANNIDATE, 
class option{
        private $value;//valore testuale dell'option
        private $intvalue;//valore dell'attributo value
        private $isSelected = false;//è stato selezionato?
        public function __construct($value,$intvalue){//costruttore che costruisce obbligatoriamente l'oprion con valore testuale e valore dell'attributo value
            $this->value = $value;
            $this->intvalue = $intvalue;
        }
        public function setSelected($selected){//metodo che seleziona diseleziona l'option
            $this->isSelected = $selected;
        }
        public function printOption(){//metodo che ritorna la stringa stampabile dell'option
            return (!$this->isSelected) ? '<option value="'.$this->intvalue.'">'.$this->value."</option>\n" :
                        '<option value="'.$this->intvalue.'" selected="selected">'.$this->value."</option>\n";
        }
        public function valore(){ return $this->value;}//metodo che ritorna il valore testuale dell'option
        public function intvalore(){ return $this->intvalue;}//metodo che ritorna il valore dell'attributo value dell'option
    }
//LA CLASSE SELECT E' UNA CLASSE DERIVATA DA formTags, E CONCRETA, I CUI OGGETTI RAPPRESENTANO UN TAG SELECT CON OPTIONS
class select extends formTags{
      
    private $options = array();//array contenente gli options del tag select
    public function addOption($value,$intvalue){// metodo che aggiunge una nuova option con valore testuale $value e valore dell'attributo value $intvalue
        $this->options[]= new option($value,$intvalue); 
    }
    public function printTag(){//metodo overridato che ritorna la stringa stampabile del tag select
        $class = $this->giveClass();
        $class = $class != '' ? "class=\"$class\"" : '';
        $stringa = "<select $class name=\"".$this->giveName() .'" id="'.$this->giveId() .'">'."\n";
        foreach ($this->options as $value)
            $stringa = $stringa.$value->printOption();
        $stringa = $stringa."</select>";
        return $stringa;
    }
    public function setValue($value){//metodo overloadato che setta il valore di default del tag  select, corrisponde alla selezione di un option
        foreach($this->options as $option){
            if($option->valore() == $value)
                $option->setSelected(true);
            else $option->setSelected(false);
        }
    }
    public function setValueFromIndex($index){//metodo che permette di settare il valore di default del tag  select, corrisponde alla selezione di un option a partire dal valore del suo attributo value, se questo esiste
        $tot = sizeof($this->options);
            for($i = 0; $i < $tot; ++$i)
                if($this->options[$i]->intvalore() == $index)
                    $this->options[$i]->setSelected(true);
                else
                $this->options[$i]->setSelected(false);
    }
}
?>
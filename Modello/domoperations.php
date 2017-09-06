<?php
require_once("DB_handler.php");
// LA CLASSE DOMOPERATIONS E' UNA CLASSE I CUI OGGETTI PERMETTONO DI ESEGUIRE DELLE OPERAZIONI DEI LETTURA E MODIFICA SU FILE XML
class domoperations {
    
    private $filexml = ''; //path al file xml 
    private $dom;//oggetto di tipo DOMDocument per eseguire le operazioni
    public $esistefile = false;//campo dato che dice se il file xml è stato caricato correttamente
    public function __construct($filexml){ //costruttore che costruisce l'oggetto a partire dal file xml dato $filexml
        $this->filexml =  $filexml;
        $this->dom = new DOMDocument;
        $this->dom->validateOnParse = true;
        $this->esisteFile = is_file($filexml) && $this->dom->load($this->filexml);   
    }
    public function isOpen(){//ritorna true se il file è stato caricato correttamente
        return $this->esisteFile;
    }
    public function modificaSimpleTag($tagname,$value, $index){ //metodo che permette di assegnare al tag semplice $tagname in posizione $index, il valore $value. Ritorna true in caso di successo
        if($this->esisteFile){
            $tag = $this->dom->getElementsByTagName($tagname);
            $item =$tag->item($index);
            if(isset($item)){
                $item->nodeValue = $value;
                return $this->dom->save($this->filexml);
            }
        }
        return false;  
    }
    public function modificaSimpleTags($tagpadre, $indexpadre,$tagname,$value){ //metodo che permette di assegnare ai tag semplic' $tagname con padre $tagpadre in posizione $indexpadre, il valore $value. Ritorna true in caso di successo
        if($this->esisteFile){
        $tag = $this->dom->getElementsByTagName($tagpadre)->item($indexpadre);
            if(isset($tag)){
                $nodi = $tag->getElementsByTagName($tagname);
                foreach($nodi as $nodo){
                    $nodo->nodeValue = $value;
            }
            return $this->dom->save($this->filexml);
            }
        }
        return false;  
    }
    public function modificaSimpleTagById($id,$value){//metodo che permette di assegnare al tag semplice con id= $id, il valore $value. Ritorna true in caso di successo
        
        if($this->esisteFile){
        $tag = $this->dom->getElementById($id);
            if(isset($tag)){
                $tag->nodeValue = $value;
                return $this->dom->save($this->filexml);
            }
        }
        return false;
        
    }
    public function modificaElementTag($tagname,$children){//metodo che permette di aggiungere in coda al primo tag complesso $tagname trovato, i figli $children rappresentati come stringa xml. Ritorna true in caso di successo
        
        if($this->esisteFile){
            $item = $this->dom->createDocumentFragment();
            $item->appendXML($children);
            $parent = $this->dom->getElementsByTagName($tagname)->item(0);
            if(isset($parent)){
                while ($parent->hasChildNodes()) {
                    $parent->removeChild($parent->firstChild);
                }
                $parent->appendChild($item);
                return $this->dom->save($this->filexml);
            }
        }
        return false;
        
    }
    public function appendChild($tagname,$index,$children){//metodo che permettedi aggiungere in coda tag complesso $tagname in posizione $index, i figli $children rappresentati come stringa xml. Ritorna true in caso di successo
       if($this->esisteFile){
        $item = $this->dom->createDocumentFragment(); 
        $item->appendXML($children);
        $parent = $this->dom->getElementsByTagName($tagname)->item($index);
        if(isset($parent)){
            $parent->appendChild($item);
            return $this->dom->save($this->filexml);
        }
       }
       return false;
    }
    public function eliminaTagimg($tagpadre,$indexpadre, $index = -1){//metodo che elimina il tag img, con rimozione anche del file trovabile nell'attributo src in posizione $index != -1, del tag padre $tagpadre in posizione $indexpadre
//se $index==-1 allora vengono eliminati tutti i tag img del tag padre . ritorna true in caso di successo   
        if($this->esisteFile){
            if($index != -1){
                $parent = $this->dom->getElementsByTagName($tagpadre)->item($indexpadre);
                if(isset($parent)){
                    $node = $parent->getElementsByTagName('img')->item($index);
                    $src = $node->getAttribute('src');
                    unlink($src);
                    $node->parentNode->removeChild($node);
                    return $this->dom->save($this->filexml);
                }
            }
            else{
                $els = $this->dom->getElementsByTagName('img');
                for ($i = $els->length; --$i >= 0; ) { 
                    $el = $els->item($i);
                    $src = $el->getAttribute('src');
                    if(unlink($src))
                    $el->parentNode->removeChild($el);
                }
                return $this->dom->save($this->filexml);
            }
    }
    return false;
    }
    public function totTag($tagname){//metodo che ritorna in caso di successo il numero totale di tag con nome $tagname, altrimenti ritorna -1
        if($this->esisteFile){
            $tag = $this->dom->getElementsByTagName($tagname);
            return $tag->length;
        }
        return -1;
    }
    public function textoftags($tagpadre,$indexpadre,$tagtarget, $index = -1){//metodo che ritorna il testo dei tag semplici $tagtarget in un array di stringhe, se $index=-1, o del tag semplice$tagtarget in posizione $index != -1, figlio/ibase_add_user
    //del tag padre $tagpadre in posizione $indexpadre. Ritorna false in caso di insuccesso
        if($this->esisteFile){
            if($index == -1){
                $text = array();
                $padre =  $this->dom->getElementsByTagName($tagpadre)->item($indexpadre);
                if(isset($padre)){
                    $nodi = $padre->getElementsByTagName($tagtarget);
                    foreach($nodi as $nodo){
                        $text[] = $nodo->nodeValue;
                    }
                }
                return $text;
            }
            $padre =  $this->dom->getElementsByTagName($tagpadre)->item($indexpadre);
            if(isset($padre)){
            $nodo = $padre->getElementsByTagName($tagtarget)->item($index);
            return isset($nodo) ? $nodo->nodeValue : '';
            }
        }
        return false;
    }
    public function uniquesimpletagTextValue($tagtarget){//ritorna il testo del tag semplice $tagtarget,se esiste, senza id  ma unico , altrimenti ritorna la stringa vuota
        if($this->esisteFile){
            $tag = $this->dom->getElementsByTagName($tagtarget)->item(0);
            return isset($tag) ? $tag->nodeValue : '';
        }
        return '';
    }
    public function simpletagbyidTextValue($id){//ritorna il testo del tag semplice con id=$id se esiste, altrimenti ritorna la stringa vuota
        if($this->esisteFile){
            $tag = $this->dom->getElementById($id);
            return isset($tag) ? $tag->nodeValue : '';
        }
        return '';
    }
    public function uniquemixedtagValueasXml($tagtarget){//ritorna in forma di stringa xml i tag del tag misto $tagtarget ,se esiste, senza id  ma unico , altrimenti ritorna la stringa vuota
        if($this->esisteFile){
            $nodo = $this->dom->getElementsByTagName($tagtarget)->item(0);
            if(isset($nodo)){
                $figli = $nodo->childNodes;
                $xml = '';
                foreach ( $figli as $node )
                    $xml = $xml . $this->dom->saveXML($node) ."\n";
                return $xml;
            }
        }
        return '';
    }
    public function uniquemixedtagTextValue($tagtarget){//ritorna in forma di stringa xml i tag del tag misto $tagtarget ,se esiste, senza id  ma unico , altrimenti ritorna la stringa vuota
        if($this->esisteFile){
            $nodo = $this->dom->getElementsByTagName($tagtarget)->item(0);
            return isset($nodo) ? $nodo->textContent : '';
        }
        return '';
    }
    public function giveNodesofmixedTagsasXml($tagpadre,$indexpadre,$tagtarget, $index){
        //ritorna in forma di stringa xml i tag del tag misto $tagtarget in posizione $index con padre $tagpadre in posizione $indexpadre. Ritorna la stringa vuota in caso di insuccesso
        if($this->esisteFile){
            $padre =  $this->dom->getElementsByTagName($tagpadre)->item($indexpadre);
            if(isset($padre)){
                $nodo = $padre->getElementsByTagName($tagtarget)->item($index);
                $figli = $nodo->childNodes;
                $xml = '';
                foreach ( $figli as $node )
                    $xml = $xml . $this->dom->saveXML($node) ."\n";
                return $xml;
            }
        }
        return '';
    }
    public function eliminaSimpleTags($tagpadre,$indexpadre,$tagtarget, $textson=''){
        //elimina il tag semplice $tagtarget contenente il testo $textson != '', altrimenti tutti i tag con nome $tagtarget, sempre se sono figli del tag $tagpadre in posizione $indexpadre. Ritorna true in caso di successo
        if($this->esisteFile){
           $padre =  $this->dom->getElementsByTagName($tagpadre)->item($indexpadre);
           if(isset($padre)){
                $nodi = $padre->getElementsByTagName($tagtarget);
                    foreach($nodi as $nodo){
                        $cancella = $textson == '' ? TRUE : $nodo->nodeValue == $textson;
                        if($cancella)
                            $padre->removeChild($nodo);
                    }
                return $this->dom->save($this->filexml);
           }
        }
    return false;
    }
    public function eliminaTag($tagpadre,$indexpadre,$tagtarget, $index){
        //elimina il tag $tagtarget in posizione $index, figlio del tag $tagpadre in posizione $indexpadre. Ritorna true in caso di successo
        if($this->esisteFile){
           $padre =  $this->dom->getElementsByTagName($tagpadre)->item($indexpadre);
           if(isset($padre)){
                $nodo = $padre->getElementsByTagName($tagtarget)->item($index);
                $padre->removeChild($nodo);
                return $this->dom->save($this->filexml);
           }
        }
        return false;
    }
    public function esistesimpletag($tagpadre,$indexpadre, $tagtarget, $text){
    //ritorna true se esiste un tag semplice contenente $text, figlio di $tagpadre in posizione $indexpadre
        if($this->esisteFile){
            $parent = $this->dom->getElementsByTagName($tagpadre)->item($indexpadre);
            if(isset($parent)){
                $nodi = $parent->getElementsByTagName($tagtarget);
                foreach($nodi as $nodo){
                    if($nodo->nodeValue == $text)
                    return true;
                }
            }
        }
       return false;
    }
}

?>
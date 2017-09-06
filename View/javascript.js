/*
    PARTE MENU' A TENDINA
*/
var csshidemenuclass = 'hideinmobile';
var cssmenuMobileClass = 'menumobile';
var cssClassEventLauncher = 'formobilemenu';
var cssClassEventsearcher = 'menuclick';
var mobilepixelswitch = 1064;
function addClassToElement(tag, classname){//Funzione che aggiunge la classe classname all'elemento tag
    if(tag != null){
        if(tag.hasAttribute("class")){
            var classe = tag.getAttribute("class"); 
            if(classe.indexOf(classname) == -1)
                tag.className += (" " + classname);
        }
        else{
            tag.setAttribute('class',classname);
            }
    }
}
function removeClassToElement(tag, classname){//Funzione che rimuove la classe classname all'elemento tag
    if(tag != null){
        var classe = tag.className;
        classe = classe.replace(classname,"");
        if(classe == '')
            tag.removeAttribute('class');
        else{
            tag.className = classe;
            }   
    }
}
function buildMobileClass(dropdowntag, showedtag, justload){//La funzione costruisce il menù a tendina showedtag, accessibile cliccando su dropdowntag
    if(showedtag!= null){
        showMenu(showedtag,justload);
        addClassToElement(dropdowntag, cssClassEventLauncher);
        dropdowntag.onclick= function(){
            showMenu(showedtag, false);
        }
    }
}
function checkMobileAndLoad(justload){//La funzione controlla la larghezza della finestra o dello schermo e se <= mobilepixelswitch passa in modalità menù a tendina per i menu della classe cssmenuMobileClass. justload è un parametro che viene 
//passato alla funzione showMenu 
    var width = (window.innerWidth > 0) ? window.innerWidth : screen.width;
    if(width <= mobilepixelswitch){
        var menu = document.querySelectorAll('.'+cssmenuMobileClass);
        var goTo = document.querySelectorAll('.'+cssClassEventsearcher);
        if(goTo.length > 0 && menu.length == goTo.length){
            for(var i=0; i< goTo.length; ++ i)
            buildMobileClass(goTo[i],menu[i], justload);
        } 
    }
    else{
        var goTo = document.querySelectorAll('.'+cssClassEventLauncher);
        var menu = document.querySelectorAll('.'+csshidemenuclass);
        for(var i = 0; i < goTo.length ; ++i){
            removeClassToElement(goTo[i], cssClassEventLauncher);
                addClassToElement(goTo[i], cssClassEventsearcher);
        }
        for(var i=0; i< menu.length; ++i){
            removeClassToElement(menu[i], csshidemenuclass);
            addClassToElement(menu[i], cssmenuMobileClass);
        }
    }
}
function showMenu(tag,justload){//Questa funzione inserisce o rimuove la classe css rappresentata dalla costante csshidemenuclass per i menù a tendina all'elemento tag.
//Se justload==false allora la classe, se esiste, viene rimossa dall'elemento. Se justload ==true e la classe non esiste già allora classe viene aggiunta all'elemento. Questo comportamento consiste a nascondere o mostrare
//il menù all'utente vedente
    if(tag !== null){
        var classe = tag.getAttribute("class"); 
        var esiste = false;
        if(classe != null)
            esiste = classe.indexOf(csshidemenuclass) != -1;
        if(esiste === true && justload ==false){
            tag.removeAttribute('class');
        }
        else{
                if(esiste == false)
                    tag.setAttribute('class', csshidemenuclass);
        }
    }
}
window.onresize = function(){//definizione dell'evento onresize per fare in modo tale che si passi in modalità mobile se il browser viene ridimensionato 
    checkMobileAndLoad(true);
}
/*
    PARTE CONTROLLO INPUT
*/
var maxFotos = 5; //  massimo di foto caricabili
var regoleInserimentiInputTextarea = {// array regoleInserimentiInputTextarea[key][i]; key è la chiave che  rappresenta l'id dell'input. 
//i=0  è l'espressione regolare. i=1 è l'errore che viene mostrato
		"titolo": [/.{2,}[^\s]/g, "Inserire un titolo chiaro e comprensibile con almeno 2 caratteri. Ad esempio: NUOVI CORSI DI CINESE"],
        "via": [/.{2,}[^\s]/g, "Inserire un indirzzo corretto. Esempio: Paolotti 13 A"],      
        "com": [/.{2,}[^\s]/g, "Inserire una città o comune italiano. Esempio: Padova"],
        "prov": [/.{2,}[^\s]/g, "Inserire una provincia italiana. Esempio: Treviso"],
        "breveDesc": [/.{10,}[^\s]/, "Inserire una introduzione chiara e comprensibile di almeno 10 caratteri dell'annuncio"],        
        "totDesc": [/.{10,}[^\s]/, "Inserire una descrizione chiara e comprensibile di almeno 10 caratteri dell'evento"],
        "cognome": [/.{2,}[^\s]/g, "Inserire il cognome, almeno 2 caratteri. Ad esempio: Rossi"],
        "nome": [/.{2,}[^\s]/g, "Inserire il nome, almeno 2 caratteri. Ad esempio: Mario"],
        "mail": [/^[^\s]+@[a-zA-Z]+\.[a-zA-Z]{1,20}$/g, 'Inserire una mail valida. Ad esempio: mario_rossi&#64;gmail.com'],
        "nomeutente": [/^[^\s]{5,}$/g, 'Inserire un nome utente con almeno 5 caratteri, senza caratteri di spaziatura. Ad esempio: mario.rossi'],
        "password1": [/^[^\s]{8,}$/g, 'Inserire una password con almeno 8 caratteri, senza caratteri di spaziatura.'],
        "password2": [/^[^\s]{8,}$/g, 'Si prega anche di reinserire la password scelta.']
};
var regoleDataOra = {// array regoleDataOra[key][i]; simile all'array precedente usato solo per la validazione della data e dell'ora nella funzione validateDataeOra()
        "giorno": [ /^[0-3]?[0-9]$/, "Inserire il giorno in formato numerico GG, da 1 a 31. Esempio: 03 se è il giono tre, oppure 24 se è il giorno ventiquattro"],
        "mese": [/^[0-3]?[0-9]$/, "Inserire il mese in formato numerico MM da 1 a 12. Esempio: 09 se è a settembre"],
		"anno": [/^[0-9]{4}$/, "Inserire l'anno in formato numerico AAAA. Esempio: 2017"],
        "ora": [/^[0-2]?[0-9]$/, "Inserire le ore in formato numerico HH,da 0 a 23. Esempio: 14 se è alle quattordici, oppure 09 se è alle nove"],
        "minuti": [/^[0-5]?[0-9]$/, "Inserire i minuti in formato numerico MM, da 0 a 59. Esempio: 15"]
};
var errorclass = 'erroreinput'; // variabile globale che definisce la classe degli errori da assegnare agli input che hannno generato un errore
function mostraErrore(erroreInHtmlList){ //funzione che mostra la lista degli errori avvenuti in un div con id=showerror. Gli errori sono memorizzati nell'array di stringhe erroreInHtmlList
    if(erroreInHtmlList.length > 0){
        var messaggi = document.createElement("UL");
        var p = document.createElement("p");
        var strong = document.createElement("strong");
        strong.appendChild(document.createTextNode("SONO AVVENUTI DEGLI ERRORI :"));
        p.appendChild(strong); 
        messaggi.setAttribute("id", "listaerrori");
        for(var i= 0; i <  erroreInHtmlList.length; ++i) {
			var li = document.createElement('li');
            li.innerHTML = erroreInHtmlList[i];
            messaggi.appendChild(li);
		}
        
        var ele = document.getElementById("hide");
        if(ele == null)
            ele = document.getElementById("showerror");
        var messaggio = document.createElement("DIV");       
        messaggio.setAttribute("id", "showerror");
        messaggio.appendChild(p);
        messaggio.appendChild(messaggi);
        ele.parentNode.replaceChild(messaggio, ele);
        window.location.hash = '#showerror';
    }
}
function setTagToerror(tag, conf){//Funzione che cambia il colore dei bordi del tag su cui è avvenuto l'errore di inserimento
    if(tag != null){
        if(conf===true){
            addClassToElement(tag,errorclass);        
        }
        else{
            removeClassToElement(tag, errorclass);
        }
    }  
}
function validainputorTextarea(array){//funzione che controlla che i tag della form con id in array[key], rispettino l'espressione regolare in array[key][0], altrimenti viene messo
//il corrispondente messaggio di errore in posizione array[key][1], in un array di stringhe da ritornare
    var errori = [];
		for(var key in array){
			var input = document.getElementById(key);
            setTagToerror(input, false);
            if(input != null){
                if(validaCampo(input.value,array[key][0] ) == false)
                {
                    errori.push(array[key][1]);
                    setTagToerror(input, true);
                }
            }
		}
    return errori;
}
function validaFotoCaricate(){//Funzione che controlla le estensioni delle foto che l'utente carica per il suo annuncio, e controlla anche che ci sia una descrizione 
//per ogni foto caricata. Ritorna un array vuoto in caso di successo, altrimenti ritorna un array di stringhe che sono gli errori avvenuti
    var errori = [];
    for(var i = 1; i <= maxFotos; ++i){
        var input = document.getElementById("Foto"+i);
        if(input != null && input.value != ''){
            setTagToerror(input, false);
            var err = false;
            if(validaCampo(input.value,/.+[^\s]\.jpg$/g) == false && validaCampo(input.value,/.+[^\s]\.jpeg$/g)==false && 
               validaCampo(input.value,/.+[^\s]\.png$/g)==false && validaCampo(input.value,/.+[^\s]\.gif$/g)==false )
             {
                errori.push('Il formato della foto numero '+i+' non è supportato');
                setTagToerror(input, true);
                err= true;
             }
            var descr = document.getElementById("DescFoto"+i);
            if(descr != null && err==false){
                setTagToerror(descr, false);
                if(validaCampo(descr.value,/.{2,}/) == false)
                {
                    errori.push('Inserire una descrizione chiara e comprensibile della foto numero '+i);
                    setTagToerror(descr, true);
                }
            }
        }
    }
    return errori;
}
function validaCampo(text, regex) {//funzione che ritorna true se la stringa text se rispetta l'espressione regolare regex, altrimenti false
		if (text.search(regex) == -1) {
			return false;
		}
		return true;
}
function validateDataeOra(){//Funzione che ritorna un array con length > 0 se ci sono stati errori nell'input della data e dell'ora
    var errori = validainputorTextarea(regoleDataOra);
        var g = document.getElementById('giorno');
        if(errori.length == 0){
            var giorno = parseInt(g.value);
            var m = document.getElementById('mese');
            var mese = parseInt(m.value);
            var a = document.getElementById('anno');
            var anno = parseInt(a.value);
            var o = document.getElementById('ora');
            var ore = parseInt(o.value);
            var min = document.getElementById('minuti');
            var minuti = parseInt(m.value);
            errore = false;
            if(giorno == 31 && (mese == 2 || mese==4 || mese == 6 || mese == 9 || mese == 11)){
                errori.push("Inserire una data corretta: il giorno 31 non esiste nel mese inserito. ");
                errore=true;
            }
            if(giorno == 30 && mese == 2){
                errori.push("Inserire una data corretta: il giorno 30 non esiste nel mese di Febbraio.");
                errore=true;
            }
            if(giorno == 29 && mese == 2 && (anno%4) != 0){
                errori.push("Inserire una data corretta: il giorno 29 Febbraio non esiste nell'anno inserito. ");
                errore=true;
            }
            if(errore == true){ 
                setTagToerror(g, true);
                setTagToerror(m, true);
                setTagToerror(a, true);
            }
           if(ore < 0 ||ore >= 24 || minuti < 0 || minuti >= 60){
                errori.push("Inserire un'ora corretta: l'ora deve essere compresa tra 0 e 24 e i minuti tra 0 e 59");
                setTagToerror(o, true);
                setTagToerror(min,true);
            }
    }       
    return errori;       
}
function validalaform(checkdate){// ritorna true se i valori di una form di aggiunta o modifica di annuncio di evento è conforme alle esigenze del mio sito
//il parametro checkdate serve ad indicare che la forma ha dei tag per l'input di date e ore percui deve fare un controllo su essi
    var errori = validainputorTextarea(regoleInserimentiInputTextarea);
    if(checkdate ===true)
    errori = errori.concat(validateDataeOra());
    errori = errori.concat(validaFotoCaricate());
    if(errori.length > 0)
    {
        mostraErrore(errori);
        return false;
    }
    return true;
}
function modificafotoMode(){//Questa funzione viene chiamata al caricamento della pagina di gestione foto, serve ad inserire l'attributo onsubmit, con valore che chiama la funzione
//validalaform passandole un valore attuale al suo parametro formale checkdate
    var form = document.getElementById('newfotouploads');
    if(form != null){
        form.setAttribute('onsubmit','return validalaform(false);');
    }
}
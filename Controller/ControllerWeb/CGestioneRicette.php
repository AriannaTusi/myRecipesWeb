<?php

/**
 * Class CGestioneRicette gestisce tutto ciò che riguarda una ricetta (ricerca, aggiunta/rimozione da preferiti, aggiunta di un commento)
 */
class CGestioneRicette {
    /**
     * Metodo che avvia la ricerca per ingrediente (mostra ingredienti selezionabili)
     */
    public function RicercaPerIngredienti(){
        $pm = FPersistentManager::getInstance();
        $cibi = $pm->loadAllObjects();
        $view = new VRicerca();
        $view->mostraIngredienti($cibi);
    }

    /**
     * Metodo che avvia la ricerca avanzata (mostrare filtri)
     */
    public function RicercaAvanzata(){
        $view = new VRicerca();
        $view->mostraFiltri();
    }

    /**
     * Metodo che ricerca le ricette contenenti determinati cibi
     */
    public function cercaPerIngredienti(){
        if(($_SERVER['REQUEST_METHOD']=="POST")){
            $view = new VRisultati();
            $idCibi = $view->recuperaIngredienti(); //id di cibi selezionati dall'utente
            $pm = FPersistentManager::getInstance();
            $ricette = $pm->ricercaTramiteIngrediente($idCibi);
            if($ricette!=null){
                $msg = "";
            } else {
                $msg = "Non ci sono ricette che soddisfano questi parametri";
            }
            $view->mostraRisultati($ricette, $msg);

        }
        else{
            header('HTTP/1.1 405 Method Not Allowed');
            header('Allow: POST');
        }

    }


    /**
     * Metodo che ricerca le ricette secondo i filtri
     */
    public function cercaAvanzata(){
        if(($_SERVER['REQUEST_METHOD']=="POST")){
            $view = new VRisultati();
            $filtri = $view->recuperaFiltri(); //filtri inseriti dall'utente
            $pm = FPersistentManager::getInstance();
            $ricette = $pm->ricercaTramiteFiltri($filtri);
            if($ricette!=null){
                $msg = "";
            } else {
                $msg = "Non ci sono ricette che soddisfano questi parametri";
            }
            $view->mostraRisultati($ricette, $msg);

        }
        else{
            header('HTTP/1.1 405 Method Not Allowed');
            header('Allow: POST');
        }

    }

    /**
     * Metodo che ricerca le ricette dal nome inserito dall'utente nella barra di ricerca
     */
    public function cercaDaNome(){
        if(($_SERVER['REQUEST_METHOD']=="POST")){
            $view = new VRisultati();
            $nome = $view->recuperaNome(); //nome inserito nella barra di ricerca
            $pm = FPersistentManager::getInstance();
            $ricette = $pm->search("ricetta", $nome, "nome");
            if($ricette!=null){
                $msg = "";
            } else {
                $msg = "Non ci sono ricette che soddisfano questi parametri";
            }
            $view->mostraRisultati($ricette, $msg);

        }
        else{
            header('HTTP/1.1 405 Method Not Allowed');
            header('Allow: POST');
        }


    }

    /**
     * Metodo che dato l'id della ricetta selezionata restituisce la ricetta
     * @param $id identificativo ricetta selezionata dall'utente
     */
    public function Ricetta($id){
        $pm = FPersistentManager::getInstance();
        $ricetta = $pm->loadById("ricetta",$id);
        $session = Sessione::getInstance();
        //se l'utente è loggato si verifica se preferisce questa ricetta (cuore pieno o vuoto a seconda dei casi)
        //se non è loggato sicuramente il cuore è vuoto
        if($session->isLoggedUtente()){
            $preferita = $pm->UtentePrefRic($id,$session->getUtente()->getId());
        } else {
            $preferita = false;
        }
        $commenti = $ricetta->getCommenti();
        $arrcommenti = array();
        //costruisco un array in cui ogni elemento è un array associativo con chiavi 'utente', 'commento' e 'img' profilo utente
        foreach ($commenti as $commento){
            $id = $commento->getIdUtente();
            $utente = $pm->loadById("utente",$id);
            $img = $utente->getImmagine();
            $img->setData(base64_encode($img->getData()));
            $tmp = array(
                'utente'=>$utente->getUsername(),
                'img'=>$img,
                'commento'=>$commento
            );
            $arrcommenti[]=$tmp;

        }

        $errore="";
        if($arrcommenti==null){
            $errore="Non ci sono commenti per questa ricetta";
        }
        else{
            $errore="";
        }
        $view = new VDettaglio();
        $view->mostraRicetta($ricetta, $preferita, $arrcommenti, $errore);
    }

    /**
     * Metodo che gestisce le ricette preferite dall'utente (solo utenti loggati, altrimenti reindirizzamento al login)
     */
    public function Preferiti() {
        $session = Sessione::getInstance();
        if($session->isLoggedUtente()){
            $utente = $session->getUtente();
            $idutente = $utente->getId();
            $pm = FPersistentManager::getInstance();
            $ut = $pm->loadById("utente", $idutente);
            $ricette = $ut->getPreferiti();
            if($ricette!=null){
                $msg = "";
            } else {
                $msg = "Non hai ricette preferite";
            }
            $view = new VPreferiti();
            $view->mostraPreferiti($ricette, $msg);

        } else {
            header('Location: /myRecipes/web/Utente/Login');
        }
    }

    /**
     * Metodo che dato gestisce l'inserimento nei preferiti (solo utenti loggati, altrimenti reindirizzamento al login)
     * @param $idricetta id della ricetta aggiunta
     */
    public function AggiungiaiPreferiti($idricetta){
        $session = Sessione::getInstance();
        if($session->isLoggedUtente()){
            $utente = $session->getUtente();
            $idutente = $utente->getId();
            $pm = FPersistentManager::getInstance();
            $ric = $pm->loadById("ricetta",$idricetta);
            $ric->incrementasalvataggi();
            $n = $ric->getNsalvataggi();
            $pm->update("ricetta",$idricetta,'nsalvataggi',$n); //aggiornamento ricetta db
            $esito =  $pm->storeUtPrefRic($idricetta, $idutente); //aggiunta di una entry
            //devo aggiornare l'oggetto utente nei dati di sessione (ha un nuovo preferito)
            $utente = $pm->loadById("utente", $idutente);
            $session->setUtenteLoggato($utente);
            if($esito){
                //inserimento corretto, redirect alla pagina corrente
                $referer = $_SERVER['HTTP_REFERER']; //indirizzo che stavo visitando quando ho aggiunto ai preferiti
                $loc = substr($referer, strpos($referer, "/myRecipes")); //recupero parte path
                header('Location: '.$loc); //reindirizzamento al referer
            }
            else {
                $viewerr = new VErrore();
                $viewerr->mostraErrore("Inserimento nei preferiti non corretto");

            }
        } else { //utente non loggato
            //redirect alla form di login

            header('Location: /myRecipes/web/Utente/Login');
        }

    }

     /** Metodo che permette di gestire l'inserimento di un commento dell'utente (solo utenti loggati)
     * @param $idricetta id ricetta commentata
     *
     */
    public function Commento($idricetta){
        if(($_SERVER['REQUEST_METHOD']=="POST")){
            $session = Sessione::getInstance();
            if($session->isLoggedUtente()){
                $view = new VDettaglio();
                $commento = $view->recuperaCommento();
                $utente = $session->getUtente();
                $idutente = $utente->getId();
                //costruzione di ECommento
                $com = new ECommento($commento['testo'], $commento['data'], $commento['ora'], $idutente, $idricetta);
                $pm = FPersistentManager::getInstance();
                $id = $pm->store($com);
                //devo aggiornare l'oggetto utente nei dati di sessione (ha un nuovo commento)
                $utente = $pm->loadById("utente", $idutente);
                $session->setUtenteLoggato($utente);
                if($id){
                    //inserimento corretto, redirect a dettaglio ricetta attuale (usiamo il metodo Ricetta di questo controller)
                    header('Location: /myRecipes/web/Ricette/Ricetta/'.$idricetta);
                } else {
                    //messaggio errore inserimento non corretto
                    $viewerr = new VErrore();
                    $viewerr->mostraErrore("Inserimento commento non corretto");
                }

            } else { //utente non loggato
                //redirect alla form di login
                header('Location: /myRecipes/web/Utente/Login');
            }

        }
        else{
            header('HTTP/1.1 405 Method Not Allowed');
            header('Allow: POST');
        }


    }
    

    /**
     * Metodo che permette di rimuovere una ricetta dai preferiti
     * Tale funzionalità è solo per utenti loggati, ma se l'utente ha la possibilità di rimuovere la ricetta
     * il controllo sul login è già avvenuto perchè
     * 1) L'utente rimuove una ricetta dai preferiti dal dettaglio ricetta, ma può farlo perchè il cuore è pieno, quindi sicuramente è gia loggato, oppure
     * 2) Rimuove una ricetta dai preferiti dall'elenco delle sue ricette preferite, ma se ha questa funzionalità disponibile sicuro è gia loggato
     * @param $idricetta identificativo ricetta da rimuovere
     */
    public function RimuoviDaPreferiti($idricetta){
        $session = Sessione::getInstance();
        if($session->isLoggedUtente()){
            $utente = $session->getUtente();
            $idutente = $utente->getId();
            $pm = FPersistentManager::getInstance();
            $ret1 = $pm->loadById("ricetta",$idricetta);
            $ret1->decrementaSalvataggi();
            $n = $ret1->getNsalvataggi();
            $pm->update("ricetta", $idricetta, "nsalvataggi", $n);
            $esito = $pm->deleteUtPrefRic($idricetta, $idutente);
            //devo aggiornare l'oggetto utente nei dati di sessione
            $utente = $pm->loadById("utente", $idutente);
            $session->setUtenteLoggato($utente);
            if($esito){
                //rimozione corretta, redirect alla pagina dei preferiti dell'utente se la rimozione avviene dai preferiti,
                // redirect alla ricetta stessa se la rimozione avviene dalla ricetta
                $referer = $_SERVER['HTTP_REFERER']; //indirizzo che stavo visitando quando ho rimosso dai preferiti
                $loc = substr($referer, strpos($referer, "/myRecipes")); //recupero parte path

                header('Location: '.$loc); //reindirizzamento al referer

            }
            else {
                //messaggio errore rimozione dai preferiti non corretta
                $viewerr = new VErrore();
                $viewerr->mostraErrore("Rimozione dai preferiti non corretta");
            }
        } else { //utente non loggato redirect a login
            header('Location: /myRecipes/web/Utente/Login');
        }

    }






}
?>
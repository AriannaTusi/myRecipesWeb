<?php
require_once('Smarty/smarty-libs/libs/Smarty.class.php');
/** class VRisultati gestisce l'input/output che permette di mostrare all'utente i risultati della ricerca secondo le
 *modalità disponibili
 *
 */
class VRisultati
{
    private $smarty;

    public function __construct()
    {
        $this->smarty = new Smarty();
        $this->smarty->setTemplateDir('Smarty/smarty-dir/templates');
        $this->smarty->setCompileDir('Smarty/smarty-dir/templates_c');
        $this->smarty->setCacheDir('Smarty/smarty-dir/cache');
        $this->smarty->setConfigDir('Smarty/smarty-dir/configs');

    }

    /**
     * Metodo che recupera l'array di idcibo inviati con la form
     * @return array|mixed array di id inseriti
     */
    public function recuperaIngredienti(){
        $arrayid=array();
        if(isset($_POST['cibi'])){
            $arrayid = $_POST['cibi']; //recupero array di id cibi inviati con la form
        }
        return $arrayid;
    }

    /**
     * Metodo che recupera i filtri inseriti nella form (si impostano a null i campi non inseriti dall'utente)
     * @return array associativo con i filtri
     */
    public function recuperaFiltri(){
        $filtri = array();
        if(isset($_POST['tprep'])) {
            $filtri['tprep'] = $_POST['tprep'];
        } else{
            $filtri['tprep'] = null;
        }
        if(isset($_POST['diff'])) {
            $filtri['diff'] = $_POST['diff'];
        } else{
            $filtri['diff'] = null;
        }
        if(isset($_POST['cat'])) {
            $filtri['cat'] = $_POST['cat'];
        } else{
            $filtri['cat'] = null;
        }
        return $filtri;
    }


    /**
     * Metodo per recuperare il nome inserito nella barra di ricerca
     * @return $nome inserito
     */
    public function recuperaNome(){
        $nome = "";
        if(isset($_POST['nomericetta'])){
            $nome = $_POST['nomericetta'];
        }
        return $nome;
    }

    /**
     * Metodo per mostrare i risultati di una ricerca
     * @param $risultati della ricerca
     * @param $msg messaggio vuoto se ci sono ricette, non vuoto se non ci sono ricette
     */
    public function mostraRisultati($risultati, $msg){

        //passaggio dei risultati a smarty per mostrare i risultati della ricerca (if utenti loggati e non)
        $session = Sessione::getInstance();
        if($risultati!=null){
            foreach ($risultati as $ricetta)
            {
                $img=$ricetta->getImmagine();
                $img->setData(base64_encode($img->getData()));
                $ricetta->setImmagine($img);
            }
        }
        if($session->isLoggedUtente()){
            $this->smarty->assign('risultati', $risultati);
            $this->smarty->assign('msg', $msg);
            $this->smarty->display('RisultatiRicercaUtReg.tpl');

        } else {
            $this->smarty->assign('risultati', $risultati);
            $this->smarty->assign('msg', $msg);
            $this->smarty->display('RisultatiRicercaUtNonReg.tpl');

        }
    }



}
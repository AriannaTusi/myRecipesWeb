<?php
require_once('Smarty/smarty-libs/libs/Smarty.class.php');

/** class VDettaglio che gestisce l'input/output per mostrare il dettaglio della ricetta e l'inserimento dei commenti*/
class VDettaglio
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
     * Metodo che mostra la ricetta
     * @param ERicetta da mostrare
     * @param bool $preferita informazione se la ricetta è preferita o no dall'utente
     * @param $arrcommenti array associativo con i commenti e info sugli utenti che li hanno pubblicati
     *
     */
    public function mostraRicetta($ricetta, $preferita, $arrcommenti, $errore){

        //passaggio a smarty per mostrare la ricetta (if per utenti loggati e non)

        $session = Sessione::getInstance();
        $img=$ricetta->getImmagine();
        $img->setData(base64_encode($img->getData()));
        $ricetta->setImmagine($img);
        $gallery = $ricetta->getImgpreparazione();


        foreach ($gallery as $g){
            $g->setData(base64_encode($g->getData()));
        }
        $ricetta->setImgpreparazione($gallery);
        if($session->isLoggedUtente()){
            // la ricetta mostrata avrà il cuore pieno o vuoto a seconda se la ricetta che stiamo mostrando è preferita
            // o no dall'utente (passiamo l'informazione a smarty che fa la if)
            //passaggio ricetta a smarty per utente loggato piu informazione se la ricetta è preferita o no
            $this->smarty->assign("errore",$errore);
            $this->smarty->assign("preferita",$preferita);
            $this->smarty->assign("ricetta",$ricetta);
            $this->smarty->assign("commenti",$arrcommenti);
            $this->smarty->display("DettaglioRicettaUtReg.tpl");


        } else {
            //passaggio ricetta a smarty per utenti non loggati
            $this->smarty->assign("errore",$errore);
            $this->smarty->assign("ricetta",$ricetta);
            $this->smarty->assign("commenti",$arrcommenti);
            $this->smarty->display("DettaglioRicettaUtNonReg.tpl");


        }
    }


    /**
     * Metodo che recupera il commento inserito dall'utente alla ricetta
     * @return mixed
     */
    public function recuperaCommento(){
        $commento['testo'] = $_POST['testo'];
        date_default_timezone_set('CET');
        $data = date ("Y-m-d H:m:s");
        $arr = explode(" ", $data);
        $commento['data'] = $arr[0];
        $commento['ora'] = $arr[1];
        return $commento;
    }


}
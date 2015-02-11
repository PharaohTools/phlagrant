<?php

Namespace Model;

class UpImportBaseBoxAllOS extends BaseFunctionModel {

    // Compatibility
    public $os = array("any") ;
    public $linuxType = array("any") ;
    public $distros = array("any") ;
    public $versions = array("any") ;
    public $architectures = array("any") ;

    // Model Group
    public $modelGroup = array("ImportBaseBox") ;

    public function performImport() {
        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params) ;
        $logging->log("Importing Base Box...") ;
        $baseBoxPath = $this->findBaseBox();
        // if its null, we don't have the box yet, so box add it
        if (is_null($baseBoxPath)) {
            $logging->log("Base Box {$this->virtualizerfile->config["vm"]["box"]} doesn't exist locally, adding...") ;
            $boxFactory = new \Model\Box();
            $boxParams = $this->params ;
            $boxParams["source"] = $this->getRemoteSource() ;
            $boxParams["guess"] = true ; // guess target
            if (strpos($this->virtualizerfile->config["vm"]["box"], "/") != false) {
                $name = substr($this->virtualizerfile->config["vm"]["box"], strpos($this->virtualizerfile->config["vm"]["box"], "/")) ;
                $logging->log("Guessing name $name ...") ; }
            else {
                $name = substr($this->virtualizerfile->config["vm"]["box"], strpos($this->virtualizerfile->config["vm"]["box"], "/")) ;
                $logging->log("Guessing name $name ...") ; }
            $boxParams["name"] = $name ;
            $box = $boxFactory->getModel($boxParams) ;
            $box->performBoxAdd() ;
            $baseBoxPath = $this->findBaseBox(); }
        $ovaFile = $this->findOVAFile($baseBoxPath) ;
        $out = $this->doImport($ovaFile) ;
        return $out ;
    }

    protected function getRemoteSource() {
        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params) ;
        $home_url = "http://www.virtualizerboxes.co.uk/" ;
        if (isset($this->virtualizerfile->config["vm"]["box_url"])) {
            $source = $this->virtualizerfile->config["vm"]["box_url"] ;
            $logging->log("Using explicit Box URL {$this->virtualizerfile->config["vm"]["box_url"]} from Virtualizerfile...") ; }
        else if (strpos($this->virtualizerfile->config["vm"]["box"], "/") != false) {
            $source = $home_url.$this->virtualizerfile->config["vm"]["box"] ;
            $logging->log("Guessing Box URL {$home_url}{$this->virtualizerfile->config["vm"]["box"]} ...") ; }
        else {
            $source = $home_url.'virtualizer/'.$this->virtualizerfile->config["vm"]["box"] ;
            // @todo dont DS this its a URL
            $logging->log("Guessing Box URL {$home_url}virtualizer/{$this->virtualizerfile->config["vm"]["box"]} ...") ; }
        return $source ;
    }

    protected function findBaseBox() {
        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params) ;
        $logging->log("Finding base box {$this->virtualizerfile->config["vm"]["box"]} from Virtualizerfile") ;
        $dirscan = BOXDIR ;
        if (file_exists(BOXDIR)) {
            $logging->log("Found base box directory ".BOXDIR) ;
            $filesInDir = scandir(BOXDIR) ;
            $boxes = array() ;
            foreach ($filesInDir as $fileInDir) {
                if (in_array($fileInDir, array(".", ".."))) { continue ; }
                if (is_dir($dirscan.DS.$fileInDir)) { $boxes[] = $fileInDir ; } }
            foreach ($boxes as $box) {
                if ($box == $this->virtualizerfile->config["vm"]["box"]) {
                    $logging->log("Found base box {$box}") ;
                    return $dirscan.DS.$box ; } } }
        else {
            $logging->log("No base box directory ".BOXDIR) ;

        }
        return null ;
    }

    protected function findOVAFile($baseBox) {
        $ovaFile = $baseBox.DS.'box.ova' ;
        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params) ;
        $logging->log("Finding OVA file $ovaFile") ;
        if (file_exists($ovaFile)) {
            $logging->log("Found OVA file {$ovaFile}") ;
            return $ovaFile ; }
        return null ;
    }

    protected function doImport($ovaFile) {
        $this->loadFiles();
        $this->findProvider("UpImport");
        return $this->provider->import($ovaFile, $this->virtualizerfile->config["vm"]["ostype"], $this->virtualizerfile->config["vm"]["name"]);
    }

}
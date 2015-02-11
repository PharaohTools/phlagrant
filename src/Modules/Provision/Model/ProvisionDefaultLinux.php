<?php

Namespace Model ;

class ProvisionDefaultLinux extends Base {

    public $virtualizerfile;
    public $papyrus ;
    protected $provisionModel ;

    public function provision($hook = "") {
        $provisionOuts = array() ;
        if ($hook != "") {$hook = "_$hook" ; }
        foreach ($this->virtualizerfile->config["vm"]["provision$hook"] as $provisionerSettings) {
            $provisionOuts[] = $this->doSingleProvision($provisionerSettings) ; }
        return $provisionOuts ;
    }

    public function provisionHook($hook, $type) {
        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params) ;
        $logging->log("Provisioning from Virtualizerfile settings if available for $hook $type") ;
        $provisionOuts = $this->provisionVirtualizerfile($hook, $type) ;
        $logging->log("Provisioning from hook directories if available for $hook $type") ;
        $provisionOuts = array_merge($provisionOuts, $this->provisionHookDirs($hook, $type)) ;
        return $provisionOuts ;
    }

    protected function provisionVirtualizerfile($hook, $type) {
        $provisionOuts = array() ;
        if (isset($this->virtualizerfile->config["vm"]["provision_{$hook}_{$type}"]) &&
            count($this->virtualizerfile->config["vm"]["provision_{$hook}_{$type}"])>0){
            foreach ($this->virtualizerfile->config["vm"]["provision_{$hook}_{$type}"] as $provisionerSettings) {
                $provisionOuts[] = $this->doSingleProvision($provisionerSettings) ; } }
        return $provisionOuts ;
    }

    protected function provisionHookDirs($hook, $type) {
        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params) ;
        $provisionOuts = array() ;
        // @todo this will do for now but should be dynamic
        $provisioners = array("PharaohTools", "Shell") ;
        foreach ($provisioners as $provisioner) {
            // echo "dave a2\n" ;
            // @todo this will do for now but should be dynamic
            $tools = array("cleopatra", "dapperstrano", "shell") ;
            foreach ($tools as $tool) {
                // echo "dave a3\n" ;
                $targets = array("host", "guest") ;
                foreach ($targets as $target) {
                    $dir = getcwd().DS."build".DS."config".DS."virtualizer".DS."hooks".DS."$provisioner".DS.
                        "$tool".DS."$hook".DS."$target".DS."$type" ;
                    $hookDirectoryExists = file_exists($dir) ;
                    $hookDirectoryIsDir = is_dir($dir) ;
                    // var_dump("hde", $hookDirectoryExists, "hdd", $hookDirectoryIsDir) ;
                    // echo "dave a4 $dir\n" ;
                    if ($hookDirectoryExists) {
                        $relDir = str_replace(getcwd(), "", $dir) ;
                        $logging->log("Virtualizer hook directory $relDir found") ;
                        $hookDirFiles = scandir($dir) ;
                        // echo "dave a5 x ".implode(" ", $hookDirFiles) ;
                        foreach ($hookDirFiles as $hookDirFile) {
                            // echo "dave a6\n" ;
                            if (substr($hookDirFile, strlen($hookDirFile)-4) == ".php") {
                                $logging->log("Virtualizer hook file $dir".DS."$hookDirFile found") ;
                                $provisionerSettings =
                                    array(
                                        "provisioner" => $provisioner,
                                        "tool" => $tool,
                                        "target" => $target,
                                        "script" => "$dir".DS."$hookDirFile"
                                    );
                                $provisionOuts[] = $this->doSingleProvision($provisionerSettings) ;
                                $logging->log("Executing $hookDirFile with $tool") ; } } } } } }
        return $provisionOuts ;
    }


    // @todo this should support other provisioners than pharaoh, provide some override here to allow
    // @todo chef solo, puppet agent, salt or ansible to get invoked
    protected function doSingleProvision($provisionerSettings) {
        $pharaohSpellings = array("Pharaoh", "pharaoh", "PharaohTools", "pharaohTools", "Pharaoh", "pharaoh", "PharaohTools", "pharaohTools") ;
        if (in_array($provisionerSettings["provisioner"], $pharaohSpellings)) {
            $provisionObjectFactory = new \Model\PharaohTools() ; }
        else if (in_array($provisionerSettings["provisioner"], array("shell", "bash", "Shell", "Bash"))) {
            $provisionObjectFactory = new \Model\Shell() ; }
        $provisionObject = $provisionObjectFactory->getModel($this->params, "Provision");
        $provisionObject->virtualizerfile = $this->virtualizerfile;
        $provisionObject->papyrus = $this->papyrus;
        return $provisionObject->provision($provisionerSettings, $this) ;
    }

}

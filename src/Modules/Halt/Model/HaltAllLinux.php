<?php

Namespace Model;

class HaltAllLinux extends BaseLinuxApp {

    // Compatibility
    public $os = array("Linux") ;
    public $linuxType = array("any") ;
    public $distros = array("any") ;
    public $versions = array("any") ;
    public $architectures = array("any") ;

    // Model Group
    public $modelGroup = array("Default") ;

    protected $phlagrantfile;
    protected $papyrus ;

    public function __construct($params) {
        parent::__construct($params);
        $this->initialize();
    }

    public function haltNow() {
        $this->loadFiles();
        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params);
        $logging->log("Attempting soft power off by button...") ;
        $logging->log("Waiting up to {$this->phlagrantfile->config["vm"]["graceful_halt_timeout"]} seconds for machine to power off...") ;
        $command = "vboxmanage controlvm {$this->phlagrantfile->config["vm"]["name"]} acpipowerbutton" ;
        $this->executeAndOutput($command);
        if ($this->waitForStatus("powered off", $this->phlagrantfile->config["vm"]["graceful_halt_timeout"], "3")==true) {
            $logging->log("Successful soft power off by button...") ;
            return true ; }
        else {
            $logging->log("Failed soft power off by button, attempting SSH shutdown.") ;

            $sshParams = $this->params ;

            $srv = array(
                "user" => $this->papyrus["username"] ,
                "password" => $this->papyrus["password"] ,
                "target" => $this->papyrus["target"] );
            $sshParams["yes"] = true ;
            $sshParams["guess"] = true ;
            $sshParams["servers"] = serialize(array($srv)) ;
            $sshParams["ssh-data"] = "echo {$this->phlagrantfile->config["ssh"]["password"]} | sudo -S shutdown now\n";

            if (isset($this->phlagrantfile->config["ssh"]["port"])) {
                $sshParams["port"] = $this->phlagrantfile->config["ssh"]["port"] ; }
            if (isset($this->phlagrantfile->config["ssh"]["timeout"])) {
                $sshParams["timeout"] = $this->phlagrantfile->config["ssh"]["timeout"] ; }
            $sshFactory = new \Model\Invoke();
            $ssh = $sshFactory->getModel($sshParams) ;
            $ssh->performInvokeSSHData() ;

            $logging->log("Attempting shutdown by SSH...") ;
            $logging->log("Waiting up to {$this->phlagrantfile->config["vm"]["ssh_halt_timeout"]} seconds for machine to power off...") ;

            if ($this->waitForStatus("powered off", $this->phlagrantfile->config["vm"]["ssh_halt_timeout"], "3")==true) {
                $logging->log("Successful power off SSH Shutdown...") ;
                return true ; } }
        if (isset($this->params["fail-hard"])) {
            $lmsg = "Attempts to Halt this box by both Soft Power off and SSH Shutdown have failed. You have used the " .
                " parameter --fail-hard to do hard power off now." ;
            $logging->log($lmsg) ;
            $command = "vboxmanage controlvm {$this->phlagrantfile->config["vm"]["name"]} poweroff" ;
            $this->executeAndOutput($command);
            return true ; }
        $lmsg = "Attempts to Halt this box by both Soft Power off and SSH Shutdown have failed. You may need to use ".
            "phlagrant halt hard. You can also use the parameter --fail-hard to do this automatically." ;
        $logging->log($lmsg) ;
        return false ;

    }

    public function haltPause() {
        $this->loadFiles();
        $command = "vboxmanage controlvm {$this->phlagrantfile->config["vm"]["name"]} pause" ;
        $this->executeAndOutput($command);
    }

    public function haltHard() {
        $this->loadFiles();
        $command = "vboxmanage controlvm {$this->phlagrantfile->config["vm"]["name"]} poweroff" ;
        $this->executeAndOutput($command);
    }

    # @todo in_array or something to check a sane status was requested
    protected function waitForStatus($statusRequested, $total_time, $interval) {
        for ($i=0; $i<$total_time; $i=$i+$interval) {
            if($this->isVMInStatus($statusRequested)) {
                return true ; }
            echo "." ;
            sleep($interval); }
        echo "\n" ;
        return false ;
    }

    protected function isVMInStatus($statusRequested) {
        $command = "vboxmanage showvminfo \"{$this->phlagrantfile->config["vm"]["name"]}\" | grep \"State:\"  " ;
        $out = $this->executeAndLoad($command);
        $isStatusRequested = strpos($out, strtolower($statusRequested)) ;
        return $isStatusRequested ;
    }

    protected function loadFiles() {
        $this->phlagrantfile = $this->loadPhlagrantFile();
        $this->papyrus = $this->loadPapyrusLocal();
    }

    protected function loadPhlagrantFile() {
        $prFactory = new \Model\PhlagrantRequired();
        $phlagrantFileLoader = $prFactory->getModel($this->params, "PhlagrantFileLoader") ;
        return $phlagrantFileLoader->load() ;
    }

    protected function loadPapyrusLocal() {
        $prFactory = new \Model\PhlagrantRequired();
        $papyrusLocalLoader = $prFactory->getModel($this->params, "PapyrusLocalLoader") ;
        return $papyrusLocalLoader->load($this->phlagrantfile->config["vm"]["name"]) ;
    }

}
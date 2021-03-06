<?php

Namespace Model;

class InvokeAllOS extends Base {

	// Compatibility
	public $os = array("any");
	public $linuxType = array("any");
	public $distros = array("any");
	public $versions = array("any");
	public $architectures = array("any");

	// Model Group
	public $modelGroup = array("Default");

	protected $servers = array();
	protected $sshCommands;
	protected $isNativeSSH;

	public function __construct($params = null) {
		parent::__construct($params) ;
	}

	public function askWhetherToInvokeSSHShell() {
		return $this->performInvokeSSHShell();
	}

	public function askWhetherToInvokeSSHScript() {
		return $this->performInvokeSSHScript();
	}

	public function askWhetherToInvokeSSHData() {
		return $this->performInvokeSSHData();
	}

	public function performInvokeSSHShell() {
		if ($this->askForSSHShellExecute() != true) {
			return false; }
		$this->populateServers();
		$commandExecution = true;
        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params);
        if (count($this->servers) > 0) {
            $logging->log("Opening CLI...", $this->getModuleName()) ;
            while ($commandExecution == true) {
                $command = $this->askForACommand();
                if ($command == false) {
                    $commandExecution = false; }
                else {
                    foreach ($this->servers as &$server) {
                        $custom_port = ($server["port"]=="22") ? "" : ":".$server["port"] ;
                        $logging->log( "[" . $server["target"] . "$custom_port] Executing $command...", $this->getModuleName()) ;
                        $rc = $this->doSSHCommand($server["ssh2Object"], $command);
                        if ($rc == 0) {
                            $logging->log(  "[" . $server["target"] . "$custom_port] $command Completed...", $this->getModuleName()) ; }
                        else {
                            $logging->log(  "[" . $server["target"] . "$custom_port] $command Failed with exit status {$rc}...",
                                $this->getModuleName(),
                                LOG_FAILURE_EXIT_CODE ) ; } } } } }
        else {
            $logging->log("No successful connections available", $this->getModuleName()) ;
            \Core\BootStrap::setExitCode(1) ;
            return false ; }
        $logging->log("Shell Completed", $this->getModuleName()) ;
		return true;
	}

	public function performInvokeSSHScript() {
		if ($this->askForSSHScriptExecute() != true) {
			return false; }
		$scriptLoc = $this->askForScriptLocation();
		$this->populateServers();
		$this->sshCommands = explode("\n", file_get_contents($scriptLoc));
        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params);
        if (count($this->servers) > 0) {
            $logging->log("Opening CLI...", $this->getModuleName()) ;
            foreach ($this->sshCommands as $sshCommand) {
                foreach ($this->servers as &$server) {
                    $custom_port = ($server["port"]=="22") ? "" : ":".$server["port"] ;
                    if (isset($server["ssh2Object"]) && is_object($server["ssh2Object"])) {
                        $logging->log(  "[" . $server["target"] . "$custom_port] Executing $sshCommand...", $this->getModuleName()) ;
                        $rc = $this->doSSHCommand($server["ssh2Object"], $sshCommand);
                        if ($rc == 0) {
                            $logging->log(  "[" . $server["target"] . "$custom_port] $sshCommand Completed...", $this->getModuleName()) ; }
                        else {

                            $logging->log(  "[" . $server["target"] . "$custom_port] $sshCommand Failed with exit status {$rc}...",
                                $this->getModuleName(),
                                LOG_FAILURE_EXIT_CODE ) ; } }
                    else {
                        $logging->log(
                            "[" . $server["target"] . "$custom_port] Connection failure. Will not execute commands on this box...",
                            $this->getModuleName() ) ;
                        if (!isset($this->params["ignore-connection-failures"]) ||
                            $this->params["ignore-connection-failures"]==false) {
                            $logging->log(
                                "No ignore-connection-failures flag set. SSH Invoke Failure.",
                                $this->getModuleName(), LOG_FAILURE_EXIT_CODE ) ;
                            return false ; } } } } }
        else {
            $logging->log("No successful connections available", $this->getModuleName()) ;
            \Core\BootStrap::setExitCode(1) ;
            return false ; }
        $logging->log("Script by SSH Completed", $this->getModuleName()) ;
		return true;
	}

	public function performInvokeSSHData() {
		if ($this->askForSSHDataExecute() != true) {
			return false; }
		$data = $this->askForSSHData();
		$this->populateServers();
		$this->sshCommands = explode("\n", $data);
        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params);
        if (count($this->servers) > 0) {
            $logging->log("Opening CLI...", $this->getModuleName()) ;
            foreach ($this->sshCommands as $sshCommand) {
                if ($sshCommand === "") continue ;
                foreach ($this->servers as &$server) {
                    $custom_port = ($server["port"]=="22") ? "" : ":".$server["port"] ;
                    if (isset($server["ssh2Object"]) && is_object($server["ssh2Object"])) {
                        $logging->log(  "[" . $server["target"] . "$custom_port] Executing $sshCommand...", $this->getModuleName()) ;
                        $rc = $this->doSSHCommand($server["ssh2Object"], $sshCommand);
                        if ($rc == 0) {
                            $logging->log(  "[" . $server["target"] . "$custom_port] $sshCommand Completed...", $this->getModuleName()) ; }
                        else {

                            $logging->log(  "[" . $server["target"] . "$custom_port] $sshCommand Failed with exit status {$rc}...",
                                $this->getModuleName(),
                                LOG_FAILURE_EXIT_CODE ) ; } }
                    else {
                        $logging->log(  "[" . $server["target"] . "$custom_port] Connection failure. Will not execute commands on this box...", $this->getModuleName()) ; } } }        }
        else {
            $logging->log("No successful connections available", $this->getModuleName(), LOG_FAILURE_EXIT_CODE) ;
            return false ; }
        $logging->log( "Data by SSH Completed", $this->getModuleName()) ;;
		return true;
	}

	public function populateServers() {
		$this->askForTimeout();
		$this->askForPort();
		$this->loadServerData();
		$this->loadSSHConnections();
	}

	protected function loadServerData() {
        // @todo if the below is emoty we have no server to connect to so should not continue
		$allProjectEnvs = \Model\AppConfig::getProjectVariable("environments");
		if (isset($this->params["servers"])) {
			$this->servers = unserialize($this->params["servers"]); }
        else {
            if (isset($this->params["env"]) && !isset($this->params["environment-name"] )) {
                $this->params["environment-name"] =$this->params["env"] ; }
			if (isset($this->params["environment-name"])) {
				$names = $this->getEnvironmentNames($allProjectEnvs);
				$this->servers = $allProjectEnvs[ $names[ $this->params["environment-name"] ] ]["servers"]; }
            else {
				if (count($allProjectEnvs) > 0) {
					$question = 'Use Environments Configured in Project?';
					$useProjEnvs = self::askYesOrNo($question, true);
					if ($useProjEnvs == true) {
						$this->servers = new \ArrayObject($allProjectEnvs);
                        return; } }
                else {
					$this->askForServerInfo(); } } }
	}

	protected function getEnvironmentNames($envs) {
		$eNames = array();
		foreach ($envs as $envKey => $env) {
			$envName = $env["any-app"]["gen_env_name"];
			$eNames[ $envName ] = $envKey; }
		return $eNames;
	}

	protected function loadSSHConnections() {
		$loggingFactory = new \Model\Logging();
		$logging = $loggingFactory->getModel($this->params);
		$logging->log("Attempting to load SSH connections...", $this->getModuleName()) ;
        $current_error_level = error_reporting();
        error_reporting(0) ;
		foreach ($this->servers as $srvId => &$server) {
			if (isset($this->params["environment-box-id-include"])) {
				if ($srvId != $this->params["environment-box-id-include"]) {
					$logging->log("Skipping {$server["name"]} for box id Include constraint", $this->getModuleName()) ;
					continue; } }
			if (isset($this->params["environment-box-id-ignore"])) {
				if ($srvId == $this->params["environment-box-id-ignore"]) {
					$logging->log("Skipping {$server["name"]} for box id Ignore constraint", $this->getModuleName()) ;
					continue; } }
			$attempt = $this->attemptSSH2Connection($server);
			if ($attempt == null || $attempt == false) {
                $logging->log("Connection to Server {$server["target"]} failed. Removing from pool.", $this->getModuleName(), LOG_FAILURE_EXIT_CODE) ;
                unset($this->servers[$srvId]);
                return false ;}
            else {
				$server["ssh2Object"] = $attempt;
				$logging->log("Connection to Server {$server["target"]} successful.", $this->getModuleName()) ;
//				echo $this->changeBashPromptToPharaoh($server["ssh2Object"]);
//				if (!isset($this->isNativeSSH) || (isset($this->isNativeSSH) && $this->isNativeSSH != true)) {
//
//				}
//                var_dump('serv', $server, $server["target"]) ;
                $test_comm = 'echo "Pharaoh Remote SSH on ...' . $server["target"] . '"' ;
				$rc = $this->doSSHCommand($server["ssh2Object"], $test_comm, true);
                echo ($rc == 0) ? 'Success ' : 'Failure '; } }
        error_reporting($current_error_level) ;
		return true;
	}

    protected function attemptSSH2Connection($server) {
        $pword = (isset($server["pword"])) ? $server["pword"] : false;
        $pword = (isset($server["password"])) ? $server["password"] : $pword;
        $invokeFactory = new \Model\Invoke() ;

        $serverObj = $invokeFactory->getModel($this->params, "Server") ;
        $serverObj->init($server['target'], $server['user'], $pword, isset($server['port']) ? $server['port'] : 22);
//      $server = new \Invoke\Server();
//		$driverString = isset($this->params["driver"]) ? $this->params["driver"] : 'seclib';
//      $options = array("os" => "DriverBashSSH", "native" => "DriverNativeSSH", "seclib" => "DriverSecLib") ;
        $driver = $this->findUsableDriver($serverObj) ;
        if ($driver !==false && $driver !==null) {
                return $driver ;
            }
            else {
                return false ;
            }
    }

    protected function findUsableDriver($serverObj) {
        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params);
        $driverString = $this->findRequestedDriverString() ;
        $invokeFactory = new \Model\Invoke() ;
        $driver = $invokeFactory->getModel($this->params, $driverString) ;
        if (is_object($driver)) {
            $logging->log("Found Requested Driver {$driverString}...", $this->getModuleName()) ;
            $driver->setServer($serverObj) ;
            $serverObj->setDriver($driver) ;
            $conn = $this->tryConnection($driver, $serverObj) ;
                if ($conn == true ) {
                    $logging->log("Test Connection Successful...", $this->getModuleName()) ;
                    return $driver; }
                else if ($conn == false) {
                    $logging->log("Test Connection Failed...", $this->getModuleName()) ; }
                else if ($conn == null) {
                    $logging->log("Test Connection Unusable...", $this->getModuleName()) ; }
                else {
                    $logging->log("Test Connection Result Unknown...", $this->getModuleName()) ; } }
        else {
            $logging->log("Unable to find Requested Driver {$driverString}...", $this->getModuleName()) ; }
        $use_default = true ;
        if (isset($this->params["force-driver"]) && $this->params["force-driver"]==true) {
            $use_default = false ; }
        if ($use_default == true) {
            $loggingFactory = new \Model\Logging();
            $logging = $loggingFactory->getModel($this->params);
            $logging->log("Unable to use requested driver, switching to default...", $this->getModuleName()) ;
            $driver = $this->findDefaultDriver() ;
            return $driver ; }
        else {
            $logging->log("Unable to use requested driver, switching to default is disabled...", $this->getModuleName(), LOG_FAILURE_EXIT_CODE) ;
            return false ; }
    }

    private function tryConnection($driver, $serverObj) {
//        var_dump($driver, $serverObj) ;
        $driver->setServer($serverObj);
        $serverObj->setDriver($driver);

        $connection_attempts = 10 ;
        $interval = 5 ;
        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params);

        if (isset($this->params["retries"])) {
            $logging->log("Trying up to {$this->params["retries"]} times as specified by Virtufile...", $this->getModuleName()) ;
            $connection_attempts = $this->params["retries"] ; }
        if (isset($this->params["interval"])) {
            $logging->log("Interval of {$this->params["interval"]} seconds as specified by Virtufile...", $this->getModuleName()) ;
            $interval = $this->params["interval"] ; }

        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params);
        for ($i=1; $i < $connection_attempts ; $i++) {
            $logging->log("Connection attempt {$i}...", $this->getModuleName()) ;
            $conn = $serverObj->connect() ;
            if ($conn == true ) {
                $logging->log("Connection attempt {$i} Successful...", $this->getModuleName()) ;
                return $driver; }
            else if ($conn == false) {
                $logging->log("Connection attempt {$i} Failed...", $this->getModuleName()) ; }
            else if ($conn == null) {
                $logging->log("Connection unusable...", $this->getModuleName()) ;
                return null ; }
            sleep($interval) ; }
        return null ;
    }

    private function findRequestedDriverString() {
        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params);
        $optionsKeep = array("os" => "DriverBashSSH", "native" => "DriverNativeSSH", "seclib" => "DriverSecLib") ;
        $optionsAsk = array_keys($optionsKeep) ;
        $system = new \Model\SystemDetectionAllOS() ;
        if (isset($this->params["driver"]) && in_array($this->params["driver"], $optionsAsk) ) {
            if (in_array($system->os, array("WINNT", "Windows")) && $this->params["driver"] == "os") {
                $logging->log("Windows does not support requested OS level SSH driver, switching to seclib...", $this->getModuleName()) ;
                return "DriverSecLib" ; }
            $logging->log("Attempting to use requested {$optionsKeep[$this->params["driver"]]} driver...", $this->getModuleName()) ;
            return $optionsKeep[$this->params["driver"]]; }
        return false ;
    }

    private function findDefaultDriver() {
        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params);
        $system = new \Model\SystemDetectionAllOS() ;
        $invokeFactory = new \Model\Invoke() ;
        if (in_array($system->os, array("WINNT", "Windows"))) {
            $logging->log("Using default driver for Windows systems, Seclib SSH driver...", $this->getModuleName()) ;
            $driver = $invokeFactory->getModel($this->params, "DriverSecLib") ;
            return $driver ; }
        $logging->log("Using default driver for Non-Windows systems, Seclib SSH driver...", $this->getModuleName()) ;
        $driver = $invokeFactory->getModel($this->params, "DriverSecLib") ;
        return $driver ;
    }

	private function askForSSHShellExecute() {
		if (isset($this->params["yes"]) && $this->params["yes"] == true) {
			return true;  }
		$question = 'Invoke SSH Shell on Server group?';
		return self::askYesOrNo($question);
	}

	private function askForSSHScriptExecute() {
		if (isset($this->params["yes"]) && $this->params["yes"] == true) {
			return true; }
		$question = 'Invoke SSH Script on Server group?';
		return self::askYesOrNo($question);
	}

	private function askForSSHDataExecute() {
		if (isset($this->params["yes"]) && $this->params["yes"] == true) {
			return true;}
		$question = 'Invoke SSH Data on Server group?';
		return self::askYesOrNo($question);
	}

	private function askForScriptLocation() {
		if (isset($this->params["ssh-script"])) {
			return $this->params["ssh-script"]; }
		$question = 'Enter Location of bash script to execute';
		return self::askForInput($question, true);
	}

	private function askForSSHData() {
		if (isset($this->params["ssh-data"])) {
			return $this->params["ssh-data"]; }
		$question = 'Enter data to execute via SSH';
		return self::askForInput($question, true);
	}

	private function askForServerInfo() {
		$startQuestion = <<<QUESTION
***********************************
*   Due to a software limitation, *
*    The user that you use here   *
*  will have their command prompt *
*    changed to PHARAOHPROMPT     *
*  ... I'm working on that one... *
*  Exit program to stop (CTRL+C)  *
***********************************
Enter Server Info:

QUESTION;
		echo $startQuestion;
		$serverAddingExecution = true;
		while ($serverAddingExecution == true) {
			$server = array();
			$server["target"] = $this->askForServerTarget();
			$server["user"] = $this->askForServerUser();
			$server["pword"] = $this->askForServerPassword();
			$this->servers[] = $server;
			$question = 'Add Another Server?';
			if (count($this->servers) < 1) {
				$question .= "You need to enter at least one server\n"; }
			$serverAddingExecution = self::askYesOrNo($question);
		}
	}

	private function askForTimeout() {
		if (isset($this->params["timeout"])) {
			return; }
		$this->params["timeout"] = 100;
	}

	private function askForPort() {
		if (isset($this->params["port"])) {
			return; }
		// @todo guess is beig unset on creation of this object
		$this->params["port"] = 22;
		return;
	}

	private function askForServerTarget() {
		if (isset($this->params["ssh-target"])) {
			return $this->params["ssh-target"];	}
		$question = 'Please Enter SSH Server Target Host Name/IP';
		$input = self::askForInput($question, true);

		return $input;
	}

	private function askForServerUser() {
		if (isset($this->params["ssh-user"])) {
			return $this->params["ssh-user"]; }
		$question = 'Please Enter SSH User';
		$input = self::askForInput($question, true);
		return $input;
	}

	private function askForServerPassword()	{
		if (isset($this->params["ssh-key-path"])) {
			return $this->params["ssh-key-path"]; }
        else {
			if (isset($this->params["ssh-pass"])) {
				return $this->params["ssh-pass"]; } }
		$question = 'Please Enter Server Password or Key Path';
		$input = self::askForInput($question);
		return $input;
	}

	private function askForACommand() {
		$question = 'Enter command to be executed on remote servers? Enter none to close connection and end program';
		$input = self::askForInput($question);
		return ($input == "") ? false : $input;
	}

    protected function doSSHCommand($sshObject, $command, $first = null) {
        $out = $sshObject->exec($command);
        echo $out["data"] ;
        return $out['rc'] ;
    }

}
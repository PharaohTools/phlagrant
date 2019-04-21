<?php

Namespace Model;

class AutopilotYAMLAnyOS extends BaseLinuxApp {

    // Compatibility
    public $os = array("any") ;
    public $linuxType = array("any") ;
    public $distros = array("any") ;
    public $versions = array("any") ;
    public $architectures = array("any") ;

    // Model Group
    public $modelGroup = array("Default") ;

    public function __construct($params) {
        parent::__construct($params);
    }

    public function loopOurYAMLFile($file) {
        $lines = $this->loadFile($file) ;
        $new_steps = array() ;
        $new_vars = array() ;
        $total_line_count = count($lines) ;
        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params);
        $logging->log("About to parse $total_line_count lines from {$file}\n\n", $this->getModuleName()) ;
        $start_time = time() ;
        $date_format = date('H:i:s, d/m/Y', $start_time) ;
        $logging->log("Execution started at {$date_format}\n\n", $this->getModuleName()) ;
        $unformatted = yaml_parse_file($file) ;
        $formatted = $this->transformArray($unformatted) ;
        $transformed_autopilot = array("vars" => $new_vars, "steps" => $formatted) ;
        return $transformed_autopilot ;
    }


    public function transformArray($unformatted) {
        $transformed = [] ;
        foreach ($unformatted as $step) {
            $one_transformed_step = [] ;
            $modact_string = key($step) ;
            $modact = $this->getModuleAndAction($modact_string) ;
            $one_transformed_step['module'] = $modact['module'] ;
            $one_transformed_step['action'] = $modact['action'] ;
            $one_transformed_step['params'] = $step[$modact_string] ;
            $transformed[] = $one_transformed_step ;
        }
        return $transformed ;
    }

    public function loadFile($file_name) {
        $loggingFactory = new \Model\Logging();
        $logging = $loggingFactory->getModel($this->params);
        $logging->log("Loading YAML Autopilot File", $this->getModuleName()) ;
        if (!file_exists($file_name)) {
            $logging->log("Something bad happened. The file $file_name does not exist", $this->getModuleName(), LOG_FAILURE_EXIT_CODE) ;
            return false ; }
        $lines = file($file_name) ;
        if (count($lines)==0) {
            $logging->log("Something bad happened. The file has no lines", $this->getModuleName(), LOG_FAILURE_EXIT_CODE) ;
            return false ; }
        return $lines ;
    }

    public function getModuleAndAction($line) {
        $parts = explode('[', $line) ;
        $modact['module'] = $parts[0] ;
        $modact['action'] = substr($parts[1], 0, strlen($parts[1])-1) ;
        return $modact ;
    }

}
<?php

Namespace Model;

class BoxResume extends BaseVirtualboxAllOS {

    // Compatibility
    public $os = array("any") ;
    public $linuxType = array("any") ;
    public $distros = array("any") ;
    public $versions = array("any") ;
    public $architectures = array("any") ;

    // Model Group
    public $modelGroup = array("BoxResume") ;

    public function resume($name) {
        $command = VBOXMGCOMM." controlvm {$name} resume" ;
        $this->executeAndOutput($command);
    }

    public function getResumableStates() {
        return array("paused") ;
    }

}
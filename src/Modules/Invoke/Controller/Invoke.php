<?php

Namespace Controller ;

class Invoke extends Base {

    public function execute($pageVars) {

        $thisModel = $this->getModelAndCheckDependencies(substr(get_class($this), 11), $pageVars) ;
        // if we don't have an object, its an array of errors
        if (is_array($thisModel)) { return $this->failDependencies($pageVars, $this->content, $thisModel) ; }
	    $isDefaultAction = self::checkDefaultActions($pageVars, array(), $thisModel) ;
        if ( is_array($isDefaultAction) ) { return $isDefaultAction; }

        $action = $pageVars["route"]["action"];
        $this->content["route"] = $pageVars["route"] ;

        if ($action=="help") {
            $helpModel = new \Model\Help();
            $this->content["helpData"] = $helpModel->getHelpData($pageVars["route"]["control"]);
            return array ("type"=>"view", "view"=>"help", "pageVars"=>$this->content); }

        if ($action=="cli") {
            $this->content["shlResult"] = $this->content["result"] = $thisModel->askWhetherToInvokeSSHShell();
            return array ("type"=>"view", "view"=>"invoke", "pageVars"=>$this->content); }

        if ($action=="script") {
            $this->content["shlResult"] = $this->content["result"] = $thisModel->askWhetherToInvokeSSHScript();
            return array ("type"=>"view", "view"=>"invoke", "pageVars"=>$this->content); }

        if ($action=="data") {
            $this->content["shlResult"] = $this->content["result"] = $thisModel->askWhetherToInvokeSSHData();
            return array ("type"=>"view", "view"=>"invoke", "pageVars"=>$this->content); }

        \Core\BootStrap::setExitCode(1);
        $this->content["messages"][] = "Action $action is not supported by ".get_class($this)." Module";
        return array ("type"=>"control", "control"=>"index", "pageVars"=>$this->content);

    }

}

<?php

Namespace Controller ;

class Provision extends Base {

    public function execute($pageVars) {

        $thisModel = $this->getModelAndCheckDependencies(substr(get_class($this), 11), $pageVars) ;
        // if we don't have an object, its an array of errors
        if (is_array($thisModel)) { return $this->failDependencies($pageVars, $this->content, $thisModel) ; }

        $action = $pageVars["route"]["action"];

        if ($action=="help") {
            $helpModel = new \Model\Help();
            $this->content["helpData"] = $helpModel->getHelpData($pageVars["route"]["control"]);
            return array ("type"=>"view", "view"=>"help", "pageVars"=>$this->content); }

        if ($action=="now") {
            $this->content["result"] = $thisModel->provisionNow();
            return array ("type"=>"view", "view"=>"provision", "pageVars"=>$this->content); }

        $this->content["messages"][] = "Invalid Provision Action";
        return array ("type"=>"control", "control"=>"index", "pageVars"=>$this->content);

    }

}
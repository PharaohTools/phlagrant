<?php

Namespace Info;

class InvokeInfo extends PTConfigureBase {

    public $hidden = false;

    public $name = "SSH Invocation Functions";

    public function _construct() {
      parent::__construct();
    }

    public function routesAvailable() {
      return array( "Invoke" => array_merge(parent::routesAvailable(), array("cli", "script", "data") ) );
    }

    public function routeAliases() {
      return array("invoke" => "Invoke", "inv" => "Invoke");
    }

  public function helpDefinition() {
      $help = <<<"HELPDATA"
  This command is part of Core and handles SSH Connection Functions.

  Invoke, invoke, inv

        - cli
        Will ask you for details for servers, then open a shell for you to execute on multiple servers
        example: ptconfigure invoke cli --environment-name=staging --ssh-driver=seclib

        - script
        Will ask you for details for servers, then execute each line of a provided script file on the remote/s
        example: ptconfigure invoke script --ssh-script="/var/www/project/script.sh" --environment-name=staging --ssh-driver=os

        - data
        Execute php variable data on one or more remotes
        example: ptconfigure invoke data --ssh-data="ls -lah" --environment-name=staging  --ssh-driver=native

HELPDATA;
      return $help ;
    }

}
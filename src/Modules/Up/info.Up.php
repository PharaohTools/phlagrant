<?php

Namespace Info;

class UpInfo extends PTConfigureBase {

  public $hidden = false;

  public $name = "Up - Create and Start a Virtualize Box";

  public function __construct() {
    parent::__construct();
  }

  public function routesAvailable() {
    return array( "Up" =>  array_merge( array("now") ) );
  }

  public function routeAliases() {
    return array("up"=>"Up");
  }

  public function helpDefinition() {
    $help = <<<"HELPDATA"
  This command allows you to create, start and provision ptvirtualize boxes.

  Up, up

        - now
        Bring up a box now
        example: ptvirtualize up now
        example: ptvirtualize up now --modify # modify the hardware settings to match the Virtufile during the up phase.
            Without it, the machine will be brought up with its previous settings. On creating new machines this will
            happen automatically regardless of the parameter.
        example: ptvirtualize up now --mod # Alias to modify
        example: ptvirtualize up now --provision # provision an existing machine with the configuration scripts specified
            in the Virtufile. Without it, the machine will be brought up with its previous config. On creating
            new machines this will happen automatically regardless of the parameter.
        example: ptvirtualize up now --pro # Alias to provision
        example: ptvirtualize up now --modify --pro # modify and provision an existing box during the up phase

HELPDATA;
    return $help ;
  }

}
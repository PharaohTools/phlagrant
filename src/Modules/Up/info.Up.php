<?php

Namespace Info;

class UpInfo extends CleopatraBase {

  public $hidden = false;

  public $name = "Up - Create and Start a Virtualizer Box";

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
  This command allows you to create, start and provision virtualizer boxes.

  Up, up

        - now
        Bring up a box now
        example: virtualizer up now
        example: virtualizer up now --modify # modify the hardware settings to match the Virtualizerfile during the up phase.
            Without it, the machine will be brought up with its previous settings. On creating new machines this will
            happen automatically regardless of the parameter.
        example: virtualizer up now --provision # provision an existing machine with the configuration scripts specified
            in the Virtualizerfile. Without it, the machine will be brought up with its previous config. On creating
            new machines this will happen automatically regardless of the parameter.
        example: virtualizer up now --modify --provision # modify and provision an existing box during the up phase

HELPDATA;
    return $help ;
  }

}
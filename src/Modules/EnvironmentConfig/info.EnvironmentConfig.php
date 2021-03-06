<?php

Namespace Info;

class EnvironmentConfigInfo extends Base {

    public $hidden = true;

    public $name = "Environment Configuration - Configure Environments for a project";

    public function __construct() {
      parent::__construct();
    }

    public function routesAvailable() {
      return array( "EnvironmentConfig" =>  array_merge(parent::routesAvailable(), array("configure", "config") ) );
    }

    public function routeAliases() {
      return array("environmentconfig"=>"EnvironmentConfig", "environment-config"=>"EnvironmentConfig",
        "envconfig"=>"EnvironmentConfig", "env-config"=>"EnvironmentConfig");
    }

    public function helpDefinition() {
      $help = <<<"HELPDATA"
  This command is part of a default Module and provides you with a method by which you can
  configure environments for your project from the command line. Currently compliant with
  both PTDeploy and PTConfigure.


  EnvironmentConfig, environmentconfig, environment-config, envconfig, env-config

        - configure
        Configure the environments for your project to use
        example: ptdeploy envconfig configure
        example: ptconfigure envconfig configure


HELPDATA;
      return $help ;
    }

}
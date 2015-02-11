<?php

Namespace Info;

class AutoSSHInfo extends CleopatraBase {

  public $hidden = false;

  public $name = "AutoSSH - Use your Papyrus details to automatically SSH or SFTP into your Virtualizer box";

  public function __construct() {
    parent::__construct();
  }

  public function routesAvailable() {
    return array( "AutoSSH" =>   array("cli", "sftp-put", "sftp-get", "help") );
  }

  public function routeAliases() {
    return array("auto-ssh"=>"AutoSSH", "autossh"=>"AutoSSH", "ssh"=>"AutoSSH", "SSH"=>"AutoSSH");
  }

  public function helpDefinition() {
    $help = <<<"HELPDATA"
  This command allows you to autoSSH a virtualizer box

  AutoSSH, auto-ssh, autossh, ssh, SSH

        - cli
        Open an SSH Cli to your Virtualizer Box
        example: virtualizer auto-ssh cli --yes --guess

        - sftp-put
        SFTP Put a file on to your Virtualizer Box
        example: virtualizer auto-ssh sftp-put --yes --guess --source=/path/to/source --target=/path/to/target

        - sftp-get
        SFTP Get a file from your Virtualizer Box
        example: virtualizer auto-ssh sftp-get --yes --guess --source=/path/to/source --target=/path/to/target

HELPDATA;
    return $help ;
  }

}
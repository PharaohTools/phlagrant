<?php

Namespace Model ;

class Virtufile extends VirtufileBase {

    public $config ;

    public function __construct() {
        $this->setConfig();
    }

    private function setConfig() {
        $this->setDefaultConfig();
        # $this->config["vm"]["gui_mode"] = "gui" ;
        $this->config["vm"]["box"] = "pharaohubuntu14041amd64" ;
        # Shared folders @todo this is not ready for any OS as it requires
        $this->config["vm"]["shared_folders"][] =
            array(
                "name" => "host_www",
                "host_path" => getcwd().DS,
                "guest_path" => "/var/www/hostshare/",
            ) ;
        # Provisioning
        $this->config["vm"]["provision"][] =
            array(
                "provisioner" => "Shell",
                "tool" => "shell",
                "target" => "guest",
                "default" => "MountShares"
            ) ;
        $this->config["vm"]["provision"][] =
            array(
                "provisioner" => "Shell",
                "tool" => "shell",
                "target" => "guest",
                "default" => "PTConfigureInit"
            ) ;
        $this->config["vm"]["provision"][] =
            array(
                "provisioner" => "PharaohTools",
                "tool" => "ptconfigure",
                "target" => "host",
                "script" => getcwd().DS."<%tpl.php%>ptconfigurefile-host</%tpl.php%>"
            ) ;
        $this->config["vm"]["provision"][] =
            array(
                "provisioner" => "PharaohTools",
                "tool" => "ptconfigure",
                "target" => "guest",
                "script" => getcwd().DS."<%tpl.php%>ptconfigurefile-guest</%tpl.php%>"
                // build/config/ptconfigure/ptconfigurefy/autopilots/generic/Virtualize/ptconfigurefy-cm-ptvirtualize.php
            ) ;
        $this->config["vm"]["provision"][] =
            array(
                "provisioner" => "PharaohTools",
                "tool" => "ptdeploy",
                "target" => "host",
                "script" => getcwd().DS."<%tpl.php%>ptdeployfile-host</%tpl.php%>"
            ) ;
        $this->config["vm"]["provision"][] =
            array(
                "provisioner" => "PharaohTools",
                "tool" => "ptdeploy",
                "target" => "guest",
                "script" => getcwd().DS."<%tpl.php%>ptdeployfile-guest</%tpl.php%>"
            ) ;
        $this->config["vm"]["provision_destroy_post"][] =
            array(
                "provisioner" => "PharaohTools",
                "tool" => "ptdeploy",
                "target" => "host",
                "script" => getcwd().DS."<%tpl.php%>ptdeployfile-host-destroy</%tpl.php%>"
            ) ;
        $this->config["vm"]["post_up_message"] = "Your Virtualize Box has been brought up. This box was configured to be " .
            "provisioned by both PTConfigure and PTDeploy. Your application should now be accessible.";
    }

}

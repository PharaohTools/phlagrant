<?php

Namespace Model;

class InvokeNativeWrapperAllLinux extends Base {

    // Compatibility
    public $os = array("Linux") ;
    public $linuxType = array("any") ;
    public $distros = array("any") ;
    public $versions = array("any") ;
    public $architectures = array("any") ;

    // Model Group
    public $modelGroup = array("NativeWrapper") ;

    public $target ;
    public $privkey ;
    public $pubkey ;
    public $port ;
    public $timeout ;

    protected $connection ;

    public function login($username, $password = '') {
        if (file_exists($password)) {
            $this->privkey = $password  ;
            $connection = ssh2_connect($this->target, $this->port, array('hostkey'=>'ssh-rsa'));
            if ($this->pubkey == null) {
                // @todo we should highlight somewhere that this public key needs to be set because surely it NOT needed?
                $this->pubkey = $this->privkey.".pub" ; }
            if (ssh2_auth_pubkey_file($connection, $username, $this->pubkey, $this->privkey, 'secret')) {
                $this->connection = $connection ;
                return true ; } }
        else {
            $connection = ssh2_connect($this->target, $this->port);
            if (ssh2_auth_password($connection, $username, $password)) {
                $this->connection = $connection ;
                return true ; } }
        return false ;
    }

    public function exec($command) {
        $stream = ssh2_exec($this->connection, $command);
        stream_set_blocking( $stream, true ); // THIS IS REQUIRED TO PREVENT https://bugs.php.net/bug.php?id=58893
        $all = "" ;
//        sleep(1);
//        $abit = stream_get_contents ($stream, -1, strlen($all)) ;
//        $all .= $abit ;
        // var_dump($stream); @todo this should be able to update output properly
        while ( !feof($stream) ) {
            $c = fgetc($stream);
            if($c === false) break;

            sleep(1);
            $abit = stream_get_contents ($stream, -1, strlen($all)) ;
            $all .= $abit ;
            echo $abit."\n" ;
//            echo "sle:".strlen($all)."\n" ;
//            echo "x"."\n" ;
        }
        fclose($stream);
        return $all ;
    }

    public function cmd ( $cmd, $returnOutput = false ) {
        // $this->logAction ( "Executing command $cmd" );
        $stream = ssh2_exec ( $this->connection, $cmd );

        if ( FALSE === $stream ) {
            $this->logAction ( "Unable to execute command $cmd" );
        }
        $this->logAction ( "$cmd was executed" );

        stream_set_blocking ( $stream, true );
        stream_set_timeout ( $stream, 100 );
        $this->lastLog = stream_get_contents ( $stream );

        $this->logAction ( "$cmd output: {$this->lastLog}" );
        fclose ( $stream );
        $this->log .= $this->lastLog . "\n";
        return ( $returnOutput ) ? $this->lastLog : $this;
    }

    protected function doSSHCommand( $sshObject, $command, $first=null ) {
        $returnVar = ($first==null) ? "" : $sshObject->read("PHARAOHPROMPT") ;
        $sshObject->write("$command\n") ;
        $returnVar .= $sshObject->read("PHARAOHPROMPT") ;
        return str_replace("PHARAOHPROMPT", "", $returnVar) ;
    }

}
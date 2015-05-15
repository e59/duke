<?php

namespace Duke\Controller;

class Login extends \Cdc\Controller {

    use \Cdc\Login;
    
    public function __construct() {

        \C::$layoutTemplate = array('layout/login.phtml', 'Duke');
        $this->module = 'Duke';
        $this->loginDestination = 'duke';
        $this->logoutDestination = 'duke';

        return parent::__construct();
    }

}

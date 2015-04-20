<?php

namespace Duke\Controller;

class Login extends \Cdc\Controller\Login {

    public $module = 'Duke';

    public $loginDestination = 'duke';

    public $logoutDestination = 'duke';

    public function __construct() {

        \C::$layoutTemplate = array('layout/login.phtml', 'Duke');

        return parent::__construct();
    }

}

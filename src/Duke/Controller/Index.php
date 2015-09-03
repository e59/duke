<?php

namespace Duke\Controller;

use \C as C;

class Index extends \Duke\Controller {

    public function indexAction() {
        $this->index = 'duke';
        $this->title = 'Início';

        ob_start();
        include $this->getTemplate('index.phtml');
        return ob_get_clean();
    }

}

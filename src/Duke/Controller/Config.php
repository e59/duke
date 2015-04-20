<?php

namespace Duke\Controller;

use \C as C;
use \Cdc\Definition\Helper as H;
use \Nette\Utils\Arrays as A;

class Config extends \Duke\Controller {

    protected $config;

    public function init() {
        parent::init();
        $this->config = \Duke\Config::getInstance();
    }

    public function indexAction() {
        $this->index = 'duke/config';

        ob_start();
        include $this->getTemplate('config/index.phtml');
        return ob_get_clean();
    }

    public function updateAction() {
        $this->index = 'duke/config/update';

        $chave = A::get(C::$matchedRoute->getParameters(), 'chave', null);

        if (!array_key_exists($chave, $this->config->items)) {
            $this->redirect('duke/config', array('Chave não encontrada', LOG_ERR));
        }

        $this->title = 'Alterar valor de configuração';
        $this->description = $chave;

        $def = array(
            'valor' => H\TextAreaColumn::def(),
        );

        $options = array();

        $original['valor'] = $this->config->items[$chave];

        $input = array_merge($original, C::$request->getPost());

        if (C::$request->isMethod('POST')) {
            $this->config->$chave = $input['valor'];
            $this->redirect('duke/config', array('Chave atualizada', LOG_SUCCESS));
        }


        $form = new \Cdc\Form($def, $options, $input);

        return $form->render();
    }

}

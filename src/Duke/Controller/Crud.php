<?php

namespace Duke\Controller;

use \C as C;
use \Nette\Utils\Arrays as A;
use \Nette\Utils\Strings as S;
use \Cdc\Definition as D;
use \Cdc\Sql\Select as Select;

class Crud extends \Duke\Controller {

    public $crudObjects = array();

    public function init() {
        $options = A::get(C::$matchedRoute->args, 'options');
        $js = A::get($options, 'js', array());
        if ($js) {
            ob_start();
            include $this->getTemplate(reset($js), end($js));
            $this->addJs(ob_get_clean());
        }

        return parent::init();
    }

    public function readAction() {

        $crudObj = A::get($this->crudObjects, 'search', '\Duke\Crud\Search');

        $definition = $this->getDefinition(A::get(C::$matchedRoute->args, 'definition'));

        $s = new $crudObj;
        $s->options = A::get(C::$matchedRoute->args, 'options');
        $s->get = \C::$request->getQuery();

        $s->operation = 'read';
        $s->definition = $definition;

        $searchResult = $s->exec();


        $crudObj = A::get($this->crudObjects, 'read', '\Duke\Crud\Read');
        $c = new $crudObj;
        $c->options = A::get(C::$matchedRoute->args, 'options');
        $c->get = \C::$request->getQuery();
        $c->operation = 'read';
        $c->definition = $s->definition;
        $c->cols = \Cdc\Definition\MetadataFactory::columns($c->definition);

        if ($searchResult['data']) {
            $c->table = \Cdc\Definition\MetadataFactory::table($c->definition);
            $c->query = Select::instance()->from(array($c->table))->cols($c->cols);
            $c->query->where($searchResult['data']);
            $search = true;
        } else {
            $search = false;
        }

        if ($searchResult['parentData']) {
            $this->lastBreadcrumb = $searchResult['parentData']['breadcrumb'];

            $this->index = $searchResult['parentData']['routeUp'];
        }

        $result = $c->exec();

        $hasItems = !empty($result['data']);

        if ($hasItems) {
            $message = false;
        } else {
            if ($search) {
                flash('Nenhum resultado encontrado.');
            } else {
                flash('Nenhum registro cadastrado.');
            }
        }

        $textBlock = $definition->textBlock($result['data'], $c->options);

        return $textBlock . $searchResult['text'] . csprintf($result['text'], $hasItems);
    }

    public function createAction() {

        $crudObj = A::get($this->crudObjects, 'create', '\Duke\Crud\Create');
        $c = new $crudObj;

        $c->options = A::get(C::$matchedRoute->args, 'options');
        $c->operation = 'create';
        $c->files = C::$request->getFiles();
        $c->get = C::$request->getQuery();
        $c->post = C::$request->getPost();
        $c->definition = $definition = $this->getDefinition(A::get(C::$matchedRoute->args, 'definition'));

        if (C::$request->isAjax()) {
            $c->fileManager = true;
        }

        $up = A::get(C::$matchedRoute->args, 'up', array());

        if ($up) {
            $paramName = A::get($up, 'parameter');
            $paramValue = A::get($c->params, $paramName);
            $redirect = array($c->options['routes']['read'], array($paramName => $paramValue));
        } else {
            $redirect = $c->options['routes']['read'];
        }

        $result = $c->exec();

        if ($result['parentData']) {
            $this->lastBreadcrumb = $result['parentData']['breadcrumb'];
            $this->index = $result['parentData']['routeUp'];
        } else {
            $this->index = \coalesce(A::get(C::$matchedRoute->args, 'index', null), $c->options['routes']['read']);
        }
        $this->lastBreadcrumb[] = C::$menuFactory->createItem('Novo');

        if (is_object($result['data'])) {
            flash($result['data']->getMessage(), LOG_ERR);
        } elseif (is_array($result['data'])) {
            \Cdc\ConstraintMessagePrinter::event($result['data']);
        } elseif ($result['data']) {
            $this->redirect($redirect, array('O item foi criado.', LOG_SUCCESS));
        }

        return $result['text'];
    }

    public function updateAction($id) {
        $crudObj = A::get($this->crudObjects, 'update', '\Duke\Crud\Update');
        $c = new $crudObj;

        $c->options = A::get(C::$matchedRoute->args, 'options');
        $c->operation = 'update';
        $c->id = $id;
        $c->files = C::$request->getFiles();
        $c->get = C::$request->getQuery();
        $c->post = C::$request->getPost();
        $c->definition = $definition = $this->getDefinition(A::get(C::$matchedRoute->args, 'definition'));

        if (C::$request->isAjax()) {
            $c->fileManager = true;
        }

        $item = $c->item($definition);

        $up = A::get(C::$matchedRoute->args, 'up', array());

        if ($up) {
            $paramName = A::get($up, 'parameter');
            $c->params[$paramName] = $item[$paramName];
            $redirect = array($c->options['routes']['read'], array($paramName => $item[$paramName]));
        } else {
            $redirect = $c->options['routes']['read'];
        }

        $c->title = $definition->query(D::TYPE_COLUMN)->byTag('title')->fetch(D::MODE_SINGLE);

        $this->index = \coalesce(A::get(C::$matchedRoute->args, 'index', null), $c->options['routes']['read']);

        $result = $c->exec();

        if ($result['parentData']) {
            $this->lastBreadcrumb = $result['parentData']['breadcrumb'];
            $this->index = $result['parentData']['routeUp'];
        } else {
            $this->index = \coalesce(A::get(C::$matchedRoute->args, 'index', null), $c->options['routes']['read']);
        }
        $this->lastBreadcrumb[] = C::$menuFactory->createItem('Editar ' . $item[$c->title]);

        if (is_object($result['data'])) {
            flash($result['data']->getMessage(), LOG_ERR);
        } elseif (is_array($result['data'])) {
            \Cdc\ConstraintMessagePrinter::event($result['data']);
        } elseif ($result['data']) {
            $this->redirect($redirect, array('Edição concluída.', LOG_SUCCESS));
        }

        return $result['text'];
    }

    public function deleteAction($id) {
        $crudObj = A::get($this->crudObjects, 'delete', '\Duke\Crud\Delete');
        $c = new $crudObj;


        $c->options = A::get(C::$matchedRoute->args, 'options');
        $c->operation = 'delete';
        $c->id = $id;
        $c->get = C::$request->getQuery();
        $c->post = C::$request->getPost();
        $c->definition = $definition = $this->getDefinition(A::get(C::$matchedRoute->args, 'definition'));
        $c->title = $definition->query(D::TYPE_COLUMN)->byTag('title')->fetch(D::MODE_SINGLE);

        $item = $c->item($definition);

        $up = A::get(C::$matchedRoute->args, 'up', array());

        if ($up) {
            $paramName = A::get($up, 'parameter');
            $c->params[$paramName] = $item[$paramName];
            $redirect = array($c->options['routes']['read'], array($paramName => $item[$paramName]));
        } else {
            $redirect = $c->options['routes']['read'];
        }

        $result = $c->exec();

        if ($result['parentData']) {
            $this->lastBreadcrumb = $result['parentData']['breadcrumb'];
            $this->index = $result['parentData']['routeUp'];
        } else {
            $this->index = \coalesce(A::get(C::$matchedRoute->args, 'index', null), $c->options['routes']['read']);
        }
        $this->lastBreadcrumb[] = C::$menuFactory->createItem('Excluir ' . $item[$c->title]);



        if (is_object($result['data'])) {
            flash($result['data']->getMessage(), LOG_ERR);
        } elseif (is_array($result['data'])) {
            \Cdc\ConstraintMessagePrinter::event($result['data']);
        } elseif ($result['data']) {
            $this->redirect($redirect, array('O item foi excluído.', LOG_SUCCESS));
        }

        return $result['text'];
    }

}

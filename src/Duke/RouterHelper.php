<?php

namespace Duke;

use Nette\Utils\Arrays as A;

class RouterHelper {

    public static function crud(\Cdc\Router $router, $base, $definition, $resource, $class = null, $target = array(), $args = array()) {

        if (!$class) {
            $class = '\Duke\Controller\Crud';
        }

        $up = A::get($args, 'up', array());


        if ($up) {
            $related = '/:' . ltrim(A::get($up, 'parameter'), ':/');
        } else {
            $related = '';
        }



        $crudInfo = array(
            'routes' => array(
                'base' => $base,
                'create' => $base . '/create',
                'read' => $base . '/read',
                'update' => $base . '/update',
                'delete' => $base . '/delete',
            ),
        );

        $router->map($base . '/read' . $related, \Nette\Utils\Arrays::mergeTree(array('class' => $class, 'resource' => $resource, 'action' => 'read'), $target), \Nette\Utils\Arrays::mergeTree(array('definition' => $definition, 'options' => $crudInfo, 'name' => $base . '/read'), $args));
        $router->map($base . '/create' . $related, \Nette\Utils\Arrays::mergeTree(array('class' => $class, 'resource' => $resource, 'action' => 'create'), $target), \Nette\Utils\Arrays::mergeTree(array('definition' => $definition, 'options' => $crudInfo, 'name' => $base . '/create'), $args));
        $router->map($base . '/update/:id', \Nette\Utils\Arrays::mergeTree(array('class' => $class, 'resource' => $resource, 'action' => 'update'), $target), \Nette\Utils\Arrays::mergeTree(array('definition' => $definition, 'options' => $crudInfo, 'name' => $base . '/update'), $args));
        $router->map($base . '/delete/:id', \Nette\Utils\Arrays::mergeTree(array('class' => $class, 'resource' => $resource, 'action' => 'delete'), $target), \Nette\Utils\Arrays::mergeTree(array('definition' => $definition, 'options' => $crudInfo, 'name' => $base . '/delete'), $args));
    }

}

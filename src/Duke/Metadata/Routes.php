<?php

namespace Duke\Metadata;

use Duke\RouterHelper as RH;
use Duke\Metadata\Resources as DR;

class Routes {

    public static function setup($router) {
        $router->map('duke', array('class' => '\Duke\Controller\Index', 'resource' => 'authenticated'));
        $router->map('duke/login', array('class' => \C::$default_login_controller, 'resource' => 'none'));
        $router->map('duke/logout', array('class' => \C::$default_login_controller, 'resource' => 'authenticated', 'action' => 'logout'));
        RH::crud($router, 'duke/usuarios', '\Duke\Definition\Usuario', Resources::ADMINISTRAR_SISTEMA);
        RH::crud($router, 'duke/grupos', '\Duke\Definition\Grupo', Resources::ADMINISTRAR_SISTEMA);

        // Config
        $router->map('duke/config', array('class' => '\Duke\Controller\Config', 'resource' => Resources::ADMINISTRAR_SISTEMA));
        $router->map('duke/config/update/:chave', array('class' => '\Duke\Controller\Config', 'resource' => Resources::ADMINISTRAR_SISTEMA, 'action' => 'update'), array('name' => 'admin/config/update'));

        // Páginas
        $crudInfo = array(
            'routes' => array(
                'read' => 'duke/pagina/read',
                'update' => 'duke/pagina/update',
            ),
        );
        $router->map('duke/pagina/read', array('class' => '\Duke\Controller\Crud', 'resource' => DR::GERENCIAR_CONTEUDO, 'action' => 'read'), array('definition' => '\Duke\Definition\Pagina', 'options' => $crudInfo));
        $router->map('duke/pagina/update/:id', array('class' => '\Duke\Controller\Crud', 'resource' => DR::GERENCIAR_CONTEUDO, 'action' => 'update'), array('definition' => '\Duke\Definition\Pagina', 'options' => $crudInfo, 'name' => 'duke/pagina/update', 'index' => 'duke/pagina/read'));
    }

}

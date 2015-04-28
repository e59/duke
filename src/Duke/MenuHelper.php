<?php

namespace Duke;

use Nette\Utils\Arrays as A;

class MenuHelper {

    public static function simpleCrud($item, $controller, $base, $label) {

        $subitem = $item->addChild($base, array(
            'uri' => 'javascript:void(0)',
            'label' => $label,
            'extras' => array(
                'index' => $base,
            ),
        ));

        $subitem->addChild($base . '/read', array(
            'uri' => $controller->link($base . '/read'),
            'label' => 'Listar',
            'extras' => array(
                'index' => $base . '/read',
            ),
        ));

        $subitem->addChild($base . '/create', array(
            'uri' => $controller->link($base . '/create'),
            'label' => 'Novo',
            'extras' => array(
                'index' => $base . '/create',
            ),
        ));
    }

}

<?php

namespace Duke\Metadata;

use \C as C;

class Resources {

    const ADMINISTRAR_SISTEMA = 'admin';
    const GERENCIAR_CONTEUDO = 'conteudo';

    public static function setup() {
        C::$resources[self::ADMINISTRAR_SISTEMA] = 'Funções administrativas';
        C::$resources[self::GERENCIAR_CONTEUDO] = 'Gerenciar conteúdo';
    }

}

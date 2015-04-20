<?php

namespace Duke\Definition;

use \Cdc\Definition\Helper as H;
use \C as C;

class Grupo extends \Duke\Definition {

    protected function buildDefinition() {
        $def = array(
            'grupo' => H\Relation::def(),
            'id' => H\PrimaryColumn::def(),
            'nome' => H\SetRequired::modify(H\TextColumn::def(array('tags' => array('title')))),
            'permissao' => H\Attachment\MultiCheckboxColumn::def(array(
                'values' => C::$resources,
                'query' => array(
                    'cols' => array('grupo_id', 'permissao_id'),
                    'from' => array('permissao'),
                ),
                'junction' => 'permissao', // tabela de junção
                'local' => 'grupo_id', // nome de parent no local
                'id' => 'permissao_id', // nome de id na junction
                'parent' => 'id', // nome de local no parent
            )),
            'criado' => H\DateTimeColumn::def(array('tags' => array('metadata'))),
        );

        return $def;
    }

}

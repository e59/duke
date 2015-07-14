<?php

namespace Duke\Definition;

use \Cdc\Definition\Helper as H;

class Pagina extends \Duke\Definition {

    protected function buildDefinition() {
        $def = array(
            'pagina' => H\Relation::def(),
            'id' => H\PrimaryColumn::def(),
            'titulo' => H\TextColumn::def(array('tags' => array('title'))),
            'texto' => H\RichColumn::def(array(self::OPERATION => array('read' => null))),
            'criado' => H\DateTimeColumn::def(array('tags' => array('metadata'))),
            'default-file' => H\Attachment\MultiFileColumn::def(array(
                'query' => array(
                    'cols' => array('arquivo.*', 'pagina_id', 'arquivo_id'),
                    'from' => array('arquivo'),
                    'join' => array(
                        'pagina_arquivo' => array('inner' => array('arquivo.id = pagina_arquivo.arquivo_id')),
                    ),
                ),
                'local' => 'pagina_id',
                'parent' => 'id',
                'junction' => 'pagina_arquivo',
                'id' => 'arquivo_id',
                'extensions' => array('png', 'jpg', 'jpeg', 'gif', 'pdf'),
                'files' => \C::$request->getFiles(),
            )),
        );

        return $def;
    }

}

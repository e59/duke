<?php

namespace Duke\Definition;

use \Cdc\Definition\Helper as H;

class Usuario extends \Duke\Definition {

    protected function buildDefinition() {

        $def = array(
            'usuario' => H\Relation::def(),
            'id' => H\PrimaryColumn::def(),
            'nome' => H\TextColumn::def(array('tags' => array('title'))),
            'grupo' => H\Attachment\MultiCheckboxColumn::def(array(
                'values' => array(array($this, 'kv'), array('grupo', 'id', 'nome')),
                'query' => array(
                    'cols' => array('usuario_id', 'id', 'nome', 'grupo_id'),
                    'from' => array('grupo'),
                    'join' => array(
                        'usuario_grupo' => array('inner' => array('grupo.id = usuario_grupo.grupo_id')),
                    ),
                ),
                'junction' => 'usuario_grupo', // tabela de junção
                'local' => 'usuario_id', // nome de parent no local
                'id' => 'grupo_id', // nome de id na junction
                'parent' => 'id', // nome de local no parent
            )),
            'admin-perfil' => H\Attachment\FileColumn::def(array(
                'query' => array(
                    'cols' => array('arquivo.usuario_id' => 'owner', 'arquivo.*', 'usuario_arquivo.usuario_id' => 'usuario_id', 'arquivo_id'),
                    'from' => array('arquivo'),
                    'join' => array(
                        'usuario_arquivo' => array('inner' => array('arquivo.id = usuario_arquivo.arquivo_id')),
                    ),
                ),
                'local' => 'usuario_arquivo.usuario_id',
                'parent' => 'id',
                'junction' => 'usuario_arquivo',
                'id' => 'arquivo_id',
                'extensions' => array('png', 'jpg', 'gif'),
                'files' => \C::$request->getFiles(),
            )),
            'email' => H\EmailColumn::def(),
            'senha' => H\PasswordColumn::def(),
            'criado' => H\DateTimeColumn::def(array('tags' => array('metadata'))),
            'ativo' => H\SetDefault::modify(H\BooleanColumn::def(), true),
        );

        return $def;
    }

    public function prepareInput($input) {
        $input['senha'] = \C::$hasher->HashPassword($input['senha']);

        return $input;
    }

}

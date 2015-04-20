<?php

namespace Duke\Controller\Crud;

use \C as C;
use \Nette\Utils\Arrays as A;
use \Nette\Utils\Strings as S;
use \Cdc\Definition as D;

class Related extends \Duke\Controller\Crud {

    public function readAction() {

        $routeUp = A::get($this->routeUp, 'route');

        $definition = A::get($this->routeUp, 'definition');
        $paramName = A::get($this->routeUp, 'parameter');


        // definition pai
        $d = $this->getDefinition($definition);

        // tabela do registro pai
        $parent_table = $d->query(D::TYPE_RELATION)->fetch(D::MODE_SINGLE);

        // título para o registro pai
        $parent_title = $d->query(D::TYPE_COLUMN)->byTag('title')->fetch(D::MODE_SINGLE);

        // nome da coluna relacionada nesta listagem
        $related_column = $this->definition->query(D::TYPE_COLUMN)->byTag('related-' . $parent_table)->fetch(D::MODE_SINGLE);

        // parametros da url
        $parameters = C::$matchedRoute->getParameters();

        // id do pai
        $parent_id = A::get($parameters, $paramName);
        $parent_primary = $d->query(D::TYPE_COLUMN)->byTag('primary')->fetch(D::MODE_SINGLE);

        // registro do pai
        $select = new \Cdc\Sql\Select(C::connection());
        $relatedRecord = $select->cols(array('*'))->from(array($parent_table))->where(array($parent_primary . ' =' => $parent_id))->stmt()->fetch();

        $this->index = $routeUp;




        $this->definition->setOperation('read');
        $cols = \Cdc\Definition\MetadataFactory::columns($this->definition);
        $table = \Cdc\Definition\MetadataFactory::table($this->definition);


        $this->lastBreadcrumb[] = C::$menuFactory->createItem($relatedRecord[$parent_title]);
        $this->lastBreadcrumb[] = C::$menuFactory->createItem(label($table));

        $sql = new \Cdc\Sql\Select(C::connection());
        $data = $sql->from(array($table))->cols($cols)->where(array($related_column . ' =' => $parent_id))->stmt();

        return $this->read($table, $cols, $data);
    }

    public function createAction() {



        $routeUp = A::get($this->routeUp, 'route');

        $definition = A::get($this->routeUp, 'definition');
        $paramName = A::get($this->routeUp, 'parameter');
        $this->index = $routeUp;


        // definition pai
        $d = $this->getDefinition($definition);

        // tabela do registro pai
        $parent_table = $d->query(D::TYPE_RELATION)->fetch(D::MODE_SINGLE);

        // título para o registro pai
        $parent_title = $d->query(D::TYPE_COLUMN)->byTag('title')->fetch(D::MODE_SINGLE);

        // nome da coluna relacionada nesta listagem
        $related_column = $this->definition->query(D::TYPE_COLUMN)->byTag('related-' . $parent_table)->fetch(D::MODE_SINGLE);

        // parametros da url
        $parameters = C::$matchedRoute->getParameters();

        // id do pai
        $parent_id = A::get($parameters, $paramName);
        $parent_primary = $d->query(D::TYPE_COLUMN)->byTag('primary')->fetch(D::MODE_SINGLE);

        // registro do pai
        $select = new \Cdc\Sql\Select(C::connection());
        $relatedRecord = $select->cols(array('*'))->from(array($parent_table))->where(array($parent_primary . ' =' => $parent_id))->stmt()->fetch();

        $this->index = $routeUp;

        $table = $this->definition->query(D::TYPE_RELATION)->fetch(D::MODE_SINGLE);


        $this->lastBreadcrumb[] = C::$menuFactory->createItem($relatedRecord[$parent_title]);
        $this->lastBreadcrumb[] = C::$menuFactory->createItem(label($table));



        $this->routeRead = array($this->routeRead, array($parent_table => $parent_id));

        return $this->save(false, array($related_column => $parent_id));
    }

    public function updateAction() {

        $item = $this->item();

        $routeUp = A::get($this->routeUp, 'route');

        $definition = A::get($this->routeUp, 'definition');
        $paramName = A::get($this->routeUp, 'parameter');


        // definition pai
        $d = $this->getDefinition($definition);

        // tabela do registro pai
        $parent_table = $d->query(D::TYPE_RELATION)->fetch(D::MODE_SINGLE);

        // título para o registro pai
        $parent_title = $d->query(D::TYPE_COLUMN)->byTag('title')->fetch(D::MODE_SINGLE);

        // nome da coluna relacionada nesta listagem
        $related_column = $this->definition->query(D::TYPE_COLUMN)->byTag('related-' . $parent_table)->fetch(D::MODE_SINGLE);

        // id do pai
        $parent_id = $item[$related_column];
        $parent_primary = $d->query(D::TYPE_COLUMN)->byTag('primary')->fetch(D::MODE_SINGLE);

        // registro do pai
        $select = new \Cdc\Sql\Select(C::connection());
        $relatedRecord = $select->cols(array('*'))->from(array($parent_table))->where(array($parent_primary . ' =' => $parent_id))->stmt()->fetch();

        $this->index = $routeUp;

        $table = $this->definition->query(D::TYPE_RELATION)->fetch(D::MODE_SINGLE);
        $this->lastBreadcrumb[] = C::$menuFactory->createItem($relatedRecord[$parent_title]);
        $this->lastBreadcrumb[] = C::$menuFactory->createItem(label($table));


        $this->routeRead = array($this->routeRead, array($parent_table => $parent_id));

        return $this->save(true, array($related_column => $parent_id));
    }

    public function deleteAction() {

        $item = $this->item();

        $routeUp = A::get($this->routeUp, 'route');

        $definition = A::get($this->routeUp, 'definition');
        $paramName = A::get($this->routeUp, 'parameter');


        // definition pai
        $d = $this->getDefinition($definition);

        // tabela do registro pai
        $parent_table = $d->query(D::TYPE_RELATION)->fetch(D::MODE_SINGLE);

        // título para o registro pai
        $parent_title = $d->query(D::TYPE_COLUMN)->byTag('title')->fetch(D::MODE_SINGLE);

        // nome da coluna relacionada nesta listagem
        $related_column = $this->definition->query(D::TYPE_COLUMN)->byTag('related-' . $parent_table)->fetch(D::MODE_SINGLE);

        // id do pai
        $parent_id = $item[$related_column];
        $parent_primary = $d->query(D::TYPE_COLUMN)->byTag('primary')->fetch(D::MODE_SINGLE);

        // registro do pai
        $select = new \Cdc\Sql\Select(C::connection());
        $relatedRecord = $select->cols(array('*'))->from(array($parent_table))->where(array($parent_primary . ' =' => $parent_id))->stmt()->fetch();

        $this->index = $routeUp;

        $table = $this->definition->query(D::TYPE_RELATION)->fetch(D::MODE_SINGLE);
        $this->lastBreadcrumb[] = C::$menuFactory->createItem($relatedRecord[$parent_title]);
        $this->lastBreadcrumb[] = C::$menuFactory->createItem(label($table));


        $this->routeRead = array($this->routeRead, array($parent_table => $parent_id));


        $definition = $this->definition;
        $definition->setOperation('delete');


        $primary = $definition->query(D::TYPE_COLUMN)->byTag('primary')->fetch(D::MODE_SINGLE);
        $title = $definition->query(D::TYPE_COLUMN)->byTag('title')->fetch(D::MODE_SINGLE);
        $table = $definition->query(D::TYPE_RELATION)->fetch(D:: MODE_SINGLE);

        $id = A::get(C :: $matchedRoute->getParameters(), $primary, null);

        $this->lastBreadcrumb[] = C::$menuFactory->createItem('Excluir ' . $item[$title]);

        if (C::$request->isPost()) {
            $item->delete();
            $this->redirect($this->routeRead, array('A operação foi concluída com sucesso.', LOG_SUCCESS));
        }


        $back = $this->routeRead;

        ob_start();
        include $this->getTemplate('crud/delete.phtml');
        return ob_get_clean();
    }

}

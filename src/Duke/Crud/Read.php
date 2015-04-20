<?php

namespace Duke\Crud;

use \Cdc\Sql\Select;
use \Duke\Definition as D;
use Nette\Utils\Arrays as A;

class Read extends Base {

    public function exec() {

        if ($this->definition) {
            if (is_string($this->definition)) {
                $definition = $this->getDefinition($this->definition);
            } else {
                $definition = $this->definition;
            }
        } else {
            $definition = new \Duke\Definition;
        }

        $operation = $this->operation;

        if ($operation) {
            $definition->setOperation($operation);
        }

        $cols = $this->cols or $cols = \Cdc\Definition\MetadataFactory::columns($definition);

        $table = $this->table or $table = \Cdc\Definition\MetadataFactory::table($definition);

        $query = $this->query or $query = Select::instance()->from(array($table))->cols($cols);

        $page = A::get($this->get, 'p', 1);

        $p = $this->createPager($page, $this->pageSize, $query);
        $get = $this->get;
        $params = $this->params;

        $list = $this->list or $list = $definition->format($definition->hydrated($query));

        $hide = $this->hide or $hide = array_flip($definition->query(D::TYPE_COLUMN)->byKey('hide')->fetch(D::MODE_KEY_ONLY));

        $options = $this->options;

        $template = $this->template or $template = $this->getTemplate('crud/read.phtml');

        ob_start();
        include $template;
        return array('data' => $list, 'text' => ob_get_clean());
    }

}

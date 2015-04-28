<?php

namespace Duke\Crud;

use \Duke\Definition as D;
use Nette\Utils\Arrays as A;

class Delete extends Base {

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


        $up = $this->up or $up = A::get(\C::$matchedRoute->args, 'up', array());

        $parentData = $this->parentData or $parentData = $this->parentData($definition, $up);


        $operation = $this->operation;

        if ($operation) {
            $definition->setOperation($operation);
        }

        $cols = $this->cols or $cols = \Cdc\Definition\MetadataFactory::columns($definition);

        $table = $this->table or $table = \Cdc\Definition\MetadataFactory::table($definition);

        $hide = $this->hide or $hide = array_flip($definition->query(D::TYPE_COLUMN)->byKey('hide')->fetch(D::MODE_KEY_ONLY));

        $options = $this->options;

        $template = $this->template or $template = $this->getTemplate('crud/delete.phtml');

        $title = $this->title or $title = $definition->query(D::TYPE_COLUMN)->byTag('title')->fetch(D::MODE_SINGLE);

        $primary = $this->primary or $primary = $definition->query(D::TYPE_COLUMN)->byTag('primary')->fetch(D::MODE_SINGLE);

        $id = $this->id;

        $item = $this->item($definition, $id);

        $result = array('data' => null, 'text' => null);

        if ($this->method == 'POST') {
            try {
                \Cdc\Sql\Delete::instance()->from(array($table))->where(array($primary . '=' => $item[$primary]))->stmt();
                $result['data'] = $id;
            } catch (\Exception $e) {
                $result['data'] = $e;
            }
        }


        ob_start();
        include $this->getTemplate($template);
        $result['text'] = ob_get_clean();
        $result['parentData'] = $parentData;

        return $result;
    }

}

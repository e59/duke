<?php

namespace Duke\Crud;

use \Cdc\Sql\Select;
use \Duke\Definition as D;
use Nette\Utils\Arrays as A;

class Search extends Base {

    public $resetUrl;

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

        $template = $this->template or $template = $this->getTemplate('crud/search.phtml');

        $options = $this->options;

        $def = \Cdc\Definition\MetadataFactory::search($definition);

        if (!$def) {
            return;
        }

        $resetUrl = $this->resetUrl or $resetUrl = $this->link(\C::$matchedRoute->getName());

        $up = $this->up or $up = A::get(\C::$matchedRoute->args, 'up', array());

        $parentData = $this->parentData or $parentData = $this->parentData($definition, $up);

        $input = A::get($this->get, 'search', array());

        if ($parentData) {
            $input = A::mergeTree($input, $parentData['parentFilter']);
            $result['parentData'] = $parentData;
        }


        $options['search_form'] = 'search';
        $options['resetUrl'] = $resetUrl;

        $form = new $this->formClass($def, $options, $input);

        $result['data'] = $definition->search($input, $def);

        $result['text'] = $form->render($this->getTemplate($template));


        return $result;
    }

}

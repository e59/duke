<?php

namespace Duke\Crud;

use \C as C;
use \Nette\Utils\Arrays as A;
use \Nette\Utils\Strings as S;
use \Cdc\Definition as D;
use \Cdc\Sql\Select;
use \Cdc\Sql\Insert;
use \Cdc\Sql\Update;

abstract class Base extends \Duke\Controller {

    public $id;

    public $formDefinition = array();

    public $rules = array();

    public $get = array();

    public $post = array();

    public $files = array();

    public $input = array();

    public $fixedData = array();

    public $params = array();

    public $operation;

    public $definition;

    public $title;

    public $table;

    public $cols;

    public $list;

    public $hide;

    public $options = array();

    public $query;

    public $template;

    public $primary = 'id';

    public $method;

    public $items = array();

    public $fileManager = false;

    public $pageSize = 50;

    public function __construct($method = null, $params = null) {
        if (null === $method) {
            $this->method = \C::$request->getMethod();
        }

        if (null === $params) {
            $this->params = \C::$matchedRoute->getParameters();
        }
    }

    abstract public function exec();

    public function deleteFile($definition, $template = null) {
        $preset = S::webalize(A::get($this->get, 'preset'));
        $tableDef = A::get($definition->query(D::TYPE_ATTACHMENT)->fetch(), $preset);

        $ids = (array) A::get($this->get, 'fileid');

        $definition->deleteFiles($ids, $preset);

        $this->fileList($definition, $template);
    }

    public function fileList($definition, $template = null) {

        $preset = S::webalize(A::get($this->get, 'preset'));
        $presets = $definition->getPresets();
        $preset_id = $presets[$preset]['id'];

        $item = $this->item($definition, null, true);

        $definition->setOperation('item');
        $tableDef = A::get($definition->query(D::TYPE_ATTACHMENT)->fetch(), $preset);

        if ($item) {
            $list = A::get($item, $preset, array());
        } else {
            $list = array();
        }

        $template or $template = $this->getTemplate('crud/filemanager.phtml');

        include $template;
    }

    public function saveUploads($item, $files, $definition) {
        $files = array_filter($files);

        if (empty($files)) {
            return array();
        }

        $newFiles = array();

        foreach ($files as $preset => $data) {

            if (!is_array($data)) {
                $data = array($data);
            } else {
                $data = array_filter($data);
            }
            foreach ($data as $file) {
                if (!$file->isOk()) {
                    // @TODO: flash user about broken file?
                    continue;
                }
                $nf = $definition->saveFile($item, $file, $preset);
                $newFiles[$preset][$nf['id']] = $nf;
            }
        }
        return $newFiles;
    }

    public function saveReferences($item, $input, $definition) {

        $refs = $definition->query(D::TYPE_ATTACHMENT)->fetch();
        $table = $definition->query(D::TYPE_RELATION)->fetch(D::MODE_SINGLE);
        $primary = $definition->query(D::TYPE_COLUMN)->byTag('primary')->fetch(D::MODE_SINGLE);

        $presets = $definition->getPresets();

        foreach ($refs as $key => $ref) {

            if (array_key_exists($key, $presets)) {
                $preset_id = $presets[$key]['id'];
            } else {
                $preset_id = false;
            }

            $values = (array) A::get($input, $key, array());
            $result = (array) A::get($item, $key, array());
            $local = A::get($ref, 'local');
            $parent = A::get($ref, 'parent');
            $junction = A::get($ref, 'junction');
            $id = A::get($ref, 'id');

            $sample = current($values);

            if (!is_array($sample)) { // attachment simples, onde são passados apenas os valores
                $values = array_fill_keys($values, true);
            }

            if ($preset_id) {
                $deleted = array();
            } else {
                $deleted = array_diff_key($result, $values);
            }
            $inserted = array_diff_key($values, $result);

            if ($deleted) {
                $ids = array();
                foreach ($deleted as $k => $v) {
                    $ids[] = $k;
                }

                $delete = new \Cdc\Sql\Delete(C::connection());
                $delete->from(array($junction))->where(array($id . ' in' => $ids, $local . '=' => $item[$parent]))->stmt();
            }

            if ($inserted) {
                $data = array();
                foreach ($inserted as $k => $v) {
                    $data[] = array(
                        $local => $item[$parent],
                        $id => $k,
                    );
                }
                $ins = new \Cdc\Sql\Insert(C::connection());
                $ins->from(array($junction))->cols($data)->stmt();
            }
        }
    }

    public function itemSelect($id, $definition) {
        $table = $definition->query(D::TYPE_RELATION)->fetch(D::MODE_SINGLE);
        $sql = new \Cdc\Sql\Select(C::connection());
        $primary = $definition->query(D::TYPE_COLUMN)->byTag('primary')->fetch(D::MODE_SINGLE);
        return $sql->cols(array('*'))->from(array($table))->where(array($primary . ' =' => $id));
    }

    public function item($definition, $id = null, $refresh = false) {
        if (!$id) {
            $id = $this->id;
        }
        if (!$id) {
            return array();
        }
        if (!isset($this->items[$id]) || $refresh) {
            $select = $this->itemSelect($id, $definition);
            $this->items[$id] = current($definition->hydrated($select));
        }
        return $this->items[$id];
    }

    public function save($update = false) {

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

        $hide = $this->hide or $hide = array_flip($definition->query(D::TYPE_COLUMN)->byKey('hide')->fetch(D::MODE_KEY_ONLY));

        $options = $this->options;

        $template = $this->template or $template = $this->getTemplate('crud/form.phtml');

        $title = $this->title or $title = $definition->query(D::TYPE_COLUMN)->byTag('title')->fetch(D::MODE_SINGLE);

        $primary = $this->primary or $primary = $definition->query(D::TYPE_COLUMN)->byTag('primary')->fetch(D::MODE_SINGLE);

        $id = $this->id;

        if ($this->input) {
            $input = $this->input;
        } else {
            if ($update) {
                $item = $this->item($definition, $id);
                $input = array_merge($definition->mergeInput($item, $this->post), $this->fixedData);
            } else {
                $item = array();
                $input = array_merge($this->post, $this->fixedData);
            }
        }

        if ($this->fileManager) {
            if (C::$request->getQuery('get_files')) {
                $this->fileList($definition);
            }
            if (C::$request->getQuery('delete_file')) {
                $this->deleteFile($definition);
            }
            die;
        }

        $result = array('data' => null, 'text' => null);
        if ($this->method == 'POST') {

            if ($this->rules) {
                $rules = $this->rules;
            } else {
                $ruleQueryResult = \Cdc\Definition\MetadataFactory::rules($definition);
                $rules = new \Cdc\Rule($ruleQueryResult);
            }
            if ($rules->invoke($input)) {

                $readyData = $definition->prepareInput($input);

                // filter keys that don't belong.
                $babyInput = array_intersect_key($readyData, array_flip($cols));
                try {
                    if ($update) {

                        Update::instance()->cols($babyInput)->from(array($table))->where(array($primary . ' =' => $item[$primary]))->stmt();
                        $id = $item[$primary];
                    } else {
                        Insert::instance()->from(array($table))->cols($babyInput)->stmt();
                        $id = $definition->lastInsertId();
                    }

                    $item = $this->item($definition, $id, true);

                    $newFiles = $this->saveUploads($item, $this->files, $definition);

                    $this->saveReferences($item, array_merge($input, $newFiles), $definition);

                    $result['data'] = $id;
                } catch (\Exception $e) {
                    $result['data'] = $e;
                }
            } else {
                $result['data'] = $rules->getMessages();
            }
        }

        $def = $this->formDefinition or $def = \Cdc\Definition\MetadataFactory::form($definition);

        $form = new $this->formClass($def, $options, $input);

        $result['text'] = $form->render($this->getTemplate($template));

        return $result;
    }

}

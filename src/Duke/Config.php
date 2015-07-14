<?php

namespace Duke;

class Config implements \Iterator, \ArrayAccess {

    public $items = array();

    public $descriptionMap;

    private $position;

    private static $instance;

    public static function getInstance() {
        if (!self::$instance) {
            $instance = self::$instance = new self;
            $result = \C::connection()->query('select * from configuracao order by chave asc')->fetchAll();
            foreach ($result as $item) {
                if (!$instance->position) {
                    $instance->position = $item['chave'];
                }
                $instance->items[$item['chave']] = $item['valor'];
                $instance->descriptionMap[$item['chave']] = $item['descricao'];
            }
        }
        return self::$instance;
    }

    private function __construct() {

    }

    public function __set($index, $value) {
        if (!array_key_exists($index, $this->items)) {
            throw new \Duke\Exception\InvalidConfigKey;
        }
        $u = new \Cdc\Sql\Update(\C::connection());
        $u->cols(array('valor' => $value))->from(array('configuracao'))->where(array('chave =' => $index))->stmt();
    }

    public function current() {
        return current($this->items);
    }

    public function key() {
        return $this->position;
    }

    public function next() {
        next($this->items);
        $this->position = key($this->items);
    }

    public function rewind() {
        reset($this->items);
        $this->position = key($this->items);
    }

    public function valid() {
        return array_key_exists($this->position, $this->items);
    }

    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);

    }

    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value)
    {
        throw new \Exception('Please don\'t do this');
    }

    public function offsetUnset($offset)
    {
        throw new \Exception('Please don\'t do this');
    }

}

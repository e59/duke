<?php

namespace Duke\Crud;

class Create extends Base {

    public function exec() {
        return $this->save();
    }

}

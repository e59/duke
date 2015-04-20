<?php

namespace Duke\Crud;

class Update extends Base {

    public function exec() {
        return $this->save(true);
    }

}

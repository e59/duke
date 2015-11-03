<?php

namespace Duke\Authenticator;

use Nette,
    Nette\Utils\Strings,
    \Hautelook\Phpass\PasswordHash,
    \Nette\Utils\Arrays as A,
    \Cdc\Sql\Select as S,
    \C as C;

class Database extends Nette\Object implements Nette\Security\IAuthenticator {

    protected $passwordColumn = 'senha';

    protected $userColumn = 'email';

    protected $checkActive = true;

    protected $useRoles = true;

    protected $buildAcl = true;

    protected $userTable = 'usuario';

    protected $roleAttachment = 'grupo';

    protected $roleColumn = 'id';

    protected $definition;

    protected $extraData = array();

    protected $bypass = false;

    protected $where = array();

    /**
     * Boolean column indicating if the user is active (i.e. not blocked, if he's blocked he can't login)
     * @var string
     */
    protected $activeColumn = 'ativo';

    public function setWhere($value) {
        $this->where = $value;
    }

    public function getWhere() {
        return $this->where;
    }

    public function setBypass($value) {
        $this->bypass = $value;
    }

    public function getBypass() {
        return $this->bypass;
    }

    public function setExtraData($value) {
        $this->extraData = $value;
    }

    public function getExtraData() {
        return $this->extraData;
    }

    public function setDefinition(\Cdc\Definition $def) {
        $this->definition = $def;
    }

    public function getDefinition() {
        if (!$this->definition) {
            $this->definition = new \Duke\Definition\Usuario;
        }
        return $this->definition;
    }

    public function setUserTable($value) {
        $this->userTable = $value;
    }

    public function getUserTable() {
        return $this->userTable;
    }

    public function setRoleColumn($value) {
        $this->roleColumn = $value;
    }

    public function getRoleColumn() {
        return $this->roleColumn;
    }

    public function setRoleAttachment($value) {
        $this->roleAttachment = $value;
    }

    public function getRoleAttachment() {
        return $this->roleAttachment;
    }

    public function setUseRoles($value) {
        $this->useRoles = $value;
    }

    public function getUseRoles() {
        return $this->useRoles;
    }

    public function setBuildAcl($value) {
        $this->buildAcl = $value;
    }

    public function getBuildAcl() {
        return $this->buildAcl;
    }

    public function setActiveColumn($value) {
        $this->activeColumn = $value;
    }

    public function setCheckActive($value) {
        $this->checkActive = $value;
    }

    public function getCheckActive() {
        return $this->checkActive;
    }

    public function getActiveColumn() {
        return $this->activeColumn;
    }

    public function setUserColumn($value) {
        $this->userColumn = $value;
    }

    public function getUserColumn() {
        return $this->userColumn;
    }

    public function setPasswordColumn($value) {
        $this->passwordColumn = $value;
    }

    public function getPasswordColumn() {
        return $this->passwordColumn;
    }

    public function getSql($credentials) {
        $sql = new S(C::connection());
        $sql->from = array($this->getUserTable());
        $sql->cols = array('*');
        $sql->where = $this->getWhere();
        $sql->where[$this->getUserColumn() . ' ='] = $credentials['user'];
        return $sql;
    }

    /**
     * Performs an authentication.
     * @return Nette\Security\Identity
     * @throws Nette\Security\AuthenticationException
     */
    public function authenticate(array $credentials) {

        $username = reset($credentials);
        $password = end($credentials);

        $row = false;

        $goodCredentials = array(
            'user' => $username,
            'password' => $password,
        );

        if (!($username == '' || $password == '')) {

            $sql = $this->getSql($goodCredentials);

            $row = \Cdc\ArrayHelper::current($this->getDefinition()->hydrated($sql));

        }

        if (!$row) {
            throw new Nette\Security\AuthenticationException('Email e/ou senha incorretos.', self::IDENTITY_NOT_FOUND);
        }

        if ($this->getCheckActive()) {
            if (!$row[$this->getActiveColumn()]) {
                throw new Nette\Security\AuthenticationException('Acesso negado. Por favor contate o administrador.', self::NOT_APPROVED);
            }
        }

        if (!$this->getBypass()) {
            if (!C::$hasher->CheckPassword(A::get($goodCredentials, 'password'), $row[$this->getPasswordColumn()])) {
                throw new Nette\Security\AuthenticationException('Email e/ou senha incorretos.', self::INVALID_CREDENTIAL);
            }
        }

        $arr = $this->buildUserCredentials($row, $this->getExtraData());
        unset($arr[$this->getPasswordColumn()]);

        $roles = array();
        if ($this->getUseRoles()) {
            foreach ($row[$this->getRoleAttachment()] as $userRole) {
                $roles[] = $userRole[$this->getRoleColumn()];
            }
        }

        return new Nette\Security\Identity($row['id'], $roles, $arr);
    }

    public function renewIdentity() {
        $id = C::$user->getId();
        $roles = C::$user->getRoles();
        $row = $this->buildUserCredentials(C::connection()->{$this->getUserTable()}('id', $id)->fetch());
        $identity = C::$user->getIdentity();

        foreach ($row as $key => $value) {
            $identity->$key = $value;
        }
    }

    public function buildUserCredentials($row, $extraData) {
        $arr = A::mergeTree($extraData, $row);
        return $arr;
    }

}

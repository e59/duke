<?php

namespace Duke;

use \C as C;
use \Nette\Utils\Arrays as A;
use \Duke\Metadata\Resources as DR;

class Controller extends \Cdc\Controller {

    public $module = 'Duke';

    public $breadcrumb;

    public $menu;

    public $index;

    public $title;

    public $description;

    /**
     *
     * @var \Knp\Menu\ItemInterface
     */
    public $lastBreadcrumb = array();

    public function init() {
        $this->menu = $this->buildMenu();
        $this->breadcrumb = $this->buildMenu(true);

        parent::init();
    }

    public function buildMenu($breadcrumb = false) {
        $menu = C::$menuFactory->createItem('Duke.Admin');

        if ($breadcrumb) {
            $menu->setChildrenAttribute('class', 'breadcrumb');
        } else {
            $menu->setChildrenAttribute('class', 'nav navbar-nav');
            $menu->setChildrenAttribute('id', 'main-menu');
        }


        $menu->addChild('duke', array(
            'uri' => $this->link('duke'),
            'label' => '<span class="fa fa-dashboard"></span> Início',
            'extras' => array(
                'safe_label' => true,
                'index' => 'duke',
            ),
        ));

        $item = $menu->addChild('duke.user_management', array(
                    'uri' => 'javascript:void(0)',
                    'label' => '<span class="fa fa-cogs"></span> Sistema<span class="icon icon-arrow"></span>',
                    'extras' => array(
                        'safe_label' => true,
                        'allow' => C::resourceAllowed(DR::ADMINISTRAR_SISTEMA),
                    )
                ))->setChildrenAttribute('class', 'sub-menu');

        $subitem = $item->addChild('duke/usuarios', array(
            'uri' => 'javascript:void(0)',
            'label' => 'Usuários',
            'extras' => array(
                'index' => 'duke/usuarios',
            ),
        ));

        $subitem->addChild('duke/usuarios/read', array(
            'uri' => $this->link('duke/usuarios/read'),
            'label' => 'Listar',
            'extras' => array(
                'index' => 'duke/usuarios/read',
            ),
        ));

        $subitem->addChild('duke/usuarios/create', array(
            'uri' => $this->link('duke/usuarios/create'),
            'label' => 'Novo',
            'extras' => array(
                'index' => 'duke/usuarios/create',
            ),
        ));


        $subitem = $item->addChild('duke/grupos', array(
            'uri' => 'javascript:void(0)',
            'label' => 'Grupos',
            'extras' => array(
                'index' => 'duke/grupos',
            ),
        ));

        $subitem->addChild('duke/grupos/read', array(
            'uri' => $this->link('duke/grupos/read'),
            'label' => 'Grupos',
            'extras' => array(
                'index' => 'duke/grupos/read',
            ),
        ));

        $subitem->addChild('duke/grupos/create', array(
            'uri' => $this->link('duke/grupos/create'),
            'label' => 'Novo',
            'extras' => array(
                'index' => 'duke/grupos/create',
            ),
        ));

        $item->addChild('duke/config', array(
            'uri' => $this->link('duke/config'),
            'label' => 'Variáveis de configuração',
            'extras' => array(
                'safe_label' => true,
                'index' => 'duke/config',
            ),
        ));

        $menu->addChild('duke.page', array(
            'uri' => $this->link('duke/pagina/read'),
            'label' => '<span class="fa fa-file-word-o"></span> Páginas',
            'extras' => array(
                'safe_label' => true,
                'index' => 'duke/pagina/read',
            ),
        ));


        $options = array(
            'allow_safe_labels' => true,
            'breadcrumb_clean_labels' => true,
            'currentClass' => 'active',
            'ancestorClass' => 'active',
            'lastClass' => 'last-child',
            'branch_class' => 'dropdown',
        );

        if (C::$changeMenuCallback) {
            if (is_array(C::$changeMenuCallback)) {
                foreach (C::$changeMenuCallback as $callback) {
                    call_user_func_array($callback, array('Duke', $this, $menu, &$options, $breadcrumb));
                }
            } else {
                call_user_func_array(C::$changeMenuCallback, array('Duke', $this, $menu, &$options, $breadcrumb));
            }

        }


        return compact('menu', 'options');
    }

    public function renderPager(\Nette\Utils\Paginator $p, $routeName = null, $params = array(), $get = array()) {
        if (!$routeName) {
            $routeName = $this->index;
        }

        ob_start();
        include $this->getTemplate('widgets/_pager.phtml');
        return ob_get_clean();
    }

    public function createPager($currentPage, $limitPerPage, $query) {
        $p = new \Nette\Utils\Paginator;
        $p->setBase(1);
        $p->setItemsPerPage($limitPerPage);
        $p->setPage($currentPage);

        $cols = $query->cols;

        $queryData = $query->stmtLazy(array());

        $stmt = \C::connection()->prepare('select count(*) from (' . $queryData['sql'] . ') c');

        foreach ($queryData['params'] as $k => $v) {
            $stmt->bindValue($k, $v[0], $v[1]);
        }
        $stmt->execute();

        $query->cols = array('count(*)');
        $p->setItemCount($stmt->fetch(\PDO::FETCH_COLUMN));
        $query->cols = $cols;

        $query->limit(array('limit' => $p->getLength(), 'offset' => $p->getOffset()));

        return $p;
    }

}

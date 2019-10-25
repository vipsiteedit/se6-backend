<?php

//use Twig_Extensions;

    class Controller extends Base_Controller
{
    protected $Alias;
    protected $sectionAlias;
    protected $vars = array();
    protected $valuesLimit = 0;
    protected $valuesOffset = 0;
    protected $sortBy = 'id';
    protected $app;

    public function __construct($alias = false, $sectionAlias = false)
    {
        $this->Alias = $alias;
        $this->sectionAlias = $sectionAlias;
        $this->app = App::get();
        $this->vars = $this->app['vars'];
    }

    public function Handler($vars = array())
    {

    }

    protected function setVars($vars)
    {
        foreach ($vars as $name => $var) {
            $this->vars[$name] = $var;
        }
    }

    public function execute($view = 'main', $vars = array())
    {
        //loadPage();
        if (file_exists(UNITS_DIR . '/' . ACTIVE_APP . '/' . $this->Alias . '.unit/tpl/' . $view . '.html')) {
            $loader = new Twig_Loader_Filesystem(UNITS_DIR . '/' . ACTIVE_APP . '/');

            // Extend path
            $loader->addPath(UNITS_DIR . '/' . ACTIVE_APP . '/' . $this->Alias . '.unit/tpl');
            $loader->addPath(TEMPLATES_DIR . '/' . ACTIVE_APP);

            $twig = new Twig_Environment($loader, array(
                'autoescape' => false,
            ));

            $extensions = glob(TWIG_EXTENSIONS_DIR . '/*.twig.php');
            foreach ($extensions as $extension) {
                require_once $extension;
                $ext_class_name = '\\Twig_Extensions\\' . ucfirst(strtolower(str_replace('.twig.php', '', basename($extension)))) . '_Twig_Extension';
                $twig->addExtension(new $ext_class_name());
            }
            $app = App::get();
            $vars['handler'] = $app['args']['uri'] . '?ajax=' . $this->Alias;

            if (!empty($vars))
                $this->vars = array_merge($this->vars, $vars);

            //render_extend_vars($this->vars);

            $template = $twig->loadTemplate($view . '.html');

            return $template->render($this->vars);
        }
    }

    public function Main($args = false)
    {
        if (!$this->setDataVars($args)) {
            return false;
        }
    }

    public function Item($args = false)
    {

        if (!$this->setDataVars($args, 'item')) {
            return false;
        }
    }

    // Обработка коллекции разделов
    protected function setDataVars($args = array(), $method = 'main')
    {
        if ($this->sectionAlias) {
            $section = GetSectionElementEx($this->sectionAlias);
            $this->vars['part']["args"] = $args;
            if (!$section->isVisible()) return;

            $groups = $section->getGroups();

            $vars['part'] = $section->getSection();
            $this->vars['part']['title'] = $vars['part']['title'];
            $this->vars['part']['description'] = $vars['part']['description'];

            $sectionId = $vars['part']['id'];

            $params = array();

            $tgroups = array();
            $valuesGroup = array();

            if ($method == 'item') {
                foreach ($args as $name => $arg) {
                    if (!in_array($name, array('code', 'id'))) continue;
                    $item = $section->getShortItemCollection(array($name => $arg));
                    if (!empty($item)) {
                        //$grs = (!empty($item['groups'])) ? explode(',', $item['groups']) : array();
                        $grs = (!empty($_SESSION['APP_SECTION_STORE'][$sectionId]['groups'])) ?
                            $grs = $_SESSION['APP_SECTION_STORE'][$sectionId]['groups'] : array();

                        $items = $section->getCollectionListId(false, array('groups' => $grs));
                        $cnt = count($items['items']);
                        for ($i = 0; $i < $cnt; $i++) {
                            if ($items['items'][$i]['id'] == $item['id']) {
                                $id_prev = ($i > 0) ? $items['items'][$i - 1]['id'] : 0;
                                $id = $items['items'][$i]['id'];
                                $id_next = ($i < $cnt - 1) ? $items['items'][$i + 1]['id'] : 0;
                            }
                        }
                        if (!empty($id_prev)) {
                            $result = $section->getCollectionItems(array($id_prev));
                            $records['prev'] = !empty($result) ? $result[0] : array();
                        }
                        if (!empty($id)) {
                            $result = $section->getCollectionItems(array($id), true);
                            $records['item'] = !empty($result) ? $result[0] : array();
                            $records['count'] = 1;
                        }
                        if (!empty($id_next)) {
                            $result = $section->getCollectionItems(array($id_next));
                            $records['next'] = !empty($result) ? $result[0] : array();
                        }
                        // print_r($records);
                    }

                    //$params['search'][] = array('field' => $name, 'value' => $arg);
                }
            } else {
                if (isset($_GET['find'])) {
                    if (!isset($params['search'])) $params['search'] = array();
                    $params['search'][] = array('field' => 'name,note', 'value' => '%' . $_GET['find'] . '%', 'sign' => 'LIKE');
                }

                if ($this->app['type'] == 'group') {
                    $params['group'] = $section->getSectionGroupsId($this->app['args']);
                }

                $limit = $offset = false;

                if (!empty($args['limit'])) {
                    $limit = (int)$args['limit'];
                }

                if (!empty($args['offset'])) {
                    $offset = (int)$args['offset'];
                }

                if (!empty($args['filter'])) {
                    $params['filter'] = $args['filter'];
                }

                if (!empty($args['sort'])) {
                    $params['sort'] = $args['sort'];
                }

                if (!empty($args['pagination'])) {
                    $params['pagination'] = true;
                }

                $params['groupTree'] = isset($args['groupTree']) ? (bool)$args['groupTree'] : true;

                $records = $section->getCollectionList(false, $params, $limit, $offset);

                foreach ($records['items'] as $record) {
                    foreach ($record['groups'] as $gr) {
                        if (empty($tgroups['items'][$gr])) {
                            $tgroups['items'][$gr] = $section->getSectionGroupItem($gr);
                        }
                        $valuesGroup[$gr]['items'][] = $record;
                    }
                }

                $this->vars['pagination'] = $records['pagination'];

                App::set(array_merge(App::get(), array('pagination' => $records['pagination'])));

            }

            $this->vars['part']["args"] = $args;
            $this->vars['part']["groups"] = $groups;
            $this->vars['part']["valuesCount"] = $records['count'];
            $this->vars['part']['values'] = $records;
            $this->vars['part']['gvalues'] = $valuesGroup;
            $this->vars['part']['thisgroups'] = $tgroups;
            $this->vars['part']['groupId'] = $params['group'];
        }
        return true;
    }
}
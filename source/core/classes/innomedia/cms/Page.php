<?php

namespace Innomedia\Cms;

class Page
{
    protected $module;
    protected $page;
    protected $blocks = array();
    protected $theme;
    protected $rows;
    protected $columns;
    protected $session;
    protected $context;
    /**
     * Page properties from page definition
     *
     * @var array
     */
    protected $properties = array();

    public function __construct(\Innomatic\Webapp\WebAppSession $session, $module, $page)
    {
        $this->module = strlen($module) ? $module : 'home';
        $this->page = strlen($page) ? $page : 'index';
        $this->session = $session;
        $processor = \Innomatic\Webapp\WebAppContainer::instance('\Innomatic\Webapp\WebAppContainer')->getProcessor();
        $this->context = \Innomedia\Context::instance(
            '\Innomedia\Context',
            \Innomatic\Core\RootContainer::instance('\Innomatic\Core\RootContainer')
                ->getHome().
            '/'.
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDomainId(),
            $processor->getRequest(),
            $processor->getResponse()
        );

        if ($this->session->isValid('innomedia_page_manager_page')) {
            $pageInSession = $this->session->get('innomedia_page_manager_page');
            if ($pageInSession != $this->module.'/'.$this->page) {
                $this->resetChanges();
            }
        }
    }

    public function isChanged()
    {
        return $this->session->isValid('innomedia_page_manager_changed');
    }

    protected function setChanged()
    {
        $this->session->put('innomedia_page_manager_changed', '1');
    }

    public function getModule()
    {
        return $this->module;
    }

    public function getPage()
    {
        return $this->page;
    }

    public function getBlocks()
    {
        return $this->blocks;
    }

    public function getRows()
    {
        return $this->rows;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function parsePage()
    {
        $page = $this->context->getPagesHome($this->module).$this->page.'.local.yml';
        if (!file_exists($page)) {
            $page = $this->context->getPagesHome($this->module).$this->page.'.yml';
        }

        if (!file_exists($page)) {
            return false;
        }

        // Checks if the page definition exists in session
        if (!$this->session->isValid('innomedia_page_manager_blocks')) {
            $def = yaml_parse_file($page);
            $properties = array();
            if (isset($def['properties'])) {
                $properties = $def['properties'];
            }
            $result = array();
            $rows = $columns = 0;
            $theme = $def['theme'];

            // Retrieve blocks definition
            foreach ($def['blocks'] as $blockDef) {
                $result[$blockDef['row']][$blockDef['column']][$blockDef['position']] = array('module' => $blockDef['module'], 'name' => $blockDef['name']);
                if ($blockDef['row'] > $rows) {
                    $rows = $blockDef['row'];
                }
                if ($blockDef['column'] > $columns) {
                    $columns = $blockDef['column'];
                }
            }
            ksort($result);
            foreach ($result as $row => $column) {
                ksort($result[$row]);
                foreach ($result[$row] as $row2 => $column2) {
                    // TODO fix warning removing @
                    @ksort($result[$row][$column2]);
                }
            }
            // Stores page definition in the session
            $this->session->put('innomedia_page_manager_blocks',     $result);
            $this->session->put('innomedia_page_manager_properties', $properties);
            $this->session->put('innomedia_page_manager_theme',      $theme);
            $this->session->put('innomedia_page_manager_rows',       $rows);
            $this->session->put('innomedia_page_manager_columns',    $columns);
            $this->session->put('innomedia_page_manager_page',       $this->module.'/'.$this->page);
        } else {
            // Retrieves page definition from the session
            $result     = $this->session->get('innomedia_page_manager_blocks');
            $properties = $this->session->get('innomedia_page_manager_properties');
            $theme      = $this->session->get('innomedia_page_manager_theme');
            $rows       = $this->session->get('innomedia_page_manager_rows');
            $columns    = $this->session->get('innomedia_page_manager_columns');
        }

        $this->blocks     = &$result;
        $this->properties = $properties;
        $this->rows       = $rows ? $rows : 1;
        $this->columns    = $columns ? $columns : 1;
        $this->theme      = $theme;
    }

    public function savePage() {
        $file = $this->context->getPagesHome($this->module).$this->page.'.local.yml';
        $yaml = array();

        if (strlen($this->theme)) {
            $yaml['theme'] = $this->theme;
        }

        foreach ($this->blocks as $row => $columns) {
            foreach ($columns as $column => $positions) {
                if (is_array($positions)) {
                    foreach ($positions as $position => $block) {
                        $yaml['blocks'][] = array(
                            'module'   => $block['module'],
                            'name'     => $block['name'],
                            'row'      => $row,
                            'column'   => $column,
                            'position' => $position
                        );
                    }
                }
            }
        }

        $yaml['properties'] = $this->properties;

        if (!yaml_emit_file($file, $yaml)) {
            return false;
        }

        $this->resetChanges();
        return true;
    }

    public function resetChanges() {
        $this->session->remove('innomedia_page_manager_blocks');
        $this->session->remove('innomedia_page_manager_properties');
        $this->session->remove('innomedia_page_manager_theme');
        $this->session->remove('innomedia_page_manager_rows');
        $this->session->remove('innomedia_page_manager_columns');
        $this->session->remove('innomedia_page_manager_page');
        $this->session->remove('innomedia_page_manager_changed');
    }
}



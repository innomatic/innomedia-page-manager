<?php

namespace Innomedia\Cms;

class Page
{
    protected $module;
    protected $pageId;
    protected $pageName;
    protected $blocks = array();
    protected $theme;
    protected $rows;
    protected $columns;
    protected $session;
    protected $context;
    protected $page;

    /**
     * Page properties from page definition
     *
     * @var array
     */
    protected $properties = array();

    public function __construct(\Innomatic\Webapp\WebAppSession $session, $module, $pageName, $pageId = 0)
    {
        $this->module   = strlen($module) ? $module : 'home';
        $this->pageName = strlen($pageName) ? $pageName : 'index';
        $this->pageId   = $pageId;
        $this->session  = $session;
        $this->context  = \Innomedia\Context::instance('\Innomedia\Context');
        $this->page     = new \Innomedia\Page($this->module, $this->pageName, $this->pageId);

        $this->page->parsePage();

        if ($this->session->isValid('innomedia_page_manager_page')) {
            $pageInSession = $this->session->get('innomedia_page_manager_page');
            if ($pageInSession != $this->module.'/'.$this->pageName) {
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

    public function getPageName()
    {
        return $this->pageName;
    }

    public function getPageId()
    {
        return $this->pageId;
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
        // TODO handle layout level blocks
        // TODO handle page instance level blocks
        $page = $this->context->getPagesHome($this->module).$this->pageName.'.local.yml';
        if (!file_exists($page)) {
            $page = $this->context->getPagesHome($this->module).$this->pageName.'.yml';
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
                $result[$blockDef['row']][$blockDef['column']][$blockDef['position']] = array(
                    'module' => $blockDef['module'],
                    'name' => $blockDef['name'],
                    'counter' => $blockDef['counter']
                );
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
            $this->session->put('innomedia_page_manager_page',       $this->module.'/'.$this->pageName);
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

    public function savePage($parameters) {
        if ($this->pageId != 0) {
            // Save page data
            $this->page->updateContent();
        }

        foreach ($this->blocks as $row => $column) {
            foreach ($column as $position => $blocks) {
                foreach ($blocks as $block) {
                    $hasBlockManager = false;
                    $blockName = ucfirst($block['module']).': '.ucfirst($block['name']);
                    $blockCounter = isset($block['counter']) ? $block['counter'] : 1;

                    $fqcn = \Innomedia\Block::getClass($this->context, $block['module'], $block['name']);
                    $included = @include_once $fqcn;
                    if ($included) {
                        // Find block class
                        $class = substr($fqcn, strrpos($fqcn, '/') ? strrpos($fqcn, '/') + 1 : 0, - 4);
                        if (class_exists($class)) {
                            if ($class::hasBlockManager()) {
                                $hasBlockManager = true;
                                $headers['0']['label'] = $blockName;
                                $managerClass = $class::getBlockManager();
                                $manager = new $managerClass($this->module.'/'.$this->pageName, $blockCounter, $this->pageId);
                                $manager->saveBlock($parameters[$block['module']][$block['name']][$blockCounter]);
                           }
                        }
                    }
                }
            }
        }
        return true;
        // TODO handle page instance level blocks

        $file = $this->context->getPagesHome($this->module).$this->pageName.'.local.yml';
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
                            'counter'  => $block['counter'],
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

    public function deletePage()
    {
        $this->page->deleteContent();
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



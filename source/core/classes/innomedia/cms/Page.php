<?php

namespace Innomedia\Cms;

class Page
{
    protected $module;
    protected $pageId;
    protected $pageName;
    protected $blocks         = array();
    protected $userBlocks     = array();
    protected $instanceBlocks = array();
    protected $cellParameters = array();
    protected $theme;
    protected $layout;
    protected $rows;
    protected $columns;
    protected $session;
    protected $context;
    protected $page;
    protected $scope;

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
        if ($pageId != 0) {
            $this->scope = 'content';
        } else {
            $this->scope = 'page';
        }
        $this->session  = $session;
        $this->context  = \Innomedia\Context::instance('\Innomedia\Context');
        $this->page     = new \Innomedia\Page($this->module, $this->pageName, $this->pageId);

        $this->page->parsePage();

        if ($this->session->isValid('innomedia_page_manager_page')) {
            $pageInSession = $this->session->get('innomedia_page_manager_page');
            if ($pageInSession != $this->module.'/'.$this->pageName.'/'.$pageId) {
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

    public function getUserBlocks()
    {
        return $this->userBlocks;
    }

    public function getInstanceBlocks()
    {
        return $this->instanceBlocks;
    }

    public function getCellParameters()
    {
        return $this->cellParameters;
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
            $pageObj = new \Innomedia\Page($this->module, $this->pageName, $this->pageId);

            $def = yaml_parse_file($page);
            $properties = array();
            if (isset($def['properties'])) {
                $properties = $def['properties'];
            }
            $result = array();
            $rows   = $columns = 0;
            $theme  = $def['theme'];
            $layout = $def['layout'];

            // Retrieve blocks layout definition
            $layoutBlocks = $pageObj->getLayoutBlocks();

            if (is_array($layoutBlocks)) {
                foreach ($layoutBlocks as $blockDef) {
                    // Check if the block supports current scope
                    $scopes = \Innomedia\Block::getScopes($this->context, $blockDef['module'], $blockDef['name']);
                    if (!in_array($this->scope, $scopes)) {
                        continue;
                    } elseif ($this->scope == 'page' && in_array('content', $scopes) && $this->page->requiresId()) {
                        continue;
                    }

                    if (!isset($blockDef['counter'])) {
                        $blockDef['counter'] = 1;
                    }

                    $result[$blockDef['row']][$blockDef['column']][$blockDef['position']] = array(
                        'module'  => $blockDef['module'],
                        'name'    => $blockDef['name'],
                        'counter' => $blockDef['counter']
                    );
                    if ($blockDef['row'] > $rows) {
                        $rows = $blockDef['row'];
                    }
                    if ($blockDef['column'] > $columns) {
                        $columns = $blockDef['column'];
                    }
                }
            }

            // Get page cells parameters
            $cellParameters = array();
            if (isset($def['cells'])) {
                foreach ($def['cells'] as $cellDef) {
                    $cellParameters[$cellDef['row']][$cellDef['column']] = $cellDef['parameters'];
                    if ($cellDef['row'] > $rows) {
                        $rows = $cellDef['row'];
                    }
                    if ($cellDef['column'] > $columns) {
                        $columns = $cellDef['column'];
                    }
                }
            }

            // Retrieve page blocks definition
            foreach ($def['blocks'] as $blockDef) {
                // Check if the block supports current scope
                $scopes = \Innomedia\Block::getScopes($this->context, $blockDef['module'], $blockDef['name']);
                if (!in_array($this->scope, $scopes)) {
                    continue;
                } elseif ($this->scope == 'page' && in_array('content', $scopes) && $this->page->requiresId()) {
                    continue;
                }

                $result[$blockDef['row']][$blockDef['column']][$blockDef['position']] = array(
                    'module'  => $blockDef['module'],
                    'name'    => $blockDef['name'],
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

            // Retrieve page user blocks definition
            $userBlocks = array();
            if (isset($def['userblocks']) && is_array($def['userblocks'])) {
                foreach ($def['userblocks'] as $blockDef) {
                    // Check if the block supports current scope
                    /*
                    $scopes = \Innomedia\Block::getScopes($this->context, $blockDef['module'], $blockDef['name']);
                    if (!in_array($this->scope, $scopes)) {
                        continue;
                    } elseif ($this->scope == 'page' && in_array('content', $scopes) && $this->page->requiresId()) {
                        continue;
                    }
                     */

                    $userBlocks[$blockDef['row']][$blockDef['column']][$blockDef['position']] = array(
                        'module'  => $blockDef['module'],
                        'name'    => $blockDef['name'],
                        'counter' => $blockDef['counter']
                    );
                    if ($blockDef['row'] > $rows) {
                        $rows = $blockDef['row'];
                    }
                    if ($blockDef['column'] > $columns) {
                        $columns = $blockDef['column'];
                    }
                }
                ksort($userBlocks);
                foreach ($userBlocks as $row => $column) {
                    ksort($userBlocks[$row]);
                    foreach ($userBlocks[$row] as $row2 => $column2) {
                        // TODO fix warning removing @
                        @ksort($userBlocks[$row][$column2]);
                    }
                }
            }

            // Stores page definition in the session
            $this->session->put('innomedia_page_manager_blocks',          $result);
            $this->session->put('innomedia_page_manager_instance_blocks', $instanceBlocks);
            $this->session->put('innomedia_page_manager_user_blocks',     $userBlocks);
            $this->session->put('innomedia_page_manager_cells',           $cellParameters);
            $this->session->put('innomedia_page_manager_properties',      $properties);
            $this->session->put('innomedia_page_manager_theme',           $theme);
            $this->session->put('innomedia_page_manager_layout',          $layout);
            $this->session->put('innomedia_page_manager_rows',            $rows);
            $this->session->put('innomedia_page_manager_columns',         $columns);
            $this->session->put('innomedia_page_manager_page',            $this->module.'/'.$this->pageName.'/'.$this->pageId);
        } else {
            // Retrieves page definition from the session
            $result         = $this->session->get('innomedia_page_manager_blocks');
            $userBlocks     = $this->session->get('innomedia_page_manager_user_blocks');
            $instanceBlocks = $this->session->get('innomedia_page_manager_instance_blocks');
            $cellParameters = $this->session->get('innomedia_page_manager_cells');
            $properties     = $this->session->get('innomedia_page_manager_properties');
            $theme          = $this->session->get('innomedia_page_manager_theme');
            $layout         = $this->session->get('innomedia_page_manager_layout');
            $rows           = $this->session->get('innomedia_page_manager_rows');
            $columns        = $this->session->get('innomedia_page_manager_columns');
        }

        $this->blocks         = &$result;
        $this->userBlocks     = $userBlocks;
        $this->instanceBlocks = $instanceBlocks;
        $this->cellParameters = $cellParameters;
        $this->properties     = $properties;
        $this->rows           = $rows ? $rows : 1;
        $this->columns        = $columns ? $columns : 1;
        $this->theme          = $theme;
        $this->layout         = $layout;
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
                    if (class_exists($fqcn)) {
                        if ($fqcn::hasBlockManager()) {
                            $hasBlockManager = true;
                            $headers['0']['label'] = $blockName;
                            $managerClass = $fqcn::getBlockManager();
                            if (class_exists($managerClass)) {
                                $manager = new $managerClass($this->module.'/'.$this->pageName, $blockCounter, $this->pageId);
                                $manager->saveBlock($parameters[$block['module']][$block['name']][$blockCounter]);
                            }
                        }
                    }
                }
            }
        }
        // @todo handle page user blocks
        // @TODO handle page instance level blocks

        // If the page contains user blocks we must update the page definition
        // file with the updated user blocks list
        if (!is_array($this->userBlocks)) {
            return true;
        }

        $file = $this->context->getPagesHome($this->module).$this->pageName.'.local.yml';
        $yaml = array();

        // Page theme
        if (strlen($this->theme)) {
            $yaml['theme'] = $this->theme;
        }

        if (strlen($this->layout)) {
            $yaml['layout'] = $this->layout;
        }

        // Page level blocks
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

        // Cell parameters
        if (is_array($this->cellParameters)) {
            foreach ($this->cellParameters as $row => $cellColumn) {
                foreach ($cellColumn as $column => $cellParameters) {
                    $yaml['cells'][] = array(
                        'row' => $row,
                        'column' => $column,
                        'parameters' => $cellParameters
                    );
                }
            }
        }

        // User blocks
        if (count($this->userBlocks) > 0) {
            foreach ($this->userBlocks as $row => $columns) {
                foreach ($columns as $column => $positions) {
                    if (is_array($positions)) {
                        foreach ($positions as $position => $block) {
                            $yaml['userblocks'][] = array(
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
        }

        // Page properties
        if (count($this->properties) > 0) {
            $yaml['properties'] = $this->properties;
        }

        // Write the local page definition file
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
        $this->session->remove('innomedia_page_manager_user_blocks');
        $this->session->remove('innomedia_page_manager_instance_blocks');
        $this->session->remove('innomedia_page_manager_cells');
        $this->session->remove('innomedia_page_manager_properties');
        $this->session->remove('innomedia_page_manager_theme');
        $this->session->remove('innomedia_page_manager_layout');
        $this->session->remove('innomedia_page_manager_rows');
        $this->session->remove('innomedia_page_manager_columns');
        $this->session->remove('innomedia_page_manager_page');
        $this->session->remove('innomedia_page_manager_changed');
    }
}



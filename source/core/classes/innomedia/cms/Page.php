<?php

namespace Innomedia\Layout\Editor;

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

        if ($this->session->isValid('innomedia_layout_editor_page')) {
            $pageInSession = $this->session->get('innomedia_layout_editor_page');
            if ($pageInSession != $this->module.'/'.$this->page) {
                $this->resetChanges();
            }
        }
    }

    public function isChanged()
    {
        return $this->session->isValid('innomedia_layout_editor_changed');
    }

    protected function setChanged()
    {
        $this->session->put('innomedia_layout_editor_changed', '1');
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
        if (!$this->session->isValid('innomedia_layout_editor_blocks')) {
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
            $this->session->put('innomedia_layout_editor_blocks',     $result);
            $this->session->put('innomedia_layout_editor_properties', $properties);
            $this->session->put('innomedia_layout_editor_theme',      $theme);
            $this->session->put('innomedia_layout_editor_rows',       $rows);
            $this->session->put('innomedia_layout_editor_columns',    $columns);
            $this->session->put('innomedia_layout_editor_page',       $this->module.'/'.$this->page);
        } else {
            // Retrieves page definition from the session
            $result     = $this->session->get('innomedia_layout_editor_blocks');
            $properties = $this->session->get('innomedia_layout_editor_properties');
            $theme      = $this->session->get('innomedia_layout_editor_theme');
            $rows       = $this->session->get('innomedia_layout_editor_rows');
            $columns    = $this->session->get('innomedia_layout_editor_columns');
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

    public function addBlock($module, $block, $row, $column, $position) {
        $this->blocks[$row][$column][$position] = array('module' => $module, 'name' => $block);
        $this->session->put('innomedia_layout_editor_blocks', $this->blocks);
        $this->setChanged();
    }

    public function moveBlock($row, $column, $position, $direction) {
        switch ($direction) {
            case 'up' :
                if ($row == 1) {
                    break;
                }
                $positions = count($this->blocks[$row -1][$column]);
                $this->blocks[$row -1][$column][$positions +1] = $this->blocks[$row][$column][$position];
                $this->removeBlock($row, $column, $position);
                break;
            case 'down' :
                $positions = count($this->blocks[$row +1][$column]);
                $this->blocks[$row +1][$column][$positions +1] = $this->blocks[$row][$column][$position];
                $this->removeBlock($row, $column, $position);
                break;
            case 'right' :
                $positions = count($this->blocks[$row][$column +1]);
                $this->blocks[$row][$column +1][$positions +1] = $this->blocks[$row][$column][$position];
                $this->removeBlock($row, $column, $position);
                break;
            case 'left' :
                if ($colum == 1) {
                    break;
                }
                $positions = count($this->blocks[$row][$column -1]);
                $this->blocks[$row][$column -1][$positions +1] = $this->blocks[$row][$column][$position];
                $this->removeBlock($row, $column, $position);
                break;
            case 'raise' :
                if ($position == 1) {
                    break;
                }
                $old_block = $this->blocks[$row][$column][$position];
                $this->blocks[$row][$column][$position]    = $this->blocks[$row][$column][$position -1];
                $this->blocks[$row][$column][$position -1] = $old_block;
                $this->session->put('innomedia_layout_editor_blocks', $this->blocks);
                break;
            case 'lower' :
                if ($position == count($this->blocks[$row][$column])) {
                    break;
                }
                $old_block = $this->blocks[$row][$column][$position];
                $this->blocks[$row][$column][$position]    = $this->blocks[$row][$column][$position +1];
                $this->blocks[$row][$column][$position +1] = $old_block;
                $this->session->put('innomedia_layout_editor_blocks', $this->blocks);
                break;
        }
        $this->setChanged();
    }

    public function removeBlock($row, $column, $position) {
        if (count($this->blocks[$row][$column]) > $position) {
            for ($i = $position; $i < count($this->blocks[$row][$column]); $i ++) {
                $this->blocks[$row][$column][$i] = $this->blocks[$row][$column][$i +1];
            }
        }
        unset ($this->blocks[$row][$column][count($this->blocks[$row][$column])]);
        $this->session->put('innomedia_layout_editor_blocks', $this->blocks);
        $this->setChanged();
    }

    public function removeRow($row) {
        if ($this->rows > $row) {
            for ($i = $row; $i < $this->rows; $i ++) {
                $this->blocks[$i] = $this->blocks[$i +1];
            }
        }
        unset ($this->blocks[$this->rows]);
        $this->rows--;
        $this->session->put('innomedia_layout_editor_rows',   $this->rows);
        $this->session->put('innomedia_layout_editor_blocks', $this->blocks);
    }

    public function removeColumn($column) {
        $rows = count($this->blocks);
        $columns = $this->columns;

        if ($columns > $column) {
            for ($i = 1; $i <= $rows; $i++) {
                for ($j = $column; $j <= $columns; $j++) {
                    $this->blocks[$i][$j] = $this->blocks[$i][$j +1];
                }
            }
        }

        for ($i = 1; $i <= $rows; $i ++) {
            unset ($this->blocks[$i][$this->columns]);
        }
        $this->columns--;
        $this->session->put('innomedia_layout_editor_columns', $this->columns);
        $this->session->put('innomedia_layout_editor_blocks',  $this->blocks);
        $this->setChanged();
    }

    public function addRow() {
        $rows = $this->session->get('innomedia_layout_editor_rows');
        $this->session->put('innomedia_layout_editor_rows', $rows +1);
        $this->rows++;
        $this->setChanged();
    }

    public function addColumn() {
        $columns = $this->session->get('innomedia_layout_editor_columns');
        $this->session->put('innomedia_layout_editor_columns', $columns +1);
        $this->columns++;
        $this->setChanged();
    }

    public function resetChanges() {
        $this->session->remove('innomedia_layout_editor_blocks');
        $this->session->remove('innomedia_layout_editor_properties');
        $this->session->remove('innomedia_layout_editor_theme');
        $this->session->remove('innomedia_layout_editor_rows');
        $this->session->remove('innomedia_layout_editor_columns');
        $this->session->remove('innomedia_layout_editor_page');
        $this->session->remove('innomedia_layout_editor_changed');
    }
}



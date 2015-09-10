<?php

use \Innomatic\Core\InnomaticContainer;
use \Innomatic\Wui\Widgets;
use \Innomatic\Wui\Dispatch;
use \Innomatic\Locale\LocaleCatalog;
use \Shared\Wui;

/**
 * Method for get number page.
 *
 * @param integer $pageNumber number of the page.
 * 
 * @return void
 */
function pagesListBuilder($pageNumber)
{

    $session = \Innomatic\Desktop\Controller\DesktopFrontController::instance(
        '\Innomatic\Desktop\Controller\DesktopFrontController'
    )->session;

    if ($session->isValid('parentid')) {
        $parentId = unserialize($session->get('parentid'));
    } 

    return \Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString(
        '', 
        array(array('view', 'default', array('parentid' => $parentId, 'pagenumber' => $pageNumber))) 
    );
}

class ImpagemanagerPanelViews extends \Innomatic\Desktop\Panel\PanelViews
{
    /**
     * Innomatic container.
     * 
     * @var \Innomatic\Core\InnomaticContainer 
     */
    protected $container;
    /**
     * Tenant data access.
     * 
     * @var \Innomatic\Dataaccess\DataAccess 
     */
    protected $dataAccess;
    /**
     * Localization catalog for the current panel.
     * 
     * @var \Innomatic\Locale\LocaleCatalog 
     */
    protected $localeCatalog;
    /**
     * Panel title.
     * 
     * @var string 
     */
    protected $pageTitle;
    /**
     * XML definition for the current panel view.
     * 
     * @var string 
     */
    protected $pageXml;
    /**
     * Panel status string.
     * 
     * @var string
     */
    protected $status;
    /**
     * Number of items for page
     * 
     * @var integer
     */
    protected $itemsPerPage = 25;

    public function update($observable, $arg = '')
    {
    }

    /**
     * Helper method for panel initialization.
     */
    public function beginHelper()
    {
        $this->container = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer');
        $this->dataAccess = $this->container->getCurrentDomain()->getDataAccess();

        $this->localeCatalog = new LocaleCatalog(
            'innomedia-page-manager::pagemanager_panel',
            $this->container->getCurrentUser()->getLanguage()
        );

        $this->icon = 'documentcopy';

        $this->toolbars['content'] = array(
            'content' => array(
              'label'         => $this->localeCatalog->getStr('content_toolbar'),
              'themeimage'    => 'documenttext',
              'action'        => \Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''))),
              'horiz'         => 'true'
            )
        );
    }

    /**
     * Helper method for panel initialization.
     */
    public function endHelper()
    {
        if (!strlen($this->pageTitle)) {
            $this->pageTitle = $this->localeCatalog->getStr('content_title');
        }

        $this->wuiContainer->addChild(
            new WuiInnomaticPage(
                'page',
                array(
                    'pagetitle'   => $this->pageTitle,
                    'icon'        => $this->icon,
                    'maincontent' => $this->wuiPanelContent,
                    'status'      => $this->status,
                    'toolbars'    => array(
                        new WuiInnomaticToolbar(
                            'view',
                            array(
                                'toolbars' => $this->toolbars,
                                'toolbar' => 'true'
                            )
                        )
                    )
                )
            )
        );
    }

    /**
     * Panel view for adding new content.
     * 
     * Parameters needed:
     * -  parentid: name of the parent page.
     * 
     * @param array $eventData WUI event data.
     */
    public function viewAddcontent($eventData)
    {
        $pagesList = \Innomedia\Page::getInstancePagesList();

        $pagesComboList = array();

        foreach ($pagesList as $pageItem) {
            list($module, $page) = explode('/', $pageItem);
            $pagesComboList[$pageItem] = ucfirst($module).': '.ucfirst($page);
        }
        ksort($pagesComboList);
        $firstPage = key($pagesComboList);
        list($module, $page) = explode('/', $firstPage);
        
        $this->tpl->set('contentTypeLabel', $this->localeCatalog->getStr('content_type_label'));
        $this->tpl->set('pagesComboList', $pagesComboList);
        $this->tpl->set('addContentLabel', $this->localeCatalog->getStr('addcontent_button'));
        $this->tpl->set('parentId', $eventData['parentid']);
    }

    /**
     * Default view with page tree and current page details.
     * 
     * @param array $eventData WUI event data.
     */
    public function viewDefault($eventData)
    {
        // --------------------------------------------------------------------
        // INITIALIZATION
        // --------------------------------------------------------------------

        $isContentPage = false;
        $isModule      = false;
        $isStaticPage  = false;
        $isHomePage    = false;

        // Set default parent id to the root page if not set.
        //
        if (!isset($eventData['parentid'])) {
            $parentId = '0';
        } else {
            $parentId = $eventData['parentid'];
        }


        \Innomatic\Desktop\Controller\DesktopFrontController::instance(
            '\Innomatic\Desktop\Controller\DesktopFrontController'
        )->session->put(
            'parentid', serialize($parentId)
        );

        // Check if the given parent is a content page, a static page or
        // a module name.
        //
        if ($parentId == '0') {
            // This is a static page (home/index).
            //
            $isStaticPage = true;
            $isHomePage   = true;
        } elseif (!is_numeric($parentId)) {
            if (substr($parentId, 0, strlen('module_')) == 'module_') {
                // This is a module name.
                //
                $isModule = true;
            } elseif (substr($parentId, 0, strlen('page_')) == 'page_') {
                // This is a static page (module + page).
                //
                $isStaticPage = true;
            }
        } else {
            // This is a content page.
            //
            $isContentPage = true;
        }

        $this->tpl->set('isModule',      $isModule      ? '1' : '0');
        $this->tpl->set('isContentPage', $isContentPage ? '1' : '0');
        $this->tpl->set('isStaticPage',  $isStaticPage  ? '1' : '0');
        $this->tpl->set('isHomePage',    $isHomePage    ? '1' : '0');
        
        // --------------------------------------------------------------------
        // PAGE TREE
        // --------------------------------------------------------------------

        // Action for going to the home page.
        //
        $homeAction = WuiEventsCall::buildEventsCallString(
            '', [['view', 'default', ['parentid' => 0]]]
        );
        
        // Extract all the pages with a node in the page tree path.
        //
        $parents_query = $this->dataAccess->execute(
            'SELECT ip.id,ipt.parent_id,ip.name '.
            'FROM innomedia_pages_tree AS ipt '.
            'JOIN innomedia_pages AS ip '.
            'ON ip.id=ipt.page_id'
        );

        $pages = array();
        $nodes = array();

        // Get the list of the static pages.
        //
        $staticPages = \Innomedia\Page::getNoInstancePagesList();

        // Add static pages to the nodes and pages list.
        //
        foreach ($staticPages as $staticPage) {
            list($module, $page) = explode('/', $staticPage);

            if ($module == 'home' && $page != 'index') {
                // Put home module pages under home root node, excluding the index page.
                //
                $nodes['page_'.$module.'_'.$page] = 0;
                $pages['page_'.$module.'_'.$page] = ucfirst($page);
            } elseif ($staticPage == 'home/index') {
                // Skip the home/index page
            } else {
                // Create a node and a leaf for any other module.
                //
                $nodes['module_'.$module] = 0;
                $pages['module_'.$module] = ucfirst($module);

                $nodes['page_'.$module.'_'.$page] = 'module_'.$module;
                $pages['page_'.$module.'_'.$page] = ucfirst($page);
            }
        }

        // Build the pages list with their parents.
        //
        while (!$parents_query->eof) {
            $nodes[$parents_query->getFields('id')] = $parents_query->getFields('parent_id');
            if ($parents_query->getFields('name') == '') {
                // If the page has no valid name, default to its internal id number.
                //
                $name =  $parents_query->getFields('id');
            } else {
                // The page has a valid name string.
                //
                $name = $parents_query->getFields('name'); 
            }
            $pages[$parents_query->getFields('id')] = $name;
            $parents_query->moveNext();
        }

        $tree_nodes = array();
        $tree_leafs = array();

        // Add content pages to the page tree.
        //
        foreach ($nodes as $node_id => $parent_id) {
            if (isset($nodes[$node_id])) {
                // This page has children, so set it as a node.
                //
                $tree_nodes[$parent_id][] = array('title' => $pages[$node_id], 'id' => $node_id);
            } else {
                // This page has no children, so set it as a leaf.
                //
                $tree_leafs[$parent_id][] = array('title' => $pages[$node_id], 'id' => $node_id);
            }
        }

        // Build the tree menu structure for the WUI widget.
        //
        $treeMenu = $this->buildTreeMenu($tree_nodes, $tree_leafs);

        if (strlen($treeMenu)) {
            $this->tpl->set('treeMenu',   $treeMenu);
        }
        $this->tpl->set('homeAction', $homeAction);
        $this->tpl->set('homeLabel',  $this->localeCatalog->getStr('home_label'));

        $wuiTheme = \Innomatic\Wui\WUI::instance('\Innomatic\Wui\WUI')->getTheme();
        $this->tpl->set('homeImageUrl', $wuiTheme->mIconsBase . $wuiTheme->mIconsSet['icons']['home']['base'] . '/icons/' . $wuiTheme->mIconsSet['icons']['home']['file']);

        // --------------------------------------------------------------------
        // PAGE DETAILS AND ACTIONS
        // --------------------------------------------------------------------

        // Set page name and title.
        //
        if ($isContentPage == true) {
            // Content page case.
            //
            $pageInfo = \Innomedia\Page::getModulePageFromId($parentId);
            $page = new \Innomedia\Page($pageInfo['module'], $pageInfo['page'], $parentId);
            $page->parsePage();
            $pageName = $page->getName();
            $pageType = ucfirst($pageInfo['module']).'/'.ucfirst($pageInfo['page']);
            
            $pageNameString = $pageName.' ('.$pageType.')';
        } elseif ($isHomePage == true) {
            // Home page case.
            //
            $pageNameString = 'Home / Index';
        } elseif ($isStaticPage == true) {
            // Static page case.
            //
            list(, $module, $page) = explode('_', $parentId);
            $pageNameString = ucfirst($module).' / '.ucfirst($page);
        } elseif ($isModule == true) {
            // Module case.
            //
            $pageNameString = ucfirst(substr($parentId, strlen('module_')));
        }

        $this->tpl->set('pageNameString', $pageNameString);

        // Action for editing the current page.
        // Not available when opening a module.
        //
        if ($isHomePage == true) {
            // Home page case.
            //
            $editAction = WuiEventsCall::buildEventsCallString(
                '', [['view', 'page', ['module' => 'home', 'page' => 'index', 'pageid' => 0]]]
            );
        } elseif ($isContentPage == true) {
            // Content page case.
            //
            $pageInfo = \Innomedia\Page::getModulePageFromId($parentId);
            $editAction = WuiEventsCall::buildEventsCallString(
                '', [['view', 'page', ['module' => $pageInfo['module'], 'page' => $pageInfo['page'], 'pageid' => $parentId]]]
            );
        } elseif ($isStaticPage == true) {
            // Static page case.
            //
            list(, $module, $page) = explode('_', $parentId);

            $editAction = WuiEventsCall::buildEventsCallString(
                '', [['view', 'page', ['module' => $module, 'page' => $page, 'pageid' => 0]]]
            );
        }

        $this->tpl->set('editContentLabel', $this->localeCatalog->getStr('editcontent_button'));
        $this->tpl->set('editAction',       $editAction);

        if ($isContentPage == true) {
            // Action for deleting a page.
            //
            $deleteAction = WuiEventsCall::buildEventsCallString(
                '',
                [ [ 'view', 'default', ['parentid' => 0] ],
                [ 'action', 'deletecontent', ['module' => $pageInfo['module'], 'page' => $pageInfo['page'], 'pageid' => $parentId] ] ]
            );

            $this->tpl->set('deleteAction', $deleteAction);
        }

        $this->tpl->set('deleteItemLabel',      $this->localeCatalog->getStr('delete_item_button'));
        $this->tpl->set('deleteConfirmMessage', $this->localeCatalog->getStr('delete_confirm_message'));

        // --------------------------------------------------------------------
        // PAGE CHILDREN LIST
        // --------------------------------------------------------------------

        // Add content button.
        //
        if ($isContentPage == true || $isHomePage == truee) {
            // Action for adding a child page.
            //
            $addAction = WuiEventsCall::buildEventsCallString(
                '', [['view', 'addcontent', ['parentid' => $parentId]]]
            );

            $this->tpl->set('newContentLabel', $this->localeCatalog->getStr('newcontent_button'));
            $this->tpl->set('addAction',       $addAction);
        }

        // Build the children pages table.
        //
        $childrenCount = 0;
        $pageChildren = [];
        if (is_numeric($parentId)) {
            // This is a content page.
            //
            $pageTree = new \Innomedia\PageTree();
            $childrenCount = count($pageTree->getPageChildren($parentId));

            if ($childrenCount > 0) {
                $pagesQuery = $this->dataAccess->execute(
                    'SELECT pg.id, pg.page, pg.name '.
                    'FROM innomedia_pages AS pg '.
                    'JOIN innomedia_pages_tree AS pt '.
                    'ON pg.id=pt.page_id '.
                    'WHERE pt.parent_id = '.$parentId
                );

                while (!$pagesQuery->eof) {
                    list ($module, $page) = explode('/', $pagesQuery->getFields('page'));

                    $pageChildren[] = [
                        'id'     => $pagesQuery->getFields('id'),
                        'name'   => strlen($pagesQuery->getFields('name')) ? $pagesQuery->getFields('name') : $pagesQuery->getFields('id'),
                        'module' => $module,
                        'page'   => $page
                    ];

                    $pagesQuery->moveNext();
                }
            }
        } else {
            // This is a static page.
            //
            $parentModule = substr($parentId, strlen('module_'));

            foreach ($staticPages as $staticPage) {
                list($module, $page) = explode('/', $staticPage);

                if ($module == $parentModule) {
                    $pageChildren[] = [
                        'id'     => 'page_'.$module.'_'.$page,
                        'name'   => ucfirst($page),
                        'module' => $module,
                        'page'   => $page
                    ];
                    $childrenCount++;
                }
            }
        }

        if ($isHomePage == true) {
            // When the current parent node is the home page, also add home
            // static pages (excluding the index) and the other modules.
            //
            $listedModules = [];
            foreach ($staticPages as $staticPage) {
                list ($module, $page) = explode('/', $staticPage);
                
                if ($module == 'home' && $page != 'index') {
                    // Put home pages under home root node, excluding the index page.
                    //
                    $pageChildren[] = [
                        'id'     => 'page_'.$module.'_'.$page,
                        'name'   => ucfirst($page),
                        'module' => $module,
                        'page'   => $page
                    ];
                    $childrenCount++;
                } elseif ($staticPage == 'home/index') {
                    // Skip the home/index page.
                    //
                } elseif (!isset($listedModules[$module])) {
                    // Create a node for each other module.
                    //
                    $pageChildren[] = [
                        'id'     => 'module_'.$module,
                        'name'   => ucfirst($module),
                        'module' => '',
                        'page'   => '' 
                    ];
                    $childrenCount++;
                    $listedModules[$module] = true;
                }
            }
        }

        // Check if there are pages with no tree path (for compatibility with
        // old content pages).
        //
        if ($isHomePage == true) {
            // Extract all the pages without a page tree path.
            //
            $orphanPagesQuery = $this->dataAccess->execute(
                'SELECT pg.id, pg.page, pg.name '.
                'FROM innomedia_pages AS pg '.
                'LEFT JOIN innomedia_pages_tree AS pt '.
                'ON pg.id = pt.page_id '.
                'WHERE pt.page_id IS NULL'
            );

            // Get the list of the static pages.
            //
            $staticPages = \Innomedia\Page::getNoInstancePagesList();

            while (!$orphanPagesQuery->eof) {
                $orphanPageData = $orphanPagesQuery->getFields();

                // Exclude static pages with saved page parameters.
                //
                if (!in_array($orphanPageData['page'], $staticPages)) {
                    list ($module, $page) = explode('/', $orphanPageData['page']);

                    // Add the orphan page to the page children list.
                    //
                    $pageChildren[] = [
                        'id'     => $orphanPageData['id'],
                        'name'   => strlen($orphanPageData['name']) ? $orphanPageData['name'] : $orphanPageData['id'],
                        'module' => $module,
                        'page'   => $page
                    ];
                    $childrenCount++;
                }
                $orphanPagesQuery->moveNext();
            }

            $orphanPagesQuery->free();
        }

        $deleteItemLabel      = $this->localeCatalog->getStr('delete_item_button');
        $editItemLabel        = $this->localeCatalog->getStr('edit_item_button');
        $deleteConfirmMessage = $this->localeCatalog->getStr('delete_confirm_message');

        $this->tpl->set('editItemLabel', $this->localeCatalog->getStr('edit_item_button'));

        foreach ($pageChildren as $id => $page) {
            $pageChildren[$id]['viewaction'] = WuiEventsCall::buildEventsCallString(
                '', [['view', 'default', ['parentid' => $page['id']]]]
            );

            $pageChildren[$id]['type'] = (strlen($page['module']) && strlen($page['page'])) ? ucfirst($page['module']).' / '.ucfirst($page['page']) : '';
            
            if ($isContentPage == true or $isHomePage == true) {
                $editAction  = WuiEventsCall::buildEventsCallString(
                    '',
                    [ [ 'view', 'page', ['module' => $page['module'], 'page' => $page['page'], 'pageid' => $page['id']] ] ]
                );
                $deleteAction = WuiEventsCall::buildEventsCallString(
                    '',
                    [ [ 'view', 'default', ['parentid' => $parentId] ], [ 'action', 'deletecontent', ['module' => $page['module'], 'page' => $page['page'], 'pageid' => $page['id']] ] ]
                );
                
                if (substr($pageChildren[$id]['id'], 0, strlen('module_')) != 'module_') {
                    $pageChildren[$id]['toolbars']['view']['edit'] = [
                        'label' => $editItemLabel,
                        'themeimage' => 'pencil',
                        'horiz' => 'true',
                        'action' => $editAction,
                    ];
                }
                
                
                if (substr($pageChildren[$id]['id'], 0, strlen('module_')) != 'module_'
                    && substr($pageChildren[$id]['id'], 0, strlen('page_')) != 'page_'
                ) {
                    $pageChildren[$id]['toolbars']['view']['delete'] = [
                        'label' => $deleteItemLabel,
                        'needconfirm' => 'true',
                        'confirmmessage' => $deleteConfirmMessage,
                        'themeimage' => 'trash',
                        'horiz' => 'true',
                        'action' => $deleteAction
                    ];
                }
            }
        }

        // Sort page children.
        //
        uasort($pageChildren, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        $this->tpl->set('childrenCount',        $childrenCount);
        $this->tpl->set('childrenContentLabel', $this->localeCatalog->getStr('children_content_label'));
        $this->tpl->setArray('pageChildren',    $pageChildren);
        $this->tpl->set('noChildrenLabel',      $this->localeCatalog->getStr('no_children_label'));
        
        // Build children pages table headers.
        //
        $tableHeaders = [];
        $tableHeaders[0]['label'] = $this->localeCatalog->getStr('page_name_header');
        $tableHeaders[1]['label'] = $this->localeCatalog->getStr('page_type_header');

        $this->tpl->set('tableHeaders', $tableHeaders);

        // Set number of element in the WUI view template variables.
        if (is_array($pageChildren)) {
            $numberItems = count($pageChildren);
        } else {
            $numberItems = 0;
        }
        $this->tpl->set('numberItems', $numberItems);

        // Set the WUI view template variables.
        // 
        $this->tpl->setArray('eventData', $eventData);

        // Set Paginator 
        // 
        $page = 1;
        if (isset($eventData['pagenumber'])) {
            $page = $eventData['pagenumber'];
        } else {
            $table = new \Shared\Wui\WuiTable('items_list', array());
            $page = $table->mPageNumber;
        }

        if ($page > ceil($numberItems / $this->itemsPerPage)) {
            $page = ceil($numberItems / $this->itemsPerPage);
        } 
        $from = ($page * $this->itemsPerPage) - $this->itemsPerPage;
        $to = $from + $this->itemsPerPage - 1;

        $this->tpl->set('itemsPerPage', $this->itemsPerPage);
        $this->tpl->set('from', $from);
        $this->tpl->set('to', $to);
    }

    /**
     * View for editing a content page or a static page.
     * 
     * @param array $eventData WUI event data.
     */
    public function viewPage($eventData)
    {
        $this->tpl->set('module', $eventData['module']);
        $this->tpl->set('page', $eventData['page']);
        $this->tpl->set('pageId', isset($eventData['pageid']) ? $eventData['pageid'] : 0);
    }

    /**
     * Build the tree menu structure.
     * 
     * @param array $catList
     * @param array $nodesList
     * @param number $level
     * @param number $id
     * @return string A menu structure ready for the WuiTreevmenu widget.:w
     */
    protected function buildTreeMenu($catList, $nodesList, $level = 1, $id = 0)
    {
        $menu = '';
        $dots = '';

        for ($i = 1; $i <= $level; $i++) {
            $dots .= '.';
        }

        $list = $catList[$id];
        uasort($list, function($a, $b) {
            return strcasecmp($a['title'], $b['title']);
        });

        $count_sub_items = 1;

        foreach ($list as $data) {

            // go to page two of the element's children, if the number of 
            // element's children is greater than the number of items per page
            if ($level >= 2 and count($list) > 0) {
                if ($count_sub_items <= $this->itemsPerPage) {
                    $count_sub_items++;
                } else {

                    $editAction = WuiEventsCall::buildEventsCallString(
                        '',
                        [['view', 'default', ['parentid' => $id, 'pagenumber' => 2]]]
                    );
                    $menu .= $dots.'|...|'.$editAction.'|...||'."\n";
                    break;
                }
            } 

            $editAction = WuiEventsCall::buildEventsCallString(
                '',
                [['view', 'default', ['parentid' => $data['id'], 'pagenumber' => 1]]]
            );
            $menu .= $dots.'|'.( strlen( $data['title'] ) > 25 ? substr( $data['title'], 0, 23 ).'...' : $data['title'] ).'|'.$editAction.'|'.$data['title'].'||'."\n";

            $menu .= $this->buildTreeMenu($catList, $nodesList, $level + 1, $data['id']);

            foreach ($nodesList[$data['id']] as $node_data) {

                $editAction = WuiEventsCall::buildEventsCallString(
                    '',
                    [['view', 'default', ['parentid' => $node_data['id'], 'pagenumber' => 1]]]
                );

                $menu .= $dots.'.|'.( strlen( $node_data['title'] ) > 25 ? substr( $node_data['title'], 0, 23 ).'...' : $node_data['title'] ).'|'.$editAction.'|'.$node_data['title'].'||'
                ."\n";
            }


        }

        if ($level == 1 and isset($nodesList[0])) {
            foreach ($nodesList[0] as $node_data) {
                $editAction = WuiEventsCall::buildEventsCallString(
                    '',
                    [['view', 'default', ['parentid' => $node_data['id'], 'pagenumber' => 1]]]
                );

                $menu .= '.|'.( strlen( $node_data['title'] ) > 25 ? substr( $node_data['title'], 0, 23 ).'...' : $node_data['title'] ).'|'.$editAction.'|'.$node_data['title'].'||'."\n";
            }
        }
        return $menu;
    }

}

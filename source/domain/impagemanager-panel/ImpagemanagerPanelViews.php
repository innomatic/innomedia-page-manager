<?php

use \Innomatic\Core\InnomaticContainer;
use \Innomatic\Wui\Widgets;
use \Innomatic\Wui\Dispatch;
use \Innomatic\Locale\LocaleCatalog;
use \Shared\Wui;

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
                    'maincontent' => new WuiXml('content', array('definition' => $this->pageXml)),
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

        $this->pageXml = '<vertgroup><children>
            <horizgroup><args><width>0%</width></args><children>
            <label><args><label>'.WuiXml::cdata($this->localeCatalog->getStr('content_type_label')).'</label></args></label>
            <combobox><args><id>pagetype</id><elements type="array">'.WuiXml::encode($pagesComboList).'</elements></args>
            </combobox>

             <button>
                <args>
                  <horiz>true</horiz>
                  <frame>false</frame>
                  <themeimage>mathadd</themeimage>
                  <label>'.$this->localeCatalog->getStr('addcontent_button').'</label>
                  <action>javascript:void(0)</action>
                </args>
                  <events>
                  <click>
                  var page = document.getElementById(\'pagetype\');
                  var pagevalue = page.options[page.selectedIndex].value;
                  var elements = pagevalue.split(\'/\');
                  xajax_AddContent(elements[0], elements[1], '.$eventData['parentid'].')</click>
                  </events>
              </button>

           </children></horizgroup>
            <horizbar/>
            <divframe><name>pageeditor</name>
              <args><id>pageeditor</id></args>
              <children><void/>
              </children>
            </divframe>
            <impagemanager>
              </impagemanager>
            </children></vertgroup>';
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
        $tree_menu = $this->buildTreeMenu($tree_nodes, $tree_leafs);

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

        if ($isContentPage == true) {
            // Action for deleting a page.
            //
            $deleteAction = WuiEventsCall::buildEventsCallString(
                '',
                [ [ 'view', 'default', ['parentid' => 0] ],
                [ 'action', 'deletecontent', ['module' => $pageInfo['module'], 'page' => $pageInfo['page'], 'pageid' => $parentId] ] ]
            );
        
            // Action for adding a child page.
            //
            $addAction = WuiEventsCall::buildEventsCallString(
                '', [['view', 'addcontent', ['parentid' => $parentId]]]
            );
        }

        // --------------------------------------------------------------------
        // PAGE CHILDREN LIST
        // --------------------------------------------------------------------

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
                    //$nodes['page_' . $module . '_' . $page] = 0;
                    //$pages['page_' . $module . '_' . $page] = ucfirst($page);
                } elseif ($staticPage == 'home/index') {
                    // Skip the home/index page
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

        // Sort page children.
        //
        uasort($pageChildren, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });
        
        // Build children pages table headers.
        //
        $tableHeaders = [];
        $tableHeaders[0]['label'] = $this->localeCatalog->getStr('page_name_header');
        $tableHeaders[1]['label'] = $this->localeCatalog->getStr('page_type_header');

        $wuiTheme = \Innomatic\Wui\WUI::instance('\Innomatic\Wui\WUI')->getTheme();
 
        $this->pageXml = '
<vertgroup>
  <children>
    <horizgroup>
          <args>
            <width>0%</width>
          </args>
      <children>

        <!-- Content tree -->

        <vertgroup>
          <args>
            <width>200</width>
          </args>
          <children>
            <horizgroup>
              <args>
                <width>0%</width>
                <align>middle</align>
              </args>
              <children>

                <image>
                  <args>
                    <width>18</width>
                    <height>18</height>
                    <imageurl>'.$wuiTheme->mIconsBase . $wuiTheme->mIconsSet['icons']['home']['base'] . '/icons/' . $wuiTheme->mIconsSet['icons']['home']['file'].'</imageurl>
                    <link>'.WuiXml::cdata($homeAction).'</link>
                  </args>
                </image>
                <link>
                  <args>
                    <label>'.WuiXml::cdata($this->localeCatalog->getStr('home_label')).'</label>
                    <bold>true</bold>
                    <link>'.WuiXml::cdata($homeAction).'</link>
                  </args>
                </link>

              </children>
            </horizgroup>
            <treevmenu><args><menu type="array">'.WuiXml::encode($tree_menu).'</menu></args></treevmenu>
          </children>
        </vertgroup>

        <vertbar />

        <vertgroup>
      <args>
        <width>100%</width>
      </args>
          <children>

            <!-- Page details -->

            <label>
              <args>
                <!--
                <label>'.WuiXml::cdata($this->localeCatalog->getStr('preview_content_label')).'</label>
                    -->
                <label>'.WuiXml::cdata($pageNameString).'</label>
                <bold>true</bold>
              </args>
            </label>
                ';

        if ($isModule == false) {
            $this->pageXml .= '
            <horizgroup>
              <args>
                <width>0%</width>
              </args>
              <children>';

        if ($isModule == false) {
            $this->pageXml .= '
             <button>
                <args>
                  <horiz>true</horiz>
                  <frame>false</frame>
                  <themeimage>pencil</themeimage>
                  <label>'.$this->localeCatalog->getStr('editcontent_button').'</label>
                  <action>'.WuiXml::cdata($editAction).'</action>
                </args>
              </button>';
        }

        if ($isContentPage == true) {
            $this->pageXml .= '
             <button>
                <args>
                  <horiz>true</horiz>
                  <frame>false</frame>
                  <themeimage>trash</themeimage>
                  <dangeraction>true</dangeraction>
                  <label>'.$this->localeCatalog->getStr('delete_item_button').'</label>
                  <needconfirm>true</needconfirm>
                  <confirmmessage>'.$this->localeCatalog->getStr('delete_confirm_message').'</confirmmessage>
                  <action>'.WuiXml::cdata($deleteAction).'</action>
                </args>
              </button>';
        }

        $this->pageXml .= '
              </children>
            </horizgroup>';

            if (!$isStaticPage or $isHomePage == true) {
                $this->pageXml .= '
            <horizbar />';
            }
        }

        if (!$isStaticPage or $isHomePage == true) {
            $this->pageXml .= '

            <!-- Page children -->

            <label>
              <args>
                <label>'.WuiXml::cdata($this->localeCatalog->getStr('children_content_label')).'</label>
                <bold>true</bold>
              </args>
            </label>';

            if ($isContentPage == true) {
                $this->pageXml .= '
            <horizgroup>
              <children>

             <button>
                <args>
                  <horiz>true</horiz>
                  <frame>false</frame>
                  <themeimage>mathadd</themeimage>
                  <label>'.$this->localeCatalog->getStr('newcontent_button').'</label>
                  <action>'.WuiXml::cdata($addAction).'</action>
                </args>
              </button>

              </children>
            </horizgroup>';
            }

            if ($childrenCount == 0) {
            $this->pageXml .= '
            <label>
              <args>
                <label>'.WuiXml::cdata($this->localeCatalog->getStr('no_children_label')).'</label>
              </args>
            </label>';
            } else {
                $this->pageXml .= '
                <table>
                  <args>
                    <headers type="array">'.WuiXml::encode($tableHeaders).'</headers>
                    <width>100%</width>
                  </args>
                  <children>';

                $tableRow = 0;
                foreach ($pageChildren as $page) {
                    $childViewAction = WuiEventsCall::buildEventsCallString(
                        '', [['view', 'default', ['parentid' => $page['id']]]]
                    );

                    $childType = (strlen($page['module']) && strlen($page['page'])) ? ucfirst($page['module']).' / '.ucfirst($page['page']) : '';
                    $this->pageXml .= '
                    <link row="'.$tableRow.'" col="0">
                      <args>
                        <label>'.WuiXml::cdata($page['name']).'</label>
                        <link>'.WuiXml::cdata($childViewAction).'</link>
                      </args>
                    </link>
                    <label row="'.$tableRow.'" col="1">
                      <args>
                        <label>'.WuiXml::cdata($childType).'</label>
                      </args>
                    </label>';

                    if ($isContentPage == true or $isHomePage == true) {
                        // Prepare WUI events calls for panel actions.
                        //
                        $editAction  = WuiEventsCall::buildEventsCallString(
                            '',
                            [ [ 'view', 'page', ['module' => $page['module'], 'page' => $page['page'], 'pageid' => $page['id']] ] ]
                        );
                        $deleteAction = WuiEventsCall::buildEventsCallString(
                            '',
                            [ [ 'view', 'default', ['parentid' => $parentId] ], [ 'action', 'deletecontent', ['module' => $page['module'], 'page' => $page['page'], 'pageid' => $page['id']] ] ]
                        );

                        $this->pageXml .= '
    <innomatictoolbar row="'.$tableRow.'" col="2">
      <args>
        <frame>false</frame>
        <toolbars type="array">'.WuiXml::encode([
            'view' => [
                'edit' => [
                    'label' => $this->localeCatalog->getStr('edit_item_button'),
                    'themeimage' => 'pencil',
                    'horiz' => 'true',
                    'action' => $editAction],
                'delete' => [
                    'label' => $this->localeCatalog->getStr('delete_item_button'),
                    'needconfirm' => 'true',
                    'confirmmessage' => $this->localeCatalog->getStr('delete_confirm_message'),
                    'themeimage' => 'trash',
                    'horiz' => 'true',
                    'action' => $deleteAction]
            ]]).'</toolbars>
      </args>
    </innomatictoolbar>';
                    }
                    $tableRow++;
                }
                $this->pageXml .= '
                  </children>
                </table>';
            }
        }

        $this->pageXml .= '
          </children>
        </vertgroup>

      </children>
    </horizgroup>
  </children>
</vertgroup>';
    }

    /**
     * View for editing a content page or a static page.
     * 
     * @param array $eventData WUI event data.
     */
    public function viewPage($eventData)
    {
        $module  = $eventData['module'];
        $page    = $eventData['page'];
        $pageId  = isset($eventData['pageid']) ? $eventData['pageid'] : 0;

        $this->pageXml = '<vertgroup>
            <children>
            <impagemanager>
              <args><module>'.WuiXml::cdata($module).'</module><page>'.WuiXml::cdata($page).'</page><pageid>'.$pageId.'</pageid></args>
            </impagemanager>
            </children>
            </vertgroup>';
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

        foreach ($list as $data) {
            $editAction = WuiEventsCall::buildEventsCallString(
                '',
                [['view', 'default', ['parentid' => $data['id']]]]
            );
            $menu .= $dots.'|'.( strlen( $data['title'] ) > 25 ? substr( $data['title'], 0, 23 ).'...' : $data['title'] ).'|'.$editAction.'|'.$data['title'].'||'."\n";

            $menu .= $this->buildTreeMenu($catList, $nodesList, $level + 1, $data['id']);

            foreach ($nodesList[$data['id']] as $node_data) {
                $editAction = WuiEventsCall::buildEventsCallString(
                    '',
                    [['view', 'default', ['parentid' => $node_data['id']]]]
                );

                $menu .= $dots.'.|'.( strlen( $node_data['title'] ) > 25 ? substr( $node_data['title'], 0, 23 ).'...' : $node_data['title'] ).'|'.$editAction.'|'.$node_data['title'].'||'
                ."\n";
            }
        }

        if ($level == 1) {
            foreach ($nodesList[0] as $node_data) {
                $editAction = WuiEventsCall::buildEventsCallString(
                    '',
                    [['view', 'default', ['parentid' => $node_data['id']]]]
                );

                $menu .= '.|'.( strlen( $node_data['title'] ) > 25 ? substr( $node_data['title'], 0, 23 ).'...' : $node_data['title'] ).'|'.$editAction.'|'.$node_data['title'].'||'."\n";
            }
        }
        return $menu;
    }

}

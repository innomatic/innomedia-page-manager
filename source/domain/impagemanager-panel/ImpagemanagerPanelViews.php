<?php

use \Innomatic\Core\InnomaticContainer;
use \Innomatic\Wui\Widgets;
use \Innomatic\Wui\Dispatch;
use \Innomatic\Locale\LocaleCatalog;
use \Shared\Wui;

class ImpagemanagerPanelViews extends \Innomatic\Desktop\Panel\PanelViews
{
    protected $container;
    protected $dataAccess;
    protected $localeCatalog;
    protected $pageTitle;
    protected $pageXml;
    protected $status;

    public function update($observable, $arg = '')
    {
    }

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

    public function viewDefault($eventData)
    {
        $isContentPage = false;
        $isModule      = false;
        $isStaticPage  = false;

        if (!isset($eventData['parentid'])) {
            $parentId = 0;
        } else {
            $parentId = $eventData['parentid'];
        }

        if (!is_numeric($parentId)) {
            if (substr($parentId, 0, strlen('module_')) == 'module_') {
                $isModule = true;
            } elseif (substr($parentId, 0, strlen('page_')) == 'page_') {
                $isStaticPage = true;
            }
        } else {
            $isContentPage = true;
        }

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
                // Put home pages under home root node, excluding the index page.
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
            $pages[$parents_query->getFields('id')] = $parents_query->getFields('name');
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

        // Action for going to the home page.
        //
        $homeAction = WuiEventsCall::buildEventsCallString(
            '', [['view', 'default', ['parentid' => 0]]]
        );

        // Action for editing the current page.
        // Not available when opening a module.
        //
        if (is_numeric($parentId)) {
            $pageInfo = \Innomedia\Page::getModulePageFromId($parentId);
            $editAction = WuiEventsCall::buildEventsCallString(
                '', [['view', 'page', ['module' => $pageInfo['module'], 'page' => $pageInfo['page'], 'pageid' => $parentId]]]
            );
        } elseif ($isStaticPage == true) {
            list(, $module, $page) = explode('_', $parentId);

            $editAction = WuiEventsCall::buildEventsCallString(
                '', [['view', 'page', ['module' => $module, 'page' => $page, 'pageid' => 0]]]
            );
        }

        // Action for adding a child page.
        //
        if (is_numeric($parentId)) {
            $addAction = WuiEventsCall::buildEventsCallString(
                '', [['view', 'addcontent', ['parentid' => $parentId]]]
            );
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

        // Check if there are pages with no tree path (for compatibility with
        // old content pages).
        //
        if (is_numeric($parentId) && $parentId == 0) {
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

                // Exlude static pages with saved page parameters.
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

        $tableHeaders = [];
        $tableHeaders[0]['label'] = $this->localeCatalog->getStr('page_name_header');
        $tableHeaders[1]['label'] = $this->localeCatalog->getStr('page_type_header');

        $this->pageXml = '
<vertgroup>
  <children>
    <horizgroup>
      <children>

        <!-- Content tree -->

        <vertgroup>
          <args>
            <width>0%</width>
          </args>
          <children>
            <link>
              <args>
                <label>'.WuiXml::cdata($this->localeCatalog->getStr('home_label')).'</label>
                <bold>true</bold>
                <link>'.WuiXml::cdata($homeAction).'</link>
              </args>
            </link>
            <treevmenu><args><menu type="array">'.WuiXml::encode($tree_menu).'</menu></args></treevmenu>
          </children>
        </vertgroup>

        <vertbar />

        <vertgroup>
      <args>
        <width>100%</width>
      </args>
          <children>';

        if ($isModule == false) {
            $this->pageXml .= '

            <!-- Page preview -->

            <label>
              <args>
                <label>'.WuiXml::cdata($this->localeCatalog->getStr('preview_content_label')).'</label>
                <bold>true</bold>
              </args>
            </label>

            <horizgroup>
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

        $this->pageXml .= '
              </children>
            </horizgroup>';

            if (!$isStaticPage) {
                $this->pageXml .= '
            <horizbar />';
            }
        }

        if (!$isStaticPage) {
            $this->pageXml .= '

            <!-- Page children -->

            <label>
              <args>
                <label>'.WuiXml::cdata($this->localeCatalog->getStr('children_content_label')).'</label>
                <bold>true</bold>
              </args>
            </label>
';

        if (is_numeric($parentId)) {
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

                    $this->pageXml .= '
                    <link row="'.$tableRow.'" col="0">
                      <args>
                        <label>'.WuiXml::cdata($page['name']).'</label>
                        <link>'.WuiXml::cdata($childViewAction).'</link>
                      </args>
                    </link>
                    <label row="'.$tableRow.'" col="1">
                      <args>
                        <label>'.WuiXml::cdata(ucfirst($page['module'].' / '.ucfirst($page['page']))).'</label>
                      </args>
                    </label>';

                    if ($isContentPage == true) {
                        // Prepare WUI events calls for panel actions.
                        //
                        $editAction  = WuiEventsCall::buildEventsCallString('', [ [ 'view', 'page', ['module' => $page['module'], 'page' => $page['page'], 'pageid' => $page['id']] ] ]);
                        $deleteAction = WuiEventsCall::buildEventsCallString('', [ [ 'view', 'default', ['parentid' => $parentId] ], [ 'action', 'deletecontent', ['module' => $page['module'], 'page' => $page['page'], 'pageid' => $page['id']] ] ]);

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

    protected function buildTreeMenu($catList, $nodesList, $level = 1, $id = 0)
    {
        $menu = '';
        $dots = '';

        for ($i = 1; $i <= $level; $i++) {
            $dots .= '.';
        }

        foreach ($catList[$id] as $data) {
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

<?php

use \Innomatic\Core\InnomaticContainer;
use \Innomatic\Wui\Widgets;
use \Innomatic\Wui\Dispatch;
use \Innomatic\Locale\LocaleCatalog;
use \Shared\Wui;

class ImpagemanagerPanelViews extends \Innomatic\Desktop\Panel\PanelViews
{
    protected $container;
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

    public function viewContent($eventData)
    {
        $pagesList = \Innomedia\Page::getInstancePagesList();
        $pageId    = isset($eventData['pageid']) && (int)$eventData['pageid'] != 0 ? $eventData['pageid'] : 0;

        $pagesComboList = array();
        $pagesComboList[] = '';

        foreach ($pagesList as $pageItem) {
            list($module, $page) = explode('/', $pageItem);
            $pagesComboList[$pageItem] = ucfirst($module).': '.ucfirst($page);
        }
        ksort($pagesComboList);

        if (isset($eventData['module']) && isset($eventData['page']) && isset($pagesComboList[$eventData['module'].'/'.$eventData['page']])) {
            $module = $eventData['module'];
            $page   = $eventData['page'];
            $firstPage = $module.'/'.$page;
        } else {
            $firstPage = key($pagesComboList);
            list($module, $page) = explode('/', $firstPage);
        }

        $this->pageXml = '<vertgroup>
            <children>
            <horizgroup><args><width>0%</width></args>
            <children>
            <label><args><label>'.WuiXml::cdata($this->localeCatalog->getStr('content_type_label')).'</label></args></label>
            <combobox><args><id>page</id><default>'.WuiXml::cdata($firstPage).'</default><elements type="array">'.WuiXml::encode($pagesComboList).'</elements></args>
              <events>
              <change>
              var page = document.getElementById(\'page\');
              var pagevalue = page.options[page.selectedIndex].value;
              var elements = pagevalue.split(\'/\');
              xajax_LoadContentList(elements[0], elements[1])</change>
              </events>
            </combobox>
            <divframe><args><id>content_list</id></args><children><void /></children></divframe>
            <divframe><args><id>lang_list</id></args><children><void /></children></divframe>
            <!--
            <formarg><args><id>pageid</id><value>'.$pageId.'</value></args></formarg>
            -->
            </children>
            </horizgroup>
            <horizbar />
            <divframe><args><id>content_editor</id></args><children><void /></children></divframe>
            <impagemanager>
            <!--
              <args><module>'.WuiXml::cdata($module).'</module><page>'.WuiXml::cdata($page).'</page><pageid>'.$pageId.'</pageid></args>
              -->
              </impagemanager>
            </children>
            </vertgroup>';
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

    public function viewPages($eventData)
    {
        $pagesList = \Innomedia\Page::getNoInstancePagesList();

        $pagesComboList = array();

        foreach ($pagesList as $pageItem) {
            list($module, $page) = explode('/', $pageItem);
            $pagesComboList[$pageItem] = ucfirst($module).': '.ucfirst($page);
        }
        ksort($pagesComboList);
        $firstPage = key($pagesComboList);
        list($module, $page) = explode('/', $firstPage);

        $this->pageXml = '<vertgroup>
            <children>
            <horizgroup><args><width>0%</width></args>
            <children>
            <label><args><label>'.WuiXml::cdata($this->localeCatalog->getStr('page_type_label')).'</label></args></label>
            <combobox><args><id>page</id><elements type="array">'.WuiXml::encode($pagesComboList).'</elements></args>
              <events>
              <change>
              var page = document.getElementById(\'page\');
              var pagevalue = page.options[page.selectedIndex].value;
              var elements = pagevalue.split(\'/\');
              xajax_WuiImpagemanagerLoadPage(elements[0], elements[1], 0)</change>
              </events>
            </combobox>
            <formarg><args><id>pageid</id><value>0</value></args></formarg>
            </children>
            </horizgroup>
            <horizbar />
            <impagemanager>
              <args><module>'.WuiXml::cdata($module).'</module><page>'.WuiXml::cdata($page).'</page></args>
            </impagemanager>
            </children>
            </vertgroup>';
    }

    public function viewDefault($eventData)
    {
        if (!isset($eventData['parentid'])) {
            $parentId = 0;
        } else {
            $parentId = $eventData['parentid'];
        }

        $parents_query = $this->container->getCurrentDomain()->getDataAccess()->execute(
            'SELECT innomedia_pages.id,parent_id,name FROM innomedia_pages_tree,innomedia_pages WHERE id=page_id'
        );

        $pages = array();
        $nodes = array();
        while (!$parents_query->eof) {
            $nodes[$parents_query->getFields('id')] = $parents_query->getFields('parent_id');
            $pages[$parents_query->getFields('id')] = $parents_query->getFields('name');
            $parents_query->moveNext();
        }

        $tree_nodes = array();
        $tree_leafs = array();

        foreach ($nodes as $node_id => $parent_id) {
            if (isset($nodes[$node_id])) {
                $tree_nodes[$parent_id][] = array('title' => $pages[$node_id], 'id' => $node_id);
            } else {
                $tree_leafs[$parent_id][] = array('title' => $pages[$node_id], 'id' => $node_id);
            }
        }

        $tree_menu = $this->buildTreeMenu($tree_nodes, $tree_leafs);

        $homeAction = WuiEventsCall::buildEventsCallString(
            '', [['view', 'default', ['parentid' => 0]]]
        );

        $pageInfo = \Innomedia\Page::getModulePageFromId($parentId);
        $editAction = WuiEventsCall::buildEventsCallString(
            '', [['view', 'page', ['module' => $pageInfo['module'], 'page' => $pageInfo['page'], 'pageid' => $parentId]]]
        );

        $addAction = WuiEventsCall::buildEventsCallString(
            '', [['view', 'addcontent', ['parentid' => $parentId]]]
        );

        $this->pageXml = '
<vertgroup>
  <children>
    <horizgroup>
      <args>
        <width>120</width>
      </args>
      <children>

        <!-- Content tree -->

        <vertgroup>
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
          <children>

            <!-- Page preview -->

            <horizgroup>
              <children>

             <button>
                <args>
                  <horiz>true</horiz>
                  <frame>false</frame>
                  <themeimage>pencil</themeimage>
                  <label>'.$this->localeCatalog->getStr('editcontent_button').'</label>
                  <action>'.WuiXml::cdata($editAction).'</action>
                </args>
              </button>

              </children>
            </horizgroup>

            <horizbar />

            <horizgroup>
              <children>

             <button>
                <args>
                  <horiz>true</horiz>
                  <frame>false</frame>
                  <themeimage>mathadd</themeimage>
                  <label>'.$this->localeCatalog->getStr('addcontent_button').'</label>
                  <action>'.WuiXml::cdata($addAction).'</action>
                </args>
              </button>

              </children>
            </horizgroup>

            <!-- Page children -->

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

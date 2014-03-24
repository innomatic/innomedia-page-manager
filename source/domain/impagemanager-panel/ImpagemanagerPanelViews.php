<?php

use \Innomatic\Core\InnomaticContainer;
use \Innomatic\Wui\Widgets;
use \Innomatic\Wui\Dispatch;
use \Innomatic\Locale\LocaleCatalog;
use \Shared\Wui;

class ImpagemanagerPanelViews extends \Innomatic\Desktop\Panel\PanelViews
{
    protected $localeCatalog;
    protected $pageTitle;
    protected $pageXml;
    protected $status;

    public function update($observable, $arg = '')
    {
    }

    public function beginHelper()
    {
        $this->localeCatalog = new LocaleCatalog(
            'innomedia-page-manager::pagemanager_panel',
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
        );

        $this->icon = 'documentcopy';

        if (count(\Innomedia\Page::getInstancePagesList()) > 0) {
            $this->toolbars['content'] = array(
                'content'           => array(
                    'label'         => $this->localeCatalog->getStr('content_toolbar'),
                    'themeimage'    => 'documenttext',
                    'action'        => \Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''))),
                    'horiz'         => 'true'
                ),
                'addcontent'           => array(
                    'label'         => $this->localeCatalog->getStr('newcontent_toolbar'),
                    'themeimage'    => 'mathadd',
                    'action'        => \Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString('', array(array('view', 'addcontent', ''))),
                    'horiz'         => 'true'
                )
            );
        }

        $this->toolbars['pages'] = array(
        	'default'           => array(
        		'label'         => $this->localeCatalog->getStr('pages_toolbar'),
        		'themeimage'    => 'documenttext',
        		'action'        => \Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString('', array(array('view', 'pages', ''))),
        		'horiz'         => 'true'
        	)
        );

        $this->toolbars['global'] = array(
        	'default'           => array(
        		'label'         => $this->localeCatalog->getStr('global_toolbar'),
        		'themeimage'    => 'settings1',
        		'action'        => \Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString('', array(array('view', 'global', ''))),
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

    public function viewDefault($eventData)
    {
        $context   = \Innomedia\Context::instance('\Innomedia\Context');
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
        $context = \Innomedia\Context::instance('\Innomedia\Context');
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
                  xajax_AddContent(elements[0], elements[1])</click>
                  </events>
              </button>

           </children></horizgroup>
            <horizbar/>
            <divframe><name>pageeditor</name>
              <args><id>pageeditor</id></args>
              <children><void/>
              </children>
            </divframe>
            </children></vertgroup>';
    }

    public function viewPages($eventData)
    {
        $context = \Innomedia\Context::instance('\Innomedia\Context');
        $pagesList = \Innomedia\Page::getPagesList();

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

    public function viewGlobal($eventData)
    {

        $this->pageXml = '<vertgroup>
            <children>
            <form><args><id>globalparamsform</id></args><children><divframe><args><id>global_parameters</id></args><children>';

        $this->pageXml .= $this->getController()->getGlobalParametersXml();

        $this->pageXml .= '</children></divframe>
            </children></form>
            <horizbar/>

            <horizgroup><args><width>0%</width></args><children>
 <button>
    <args>
      <horiz>true</horiz>
      <frame>false</frame>
      <themeimage>buttonok</themeimage>
      <label>'.$this->localeCatalog->getStr('save_button').'</label>
      <action>javascript:void(0)</action>
    </args>
    			  <events>
                  <click>'.WuiXml::cdata('
                  var kvpairs = [];
var form = document.getElementById(\'globalparamsform\');
for ( var i = 0; i < form.elements.length; i++ ) {
   var e = form.elements[i];
   kvpairs.push(encodeURIComponent(e.id) + \'=\' + encodeURIComponent(e.value));
}
var params = kvpairs.join(\'&\');
                  xajax_SaveGlobalParameters(params)').'</click>
    			  </events>
  </button>
  <button>
    <args>
      <horiz>true</horiz>
      <frame>false</frame>
      <themeimage>buttoncancel</themeimage>
      <label>'.$this->localeCatalog->getStr('revert_button').'</label>
      <action>javascript:void(0)</action>
    </args>
    			  <events>
    			    <click>xajax_RevertGlobalParameters(\''.$module.'\', \''.$page.'\', \''.$pageId.'\')</click>
    			  </events>
                  </button>
                  </children></horizgroup>
            </children>
            </vertgroup>
                  ';

    }

}

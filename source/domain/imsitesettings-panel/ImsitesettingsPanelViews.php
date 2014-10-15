<?php

use \Innomatic\Core\InnomaticContainer;
use \Innomatic\Wui\Widgets;
use \Innomatic\Wui\Dispatch;
use \Innomatic\Locale\LocaleCatalog;
use \Shared\Wui;

class ImsitesettingsPanelViews extends \Innomatic\Desktop\Panel\PanelViews
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

        $this->icon = 'settings1';

        $this->toolbars['settings'] = array(
            'general' => array(
                'label'         => $this->localeCatalog->getStr('global_settings_toolbar'),
                'themeimage'    => 'settings1',
                'action'        => \Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''))),
                'horiz'         => 'true'
            ),
            'pages' => array(
                'label'         => $this->localeCatalog->getStr('pages_settings_toolbar'),
                'themeimage'    => 'documenttext',
                'action'        => \Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString('', array(array('view', 'pages', ''))),
                'horiz'         => 'true'
            )
        );
    }

    public function endHelper()
    {
        if (!strlen($this->pageTitle)) {
            $this->pageTitle = $this->localeCatalog->getStr('settings_title');
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
        $this->pageXml = '<vertgroup><children>
          <form><args><id>globalparamsform</id></args><children><divframe><args><id>global_parameters</id></args><children>
          ';

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
              <mainaction>true</mainaction>
              <label>'.$this->localeCatalog->getStr('save_button').'</label>
              <action>javascript:void(0)</action>
            </args>
    			  <events>
              <click>'
                .WuiXml::cdata(
                  'var kvpairs = [];
                  var form = document.getElementById(\'globalparamsform\');
                  for ( var i = 0; i < form.elements.length; i++ ) {
                     var e = form.elements[i];
                     kvpairs.push(encodeURIComponent(e.id) + \'=\' + encodeURIComponent(e.value));
                  }
                  var params = kvpairs.join(\'&\');
                  xajax_SaveGlobalParameters(params)'
                )
              .'</click>
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
          </vertgroup>';
    }

    public function viewPages($eventData)
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
}

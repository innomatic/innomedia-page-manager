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

        $this->icon = 'elements';

        $this->toolbars['view'] = array(
        	'default' => array(
        		'label' => $this->localeCatalog->getStr('layout_editor_toolbar'),
        		'themeimage' => 'elements',
        		'action' => \Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''))),
        		'horiz' => 'true'
        	)
        );
       }

    public function endHelper()
    {
    	if (!strlen($this->pageTitle)) {
    		$this->pageTitle = $this->localeCatalog->getStr('editor_title');
    	}

    	$this->wuiContainer->addChild(
			new WuiInnomaticPage(
				'page',
				array(
					'pagetitle' => $this->pageTitle,
					'icon' => $this->icon,
					'maincontent' => new WuiXml('content', array('definition' => $this->pageXml)),
					'status' => $this->status,
					'toolbars' => array(
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
        $processor = \Innomatic\Webapp\WebAppContainer::instance('\Innomatic\Webapp\WebAppContainer')->getProcessor();
        $context = \Innomedia\Context::instance(
            '\Innomedia\Context',
            \Innomatic\Core\RootContainer::instance('\Innomatic\Core\RootContainer')
                ->getHome().
            '/'.
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDomainId(),
            $processor->getRequest(),
            $processor->getResponse()
        );
        $modules = $context->getModulesList();
        $pages_list = array();

        $pagesComboList = array();

        foreach ($modules as $module) {
            $module_obj = new \Innomedia\Module($context, $module);
            if (!$module_obj->hasPages()) {
                continue;
            }
            $pages_list[$module] = $module_obj->getPagesList();

            foreach ($pages_list[$module] as $page) {
                $pagesComboList[$module.'/'.$page] = ucfirst($module).': '.ucfirst($page);
            }
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
              xajax_WuiImpagemanagerLoadPage(elements[0], elements[1])</change>
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

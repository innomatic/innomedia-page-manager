<?php
namespace Shared\Wui;

use \Innomatic\Desktop\Controller;
require_once('innomatic/ajax/XajaxResponse.php');

class WuiImpagemanager extends \Shared\Wui\WuiWidget
{
    public function __construct($elemName, $elemArgs = '', $elemTheme = '', $dispEvents = '')
    {
        parent::__construct($elemName, $elemArgs, $elemTheme, $dispEvents);
    }

    public function generateSource()
    {
        $this->mLayout = '<div id="wui_impagemanager">'.$this->getHTML($this->mArgs['module'], $this->mArgs['page']).'</div>';
        return true;
    }

    protected function getHTML($module, $page, $modified = false)
    {
        $localeCatalog = new \Innomatic\Locale\LocaleCatalog(
            'innomedia-layout-editor::editor',
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
        );

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
        /*
        $modules = $context->getModulesList();
        $pages_list = array();

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
 */
        $editorPage = new \Innomedia\Cms\Page(
            DesktopFrontController::instance('\Innomatic\Desktop\Controller\DesktopFrontController')->session,
            $module,
            $page
        );

        if ($editorPage->isChanged()) {
            $modified = true;
        }

        $editorPage->parsePage();
        $blocks = $editorPage->getBlocks();
        $columns = $editorPage->getColumns();
        $rows = $editorPage->getRows();

        $html = '
            <style type="text/css">
            <!--
            .gridtable {
	margin: 2px;
	padding: 0px;
	border: solid 1px #e6e6e6;
}

.gridtable td {
	padding: 3px;
	vertical-align: middle;
	text-align: center;
	border: solid 1px #e6e6e6;
    }

.gridtable td td {
    border: 0;
    }
    .newgridblock {
	padding: 3px;
}

.gridblock {
	border-bottom: dotted 1px #e6e6e6;
	padding: 3px;
}

.gridblock p {
	margin: 0px;
	padding: 2px;
	padding-bottom: 5px;
	color: #c0c0c0;
    }

    .gridtable ul
{
	padding: 0px;
	display: block;
	margin: 0px;
	white-space: nowrap;
}

.gridtable li
{
	display: inline;
}

.gridtable img
{
	vertical-align: middle;
}

.gridtable li a
{
	font-size: 10px;
	font-weight: bold;
	text-decoration: none;
	vertical-align: middle;
}

    -->
</style>

<table class="gridtable">';

        for ($row = 1; $row <= $rows; $row++) {
            $html .= '<tr>';
            for ($column = 1; $column <= $columns; $column++) {
                $html .= '<td>';
                if (isset($blocks[$row][$column])) {
                    $positions = count($blocks[$row][$column]);
                    foreach ($blocks[$row][$column] as $position => $block) {
                        $html .= '<div class="gridblock">
                            <p>'.$block['module'].'/'.$block['name'].'</p>';

                        $fqcn = \Innomedia\Block::getClass($context, $block['module'], $block['name']);
                        $html .= $fqcn;
                        $included = @include_once $fqcn;
                        if ($included) {
                            // Find block class
                            $class = substr($fqcn, strrpos($fqcn, '/') ? strrpos($fqcn, '/') + 1 : 0, - 4);
                            if (class_exists($class)) {
                                if ($class::hasBlockManager()) {
                                    $managerClass = $class::getBlockManager();
                                    $manager = new $managerClass();
                                    $html .= WuiXml::getContentFromXml('', $manager->getManagerXml());
                               }
                            }
                        }

                        $html .= '
                            </div>';
                    }
                } else {
                }
                $html .= '</td>';
            }
            $html .= '            </tr>';
        }
        $html .= '<table>';

        $xml = '<vertgroup><children>
            <horizbar/>

            <horizgroup><args><width>0%</width></args><children>
            ';

        if ($modified == true) {
            $xml .= '  <button>
    <args>
      <horiz>true</horiz>
      <frame>false</frame>
      <themeimage>buttonok</themeimage>
      <label>'.$localeCatalog->getStr('save_button').'</label>
      <action>javascript:void(0)</action>
    </args>
    			  <events>
    			    <click>xajax_WuiImpagemanagerSavePage(\''.$module.'\', \''.$page.'\')</click>
    			  </events>
  </button>
  <button>
    <args>
      <horiz>true</horiz>
      <frame>false</frame>
      <themeimage>buttoncancel</themeimage>
      <label>'.$localeCatalog->getStr('revert_button').'</label>
      <action>javascript:void(0)</action>
    </args>
    			  <events>
    			    <click>xajax_WuiImpagemanagerRevertPage(\''.$module.'\', \''.$page.'\')</click>
    			  </events>
  </button>

';
        }

        $xml .= '
            </children></horizgroup>
            </children></vertgroup>';

        $html .= WuiXml::getContentFromXml('', $xml);

        return $html;
    }

    public static function ajaxLoadPage($module, $page)
    {
        $objResponse = new XajaxResponse();
        if (!(strlen($module) && strlen($page))) {
            return $objResponse;
        }

        $editorPage = new \Innomedia\Cms\Page(
            DesktopFrontController::instance('\Innomatic\Desktop\Controller\DesktopFrontController')->session,
            $module,
            $page
        );
        $editorPage->parsePage();

        $objResponse->addAssign("wui_impagemanager", "innerHTML", self::getHTML($module, $page, false));

        return $objResponse;
    }

    public static function ajaxSavePage($module, $page)
    {
        $objResponse = new XajaxResponse();
        if (!(strlen($module) && strlen($page))) {
            return $objResponse;
        }

        $editorPage = new \Innomedia\Cms\Page(
            DesktopFrontController::instance('\Innomatic\Desktop\Controller\DesktopFrontController')->session,
            $module,
            $page
        );
        $editorPage->parsePage();
        $editorPage->savePage();

        $objResponse->addAssign("wui_impagemanager", "innerHTML", self::getHTML($module, $page, false));

        return $objResponse;
    }

    public static function ajaxRevertPage($module, $page)
    {
        $objResponse = new XajaxResponse();
        if (!(strlen($module) && strlen($page))) {
            return $objResponse;
        }

        $editorPage = new \Innomedia\Cms\Page(
            DesktopFrontController::instance('\Innomatic\Desktop\Controller\DesktopFrontController')->session,
            $module,
            $page
        );
        $editorPage->parsePage();
        $editorPage->resetChanges();

        $objResponse->addAssign("wui_impagemanager", "innerHTML", self::getHTML($module, $page, false));

        return $objResponse;
    }

}

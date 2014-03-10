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

        $xml = '<vertgroup><children>
            <grid><args><width>100%</width></args><children>';
        $editorPage->parsePage();
        $blocks = $editorPage->getBlocks();
        $columns = $editorPage->getColumns();
        $rows = $editorPage->getRows();

        for ($row = 1; $row <= $rows; $row++) {
            for ($column = 1; $column <= $columns; $column++) {
                if (isset($blocks[$row][$column])) {
                    $positions = count($blocks[$row][$column]);
                    foreach ($blocks[$row][$column] as $position => $block) {
                        $hasBlockManager = false;
                        $blockName = ucfirst($block['module']).': '.ucfirst($block['name']);
                        $xml .= '<vertgroup row="'.$row.'" col="'.$column.'"><children>';

                        $fqcn = \Innomedia\Block::getClass($context, $block['module'], $block['name']);
                        $included = @include_once $fqcn;
                        if ($included) {
                            // Find block class
                            $class = substr($fqcn, strrpos($fqcn, '/') ? strrpos($fqcn, '/') + 1 : 0, - 4);
                            if (class_exists($class)) {
                                if ($class::hasBlockManager()) {
                                    $hasBlockManager = true;
                                    $headers['0']['label'] = $blockName;
                                    $managerClass = $class::getBlockManager();
                                    $manager = new $managerClass($module.'/'.$page);
                                    $xml .= '<table><args><width>400</width><headers type="array">'.
                                        WuiXml::encode($headers)
                                        .'</headers></args><children><vertgroup row="0" col="0"><children>'.
                                        $manager->getManagerXml().'</children></vertgroup></children></table>';
                               }
                            }
                        }

                        if (!$hasBlockManager) {
                            $xml .= '<label><args><label>'.WuiXml::cdata($blockName).'</label></args></label>';
                        }
                        $xml .= '</children></vertgroup>';
                    }
                } else {
                }
            }
        }

        $xml .= '</children></grid>
            <horizbar/>

            <horizgroup><args><width>0%</width></args><children>
            ';

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

        $xml .= '</children></horizgroup>
            </children></vertgroup>';

        return WuiXml::getContentFromXml('', $xml);
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

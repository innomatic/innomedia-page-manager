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
        $this->mLayout = '<div id="wui_impagemanager">'.$this->getHTML($this->mArgs['module'], $this->mArgs['page'], $this->mArgs['pageid']).'</div>';
        return true;
    }

    protected function getHTML($module, $page, $pageId = 0, $modified = false)
    {
        $localeCatalog = new \Innomatic\Locale\LocaleCatalog(
            'innomedia-page-manager::editor',
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
        );

        $context = \Innomedia\Context::instance('\Innomedia\Context');
        $editorPage = new \Innomedia\Cms\Page(
            DesktopFrontController::instance('\Innomatic\Desktop\Controller\DesktopFrontController')->session,
            $module,
            $page,
            $pageId
        );

        $xml = '<vertgroup><children>
            <form><name>impagemanager</name>
              <args><id>impagemanager</id></args>
              <children>';

        if ($editorPage->getPageId() != 0) {
            $xml .= '
              <grid><children>
                <label row="0" col="0"><args><label>'.WuiXml::cdata($localeCatalog->getStr('page_id_label')).'</label></args></label>
                <label row="0" col="1"><args><label>'.WuiXml::cdata($editorPage->getPageId()).'</label><bold>true</bold></args></label>
                <label row="1" col="0"><args><label>'.WuiXml::cdata($localeCatalog->getStr('page_title_label')).'</label></args></label>
                <string row="1" col="1"><args><id>page_title</id><value>'.WuiXml::cdata($editorPage->getPage()->getTitle()).'</value><size>100</size></args></string>
              </children></grid>
              <horizbar/>';
        }

        $xml .= '
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
                        $blockCounter = isset($block['counter']) ? $block['counter'] : 1;
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
                                    $manager = new $managerClass($module.'/'.$page, $blockCounter, $pageId);
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
            </children></form>
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
                  <click>'.WuiXml::cdata('
                  var pageTitle = document.getElementById(\'page_title\').value;
                  var kvpairs = [];
var form = document.getElementById(\'impagemanager\');
for ( var i = 0; i < form.elements.length; i++ ) {
   var e = form.elements[i];
   kvpairs.push(encodeURIComponent(e.id) + \'=\' + encodeURIComponent(e.value));
}
var params = kvpairs.join(\'&\');
                  xajax_WuiImpagemanagerSavePage(\''.$module.'\', \''.$page.'\', \''.$pageId.'\', pageTitle, params)').'</click>
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
    			    <click>xajax_WuiImpagemanagerRevertPage(\''.$module.'\', \''.$page.'\', \''.$pageId.'\')</click>
    			  </events>
  </button>
  <button>
    <args>
      <horiz>true</horiz>
      <frame>false</frame>
      <themeimage>trash</themeimage>
      <label>'.$localeCatalog->getStr('delete_button').'</label>
      <action>javascript:void(0)</action>
    </args>
    			  <events>
    			    <click>xajax_WuiImpagemanagerDeletePage(\''.$module.'\', \''.$page.'\', \''.$pageId.'\')</click>
    			  </events>
  </button>

';

        $xml .= '</children></horizgroup>
            </children></vertgroup>';

        return WuiXml::getContentFromXml('', $xml);
    }

    public static function ajaxLoadPage($module, $pageName, $pageId)
    {
        $objResponse = new XajaxResponse();
        if (!(strlen($module) && strlen($pageName))) {
            return $objResponse;
        }

        $editorPage = new \Innomedia\Cms\Page(
            DesktopFrontController::instance('\Innomatic\Desktop\Controller\DesktopFrontController')->session,
            $module,
            $pageName,
            $pageId
        );
        $editorPage->parsePage();

        $objResponse->addAssign("wui_impagemanager", "innerHTML", self::getHTML($module, $pageName, $pageId, false));

        return $objResponse;
    }

    public static function ajaxSavePage($module, $pageName, $pageId, $pageTitle, $parameters)
    {
        $objResponse = new XajaxResponse();
        if (!(strlen($module) && strlen($pageName))) {
            return $objResponse;
        }

        $editorPage = new \Innomedia\Cms\Page(
            DesktopFrontController::instance('\Innomatic\Desktop\Controller\DesktopFrontController')->session,
            $module,
            $pageName,
            $pageId
        );
        $editorPage->parsePage();
        $editorPage->getPage()->setTitle($pageTitle);
        $decodedParams = array();
        foreach (explode('&', $parameters) as $chunk) {
            $param = explode("=", $chunk);

            if ($param) {
                $moduleName = $blockName = '';

                $keys = explode('_', urldecode($param[0]));
                if (count($keys) < 4) {
                    // Key is not valid
                    continue;
                }

                $moduleName = array_shift($keys);
                $blockName = array_shift($keys);
                $blockCounter = array_shift($keys);
                $paramName = implode('_', $keys);
                $decodedParams[$moduleName][$blockName][$blockCounter][$paramName] = urldecode($param[1]);
            }
        }
        $editorPage->savePage($decodedParams);

        $objResponse->addAssign("wui_impagemanager", "innerHTML", self::getHTML($module, $pageName, $pageId, false));

        return $objResponse;
    }

    public static function ajaxRevertPage($module, $pageName, $pageId)
    {
        $objResponse = new XajaxResponse();
        if (!(strlen($module) && strlen($pageName))) {
            return $objResponse;
        }

        $editorPage = new \Innomedia\Cms\Page(
            DesktopFrontController::instance('\Innomatic\Desktop\Controller\DesktopFrontController')->session,
            $module,
            $pageName,
            $pageId
        );
        $editorPage->parsePage();
        $editorPage->resetChanges();

        $objResponse->addAssign("wui_impagemanager", "innerHTML", self::getHTML($module, $pageName, $pageId, false));

        return $objResponse;
    }

    public static function ajaxDeletePage($module, $pageName, $pageId)
    {
        $objResponse = new XajaxResponse();
        if (!(strlen($module) && strlen($pageName) && strlen($pageId))) {
            return $objResponse;
        }

        $editorPage = new \Innomedia\Cms\Page(
            DesktopFrontController::instance('\Innomatic\Desktop\Controller\DesktopFrontController')->session,
            $module,
            $pageName,
            $pageId
        );
        $editorPage->parsePage();
        $editorPage->deletePage();

        $objResponse->addAssign("wui_impagemanager", "innerHTML", '');

        return $objResponse;
    }

}

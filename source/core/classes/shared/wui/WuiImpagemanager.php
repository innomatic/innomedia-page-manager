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
        if (!(strlen($module) > 0 and strlen($page) > 0)) {
            return WuiXml::getContentFromXml('', '<void/>');
        }

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
              <children>

              <grid><children>';

        $gridRow = 0;

        if ($editorPage->getPageId() != 0) {
            $xml .= '
                <label row="'.$gridRow.'" col="0" halign="right"><args><label>'.WuiXml::cdata($localeCatalog->getStr('page_id_label')).'</label></args></label>
                <label row="'.$gridRow++.'" col="1"><args><label>'.WuiXml::cdata($editorPage->getPageId()).'</label><bold>true</bold></args></label>
                <label row="'.$gridRow.'" col="0" halign="right"><args><label>'.WuiXml::cdata($localeCatalog->getStr('page_name_label')).'</label></args></label>
                <string row="'.$gridRow++.'" col="1"><args><id>page_name</id><value>'.WuiXml::cdata($editorPage->getPage()->getName()).'</value><size>80</size></args></string>
                <label row="'.$gridRow.'" col="0" halign="right"><args><label>'.WuiXml::cdata($localeCatalog->getStr('page_url_label')).'</label></args></label>
                <string row="'.$gridRow++.'" col="1"><args><id>page_url_keywords</id><value>'.WuiXml::cdata($editorPage->getPage()->getUrlKeywords()).'</value><size>80</size></args></string>
              ';
        }

        $xml .= '
                <label row="'.$gridRow.'" col="0" halign="right"><args><label>'.WuiXml::cdata($localeCatalog->getStr('page_meta_description_label')).'</label></args></label>
                <string row="'.$gridRow++.'" col="1"><args><id>page_meta_description</id><value>'.WuiXml::cdata($editorPage->getPage()->getParameters()['meta_description']).'</value><size>80</size></args></string>
                <label row="'.$gridRow.'" col="0" halign="right"><args><label>'.WuiXml::cdata($localeCatalog->getStr('page_meta_keys_label')).'</label></args></label>
                <string row="'.$gridRow++.'" col="1"><args><id>page_meta_keys</id><value>'.WuiXml::cdata($editorPage->getPage()->getParameters()['meta_keys']).'</value><size>80</size></args></string>
              </children></grid>
              <horizbar/>
            <grid><args><width>100%</width></args><children>';
        $editorPage->parsePage();
        $blocks = $editorPage->getBlocks();
        $columns = $editorPage->getColumns();
        $rows = $editorPage->getRows();

        for ($row = 1; $row <= $rows; $row++) {
            for ($column = 1; $column <= $columns; $column++) {
                if (isset($blocks[$row][$column])) {
                    $positions = count($blocks[$row][$column]);
                    $xml .= '<vertgroup row="'.$row.'" col="'.$column.'"><children>';
                    foreach ($blocks[$row][$column] as $position => $block) {
                        $hasBlockManager = false;
                        $blockName = ucfirst($block['module']).': '.ucfirst($block['name']);
                        $blockCounter = isset($block['counter']) ? $block['counter'] : 1;


                        $fqcn = \Innomedia\Block::getClass($context, $block['module'], $block['name']);
                        if (class_exists($fqcn)) {
                            if ($fqcn::hasBlockManager()) {
                                $hasBlockManager = true;
                                $headers['0']['label'] = $blockName;
                                $managerClass = $fqcn::getBlockManager();
                                if (class_exists($managerClass)) {
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
                    }
                    $xml .= '</children></vertgroup>';
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
                  <click>'.WuiXml::cdata(($pageId != 0 ? 'var pageName = document.getElementById(\'page_name\').value; var urlKeywords = document.getElementById(\'page_url_keywords\').value;' : 'var pageName = \'\'; var urlKeywords = \'\'').'
var metaKeys  = document.getElementById(\'page_meta_keys\').value;
var metaDescription = document.getElementById(\'page_meta_description\').value;
                  var kvpairs = [];
var form = document.getElementById(\'impagemanager\');
for ( var i = 0; i < form.elements.length; i++ ) {
   var e = form.elements[i];
   kvpairs.push(encodeURIComponent(e.id) + \'=\' + encodeURIComponent(e.value));
}
var params = kvpairs.join(\'&\');
                  xajax_WuiImpagemanagerSavePage(\''.$module.'\', \''.$page.'\', \''.$pageId.'\', pageName, urlKeywords, metaDescription, metaKeys, params)').'</click>
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
  </button>';

  if ($pageId != 0) {
      $xml .= '
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
    }

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

    public static function ajaxSavePage($module, $page, $pageId, $pageName, $urlKeywords, $metaDescription, $metaKeys, $parameters)
    {
        $objResponse = new XajaxResponse();
        if (!(strlen($module) && strlen($page))) {
            return $objResponse;
        }

        $editorPage = new \Innomedia\Cms\Page(
            DesktopFrontController::instance('\Innomatic\Desktop\Controller\DesktopFrontController')->session,
            $module,
            $page,
            $pageId
        );
        $editorPage->parsePage();
        $editorPage->getPage()->setName($pageName);
        $editorPage->getPage()->setUrlKeywords($urlKeywords);
        $editorPage->getPage()->setParameter('meta_description', $metaDescription);
        $editorPage->getPage()->setParameter('meta_keys', $metaKeys);

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

        $objResponse->addAssign("wui_impagemanager", "innerHTML", self::getHTML($module, $page, $pageId, false));

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

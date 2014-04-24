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
            $container = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer');
            $dropzoneJs = '<script src="'.$container->getBaseUrl(false).'/shared/dropzone.js"></script>
                <link href="'.$container->getBaseUrl(false).'/shared/dropzone.css" type="text/css" rel="stylesheet">';
        $this->mLayout = $dropzoneJs.'<div id="wui_impagemanager">'.$this->getHTML($this->mArgs['module'], $this->mArgs['page'], $this->mArgs['pageid']).'</div>';
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

                <formarg><args><id>page_url_keywords</id><value>'.WuiXml::cdata($editorPage->getPage()->getUrlKeywords()).'</value></args></formarg>

              <grid><children>';

        $gridRow = 0;

        if ($editorPage->getPageId() != 0) {
            $xml .= '
                <label row="'.$gridRow.'" col="0" halign="right"><args><label>'.WuiXml::cdata($localeCatalog->getStr('page_id_label')).'</label></args></label>
                <label row="'.$gridRow.'" col="1"><args><label>'.WuiXml::cdata($editorPage->getPageId()).'</label><bold>true</bold></args></label>
                <label row="'.$gridRow.'" col="2" halign="right"><args><label>'.WuiXml::cdata($localeCatalog->getStr('page_address_label')).'</label></args></label>
                <link row="'.$gridRow++.'" col="3"><args><target>_blank</target><label>'.WuiXml::cdata($editorPage->getPage()->getPageUrl(true)).'</label><link>'.WuiXml::cdata($editorPage->getPage()->getPageUrl(true)).'</link></args></link>
                <label row="'.$gridRow.'" col="0" halign="right"><args><label>'.WuiXml::cdata($localeCatalog->getStr('page_name_label')).'</label></args></label>
                <string row="'.$gridRow++.'" col="1" halign="" valign="" colspan="3"><args><id>page_name</id><value>'.WuiXml::cdata($editorPage->getPage()->getName()).'</value><size>80</size></args></string>
              ';

            /*
                <label row="'.$gridRow.'" col="0" halign="right"><args><label>'.WuiXml::cdata($localeCatalog->getStr('page_url_label')).'</label></args></label>
                <string row="'.$gridRow++.'" col="1" halign="" valign="" colspan="3"><args><id>page_url_keywords</id><value>'.WuiXml::cdata($editorPage->getPage()->getUrlKeywords()).'</value><size>80</size></args></string>
            */
        }

        if ($editorPage->getPage()->requiresId() == false or ($editorPage->getPage()->requiresId() == true && $editorPage->getPageId() != 0)) {
            $xml .= '
                <label row="'.$gridRow.'" col="0" halign="right"><args><label>'.WuiXml::cdata($localeCatalog->getStr('page_title_label')).'</label></args></label>
                <string row="'.$gridRow++.'" col="1" halign="" valign="" colspan="3"><args><id>page_title</id><value>'.WuiXml::cdata($editorPage->getPage()->getParameters()['title']).'</value><size>80</size></args></string>
                <label row="'.$gridRow.'" col="0" halign="right"><args><label>'.WuiXml::cdata($localeCatalog->getStr('page_meta_description_label')).'</label></args></label>
                <string row="'.$gridRow++.'" col="1" halign="" valign="" colspan="3"><args><id>page_meta_description</id><value>'.WuiXml::cdata($editorPage->getPage()->getParameters()['meta_description']).'</value><size>80</size></args></string>
                <label row="'.$gridRow.'" col="0" halign="right"><args><label>'.WuiXml::cdata($localeCatalog->getStr('page_meta_keys_label')).'</label></args></label>
                <string row="'.$gridRow++.'" col="1" halign="" valign="" colspan="3"><args><id>page_meta_keys</id><value>'.WuiXml::cdata($editorPage->getPage()->getParameters()['meta_keys']).'</value><size>80</size></args></string>';
        }

        $xml .= '     </children></grid>
              <horizbar/>
            <grid><args><width>100%</width></args><children>';
        $editorPage->parsePage();
        $blocks         = $editorPage->getBlocks();
        if ($editorPage->getScope() == 'page') {
            $userBlocks = $editorPage->getUserBlocks();
        } else {
            $userBlocks = $editorPage->getInstanceBlocks();
        }
        $columns        = $editorPage->getColumns();
        $rows           = $editorPage->getRows();
        $cellParameters = $editorPage->getCellParameters();

        $gridRow = -1;
        for ($row = 1; $row <= $rows; $row++) {
            if (isset($blocks[$row]) or isset($cellParameters[$row])) {
                $gridRow++;
            }

            for ($column = 1; $column <= $columns; $column++) {
                if (isset($blocks[$row][$column])) {
                    //$positions = count($blocks[$row][$column]);

                    $colspan = '1';
                    if (isset($cellParameters[$row][$column]['colspan'])) {
                        $colspan = $cellParameters[$row][$column]['colspan'];
                    }

                    $rowspan = '1';
                    if (isset($cellParameters[$row][$column]['rowspan'])) {
                        $rowspan = $cellParameters[$row][$column]['rowspan'];
                    }

                    $xml .= '<vertgroup row="'.$gridRow.'" col="'.$column.'" halign="left" valign="top" colspan="'.$colspan.'" rowspan="'.$rowspan.'"><children>';
                    foreach ($blocks[$row][$column] as $position => $block) {
                        $hasBlockManager = false;
                        $blockName = ucfirst($block['module']).': '.ucfirst($block['name']);
                        $blockCounter = isset($block['counter']) ? $block['counter'] : 1;


                        $fqcn = \Innomedia\Block::getClass($context, $block['module'], $block['name']);
                        if (class_exists($fqcn)) {
                            if ($fqcn::hasBlockManager()) {
                                $hasBlockManager       = true;
                                $headers['0']['label'] = $blockName;
                                $managerClass          = $fqcn::getBlockManager();
                                if (class_exists($managerClass)) {
                                    $manager = new $managerClass($module.'/'.$page, $blockCounter, $pageId);
                                    $xml .= '<table><args><width>'.($column == 2 ? '700' : '250').'</width><headers type="array">'.
                                        WuiXml::encode($headers)
                                        .'</headers></args><children><vertgroup row="0" col="0"><args><width>'.($column == 2 ? '700' : '250').'</width></args><children>'.
                                        $manager->getManagerXml().'</children></vertgroup></children></table>';
                               }
                            }
                        }

                        if (!$hasBlockManager) {
                            $xml .= '<label><args><label>'.WuiXml::cdata($blockName).'</label></args></label>';
                        }
                    }
                    $xml .= '</children></vertgroup>';
                } elseif (isset($cellParameters[$row][$column])) {
                    // Check if the cell supports user/instance blocks for the current cell and scope
                    if (
                        isset($cellParameters[$row][$column]['accepts'])
                        && is_array($cellParameters[$row][$column]['accepts'])
                        && isset($cellParameters[$row][$column]['scope'])
                        && $cellParameters[$row][$column]['scope'] == $editorPage->getScope()
                    ) {
                        $supportedBlocks = \Innomedia\Block::getBlocksByTypes($cellParameters[$row][$column]['accepts']);
                        if (count($supportedBlocks)) {
                            $xml .= '<vertframe row="'.$gridRow.'" col="'.$column.'" halign="left" valign="top"><children>';
                            $position = 0;

                            if (true or $editorPage->getScope() == 'page') {
                                if (isset($userBlocks[$row][$column])) {
                                    $positions = count($userBlocks[$row][$column]);
                                    foreach ($userBlocks[$row][$column] as $position => $block) {
                                        $xml .= '<horizgroup><args><width>0%</width></args><children>';
                                        $hasBlockManager = false;
                                        $blockName = ucfirst($block['module']).': '.ucfirst($block['name']);
                                        $blockCounter = isset($block['counter']) ? $block['counter'] : 1;

                                        $fqcn = \Innomedia\Block::getClass($context, $block['module'], $block['name']);
                                        if (class_exists($fqcn)) {
                                            if ($fqcn::hasBlockManager()) {
                                                $hasBlockManager       = true;
                                                $headers['0']['label'] = $blockName;
                                                $managerClass          = $fqcn::getBlockManager();
                                                if (class_exists($managerClass)) {
                                                    $manager = new $managerClass($module.'/'.$page, $blockCounter, $pageId);
                                                    $xml .= '<table><args><width>'.($column == 2 ? '700' : '250').'</width><headers type="array">'.
                                                        WuiXml::encode($headers)
                                                        .'</headers></args><children><vertgroup row="0" col="0"><args><width>'.($column == 2 ? '700' : '250').'</width></args><children>'.
                                                        $manager->getManagerXml().'</children></vertgroup></children></table>';
                                               }
                                            }
                                        }

                                        if (!$hasBlockManager) {
                                            $xml .= '<label><args><label>'.WuiXml::cdata($blockName).'</label></args></label>';
                                        }

                                        $xml .= '<vertgroup><children><button>
                                            <args>
                                              <horiz>true</horiz>
                                              <frame>false</frame>
                                              <themeimage>trash</themeimage>
                                              <themeimagetype>mini</themeimagetype>
                                              <action>javascript:void(0)</action>
                                            </args>
                                              <events>
                                                <click>xajax_WuiImpagemanagerRemoveBlock(\''.$module.'\', \''.$page.'\', \''.$pageId.'\', \''.$row.'\', \''.$column.'\', \''.$position.'\')</click>
                                              </events>
                                          </button>';

                                         if ($position > 1) {
                                             $xml .= '<button>
                                            <args>
                                          <horiz>true</horiz>
                                          <frame>false</frame>
                                          <themeimage>arrowup</themeimage>
                                          <themeimagetype>mini</themeimagetype>
                                          <action>javascript:void(0)</action>
                                        </args>
                                          <events>
                                            <click>xajax_WuiImpagemanagerRaiseBlock(\''.$module.'\', \''.$page.'\', \''.$pageId.'\', \''.$row.'\', \''.$column.'\', \''.$position.'\')</click>
                                          </events>
                                      </button>';
                                     }

                                     if ($position < $positions) {
                                         $xml .= '<button>
                                        <args>
                                          <horiz>true</horiz>
                                          <frame>false</frame>
                                          <themeimage>arrowdown</themeimage>
                                          <themeimagetype>mini</themeimagetype>
                                          <action>javascript:void(0)</action>
                                        </args>
                                          <events>
                                            <click>xajax_WuiImpagemanagerLowerBlock(\''.$module.'\', \''.$page.'\', \''.$pageId.'\', \''.$row.'\', \''.$column.'\', \''.$position.'\')</click>
                                          </events>
                                      </button>';
                                    }

                                    $xml .= '</children></vertgroup></children></horizgroup>';
                                }
                            }
                        }
                        // Build the list of supported blocks
                        $supportedList = array();
                        foreach ($supportedBlocks as $supportedBlock) {
                            list($supportedModule, $supportedBlock) = explode('/', $supportedBlock);
                            $supportedList[$supportedModule.'/'.$supportedBlock] = ucfirst($supportedModule).': '.ucfirst($supportedBlock);
                        }

                        $addBlockName = 'addblockname'.rand();

                        $xml .= '<horizgroup><args><width>0%</width></args><children>';
                        $xml .= '<label><args><label>'.WuiXml::cdata($localeCatalog->getStr('add_block_label')).'</label></args></label>';
                        $xml .= '<combobox><args><id>'.$addBlockName.'</id><elements type="array">'.\Shared\Wui\WuiXml::encode($supportedList).'</elements></args></combobox>';
                        $xml .= '<button>
<args>
  <horiz>true</horiz>
  <frame>false</frame>
  <themeimage>mathadd</themeimage>
  <themeimagetype>mini</themeimagetype>
  <action>javascript:void(0)</action>
</args>
              <events>
              <click>'.WuiXml::cdata('
          var page = document.getElementById(\''.$addBlockName.'\');
          var pagevalue = page.options[page.selectedIndex].value;
          var elements = pagevalue.split(\'/\');
                xajax_WuiImpagemanagerAddBlock(\''.$module.'\', \''.$page.'\', \''.$pageId.'\', elements[0], elements[1], \''.$row.'\', \''.$column.'\', \''.($position+1).'\');
                ').'</click>
              </events>
</button>
';
                        $xml .= '</children></horizgroup>';

                        $xml .= '</children></vertframe>';
                    }
                }
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
                  <click>'.WuiXml::cdata(($pageId != 0 ? 'var pageName = document.getElementById(\'page_name\').value; var urlKeywords = document.getElementById(\'page_url_keywords\').value;' : 'var pageName = \'\'; var urlKeywords = \'\'; ').
        (($editorPage->getPage()->requiresId() == false or ($editorPage->getPage()->requiresId() == true && $editorPage->getPageId() != 0)) ?

'var pageTitle = document.getElementById(\'page_title\').value;
var metaKeys  = document.getElementById(\'page_meta_keys\').value;
var metaDescription = document.getElementById(\'page_meta_description\').value;' :
'var pageTitle = \'\';
    var metaKeys  = \'\';
var metaDescription = \'\';').'
                  var kvpairs = [];
var form = document.getElementById(\'impagemanager\');
for ( var i = 0; i < form.elements.length; i++ ) {
    var e = form.elements[i];
    if (e.type == \'checkbox\') {
        if (e.checked) {
            kvpairs.push(encodeURIComponent(e.id) + \'=\' + encodeURIComponent(e.value));
        }
    } else {
        kvpairs.push(encodeURIComponent(e.id) + \'=\' + encodeURIComponent(e.value));
    }
}
var params = kvpairs.join(\'&\');
xajax_WuiImpagemanagerSavePage(\''.$module.'\', \''.$page.'\', \''.$pageId.'\', pageName, urlKeywords, pageTitle, metaDescription, metaKeys, params)').'</click>
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

        $container = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer');
        $objResponse->addAssign("wui_impagemanager", "innerHTML", self::getHTML($module, $pageName, $pageId, false));
        $objResponse->addIncludeScript($container->getBaseUrl(false).'/shared/dropzone.js');

        return $objResponse;
    }

    public static function ajaxAddBlock($module, $page, $pageId, $blockModule, $blockName, $row, $column, $position)
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
        $editorPage->addBlock($blockModule, $blockName, $row, $column, $position);

        $objResponse->addAssign("wui_impagemanager", "innerHTML", self::getHTML($module, $page, $pageId, false));

        return $objResponse;
    }

    public static function ajaxRaiseBlock($module, $page, $pageId, $row, $column, $position)
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
        $editorPage->moveBlock($row, $column, $position, 'raise');

        $objResponse->addAssign("wui_impagemanager", "innerHTML", self::getHTML($module, $page, $pageId, false));

        return $objResponse;
    }

    public static function ajaxLowerBlock($module, $page, $pageId, $row, $column, $position)
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
        $editorPage->moveBlock($row, $column, $position, 'lower');

        $objResponse->addAssign("wui_impagemanager", "innerHTML", self::getHTML($module, $page, $pageId, false));

        return $objResponse;
    }

    public static function ajaxRemoveBlock($module, $page, $pageId, $row, $column, $position)
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
        $editorPage->removeBlock($row, $column, $position);

        $objResponse->addAssign("wui_impagemanager", "innerHTML", self::getHTML($module, $page, $pageId, false));

        return $objResponse;
    }

    public static function ajaxSavePage($module, $page, $pageId, $pageName, $urlKeywords, $pageTitle, $metaDescription, $metaKeys, $parameters)
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
        $editorPage->getPage()->setName($pageName)
            ->setUrlKeywords($urlKeywords)
            ->setParameter('title', $pageTitle)
            ->setParameter('meta_description', $metaDescription)
            ->setParameter('meta_keys', $metaKeys);

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

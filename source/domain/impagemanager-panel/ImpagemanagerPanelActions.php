<?php

use \Innomatic\Core\InnomaticContainer;
use \Innomatic\Wui\Widgets;
use \Innomatic\Wui\Dispatch;
use \Innomatic\Domain\User;
use \Shared\Wui;

class ImpagemanagerPanelActions extends \Innomatic\Desktop\Panel\PanelActions
{
    protected $localeCatalog;

    public function __construct(\Innomatic\Desktop\Panel\PanelController $controller)
    {
        parent::__construct($controller);
    }

    public function beginHelper()
    {
        $this->localeCatalog = new \Innomatic\Locale\LocaleCatalog(
            'innomedia-page-manager::pagemanager_panel',
            InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
        );
    }

    public function endHelper()
    {
    }

    public function executeDeletecontent($eventData)
    {
        $editorPage = new \Innomedia\Cms\Page(
            DesktopFrontController::instance('\Innomatic\Desktop\Controller\DesktopFrontController')->session,
            $eventData['module'],
            $eventData['page'],
            $eventData['pageid']
        );
        $editorPage->parsePage();
        $editorPage->deletePage();        
    }

    public static function ajaxAddContent($module, $page, $parentId)
    {
        $pageid = 0;
        $scope_page = 'backend';
        $contentPage = new \Innomedia\Page($module, $page, $pageid, $scope_page);
        $contentPage->addContent($parentId);
        $xml = '<vertgroup><children>
            <horizbar />
            <impagemanager>
            <args>
              <module>'.WuiXml::cdata($module).'</module>
              <page>'.WuiXml::cdata($page).'</page>
              <pageid>'.$contentPage->getId().'</pageid>
            </args>
            </impagemanager>
            </children></vertgroup>';

        $objResponse = new XajaxResponse();
        $objResponse->addAssign("pageeditor", "innerHTML", \Shared\Wui\WuiXml::getContentFromXml('pageeditor', $xml));

        return $objResponse;
    }

    public static function ajaxLoadContentList($module, $page)
    {
        $localeCatalog = new \Innomatic\Locale\LocaleCatalog(
            'innomedia-page-manager::pagemanager_panel',
            InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
        );

        $domainDa = InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
            ->getCurrentDomain()
            ->getDataAccess();

        $pagesQuery = $domainDa->execute(
            "SELECT id, name
            FROM innomedia_pages
            WHERE page=".$domainDa->formatText($module.'/'.$page)."
            ORDER BY name"
        );

        $pages = array();
        $pages[] = '';

        while (!$pagesQuery->eof) {
            $pages[$pagesQuery->getFields('id')] = $pagesQuery->getFields('name');
            $pagesQuery->moveNext();
        }

        $xml = '<horizgroup><children>
            <label><args><label>'.WuiXml::cdata($localeCatalog->getStr('content_item_label')).'</label></args></label>
            <combobox>
            <args>
            <id>pageid</id>
            <elements type="array">'.\Shared\Wui\WuiXml::encode($pages).'</elements>
            </args>
            <events>
            <change>'
            .\Shared\Wui\WuiXml::cdata(
                'var pageid = document.getElementById(\'pageid\').value;
                xajax_WuiImpagemanagerLoadPage(\''.$module.'\', \''.$page.'\', pageid);
                '
            ).'
            </change>
            </events>
            </combobox>
            </children></horizgroup>';

        $objResponse = new XajaxResponse();
        $objResponse->addAssign("content_list", "innerHTML", \Shared\Wui\WuiXml::getContentFromXml('contentlist', $xml));

        return $objResponse;
    }
}

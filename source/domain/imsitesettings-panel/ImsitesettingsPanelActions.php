<?php

use \Innomatic\Core\InnomaticContainer;
use \Innomatic\Wui\Widgets;
use \Innomatic\Wui\Dispatch;
use \Innomatic\Domain\User;
use \Shared\Wui;

class ImsitesettingsPanelActions extends \Innomatic\Desktop\Panel\PanelActions
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

    public static function ajaxSaveGlobalParameters($parameters)
    {
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

        $context = \Innomedia\Context::instance('\Innomedia\Context');
        $modulesList = $context->getModulesList();

        foreach ($modulesList as $module) {
            $moduleObj = new \Innomedia\Module($module);
            $moduleBlocks = $moduleObj->getBlocksList();
            foreach ($moduleBlocks as $block) {
                $scopes = \Innomedia\Block::getScopes($context, $module, $block);
                if (in_array('global', $scopes)) {
                    $fqcn = \Innomedia\Block::getClass($context, $module, $block);
                    if (class_exists($fqcn)) {
                        if ($fqcn::hasBlockManager()) {
                            $hasBlockManager = true;
                            $headers['0']['label'] = ucfirst($module).': '.ucfirst($block);
                            $managerClass = $fqcn::getBlockManager();
                            if (class_exists($managerClass)) {
                                $manager = new $managerClass('', 1, 0);
                                $manager->saveBlock($decodedParams[$module][$block][1]);
                            }
                        }
                    }
                }
            }
        }

        $xml = \ImsitesettingsPanelController::getGlobalParametersXml();

        $objResponse = new XajaxResponse();
        $objResponse->addAssign("global_parameters", "innerHTML", \Shared\Wui\WuiXml::getContentFromXml('contentlist', $xml));

        return $objResponse;
    }

    public static function ajaxRevertGlobalParameters()
    {
        $xml = \ImsitesettingsPanelController::getGlobalParametersXml();

        $objResponse = new XajaxResponse();
        $objResponse->addAssign("global_parameters", "innerHTML", \Shared\Wui\WuiXml::getContentFromXml('contentlist', $xml));

        return $objResponse;
    }

}

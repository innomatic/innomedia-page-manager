<?php

class ImsitesettingsPanelController extends \Innomatic\Desktop\Panel\PanelController
{
    public function update($observable, $arg = '')
    {
    }

    public static function getGlobalParametersXml()
    {
        $context = \Innomedia\Context::instance('\Innomedia\Context');
        $modulesList = $context->getModulesList();
        $xml = '<vertgroup><children>';

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
                                $xml .= '<table><args><width>100%</width><headers type="array">'.
                                    WuiXml::encode($headers)
                                    .'</headers></args><children><vertgroup row="0" col="0"><args><width>100%</width></args><children>'.
                                    $manager->getManagerXml().'</children></vertgroup></children></table>';
                            }
                        }
                    }
                }
            }
        }

        $xml .= '</children></vertgroup>';

        return $xml;
    }

}

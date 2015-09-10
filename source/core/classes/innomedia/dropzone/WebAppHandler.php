<?php
/**
 * Innomedia
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 *
 * @copyright  2008-2014 Innoteam Srl
 * @license    http://www.innomatic.io/license/   BSD License
 * @link       http://www.innomatic.io
 * @since      Class available since Release 1.0.0
 */
namespace Innomedia\Dropzone;

/**
 *
 * @author Alex Pagnoni <alex.pagnoni@innomatic.io>
 * @copyright Copyright 2014 Innoteam Srl
 * @since 1.0
 */
class WebAppHandler extends \Innomatic\Webapp\WebAppHandler
{

    /**
     * Inits the webapp handler.
     */
    public function init()
    {}

    public function doGet(\Innomatic\Webapp\WebAppRequest $req, \Innomatic\Webapp\WebAppResponse $res)
    {
        // Start Innomatic
        $innomatic = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer');
        $innomatic->setInterface(\Innomatic\Core\InnomaticContainer::INTERFACE_EXTERNAL);
        $root           = \Innomatic\Core\RootContainer::instance('\Innomatic\Core\RootContainer');
        $innomatic_home = $root->getHome() . 'innomatic/';
        $innomatic->bootstrap($innomatic_home, $innomatic_home . 'core/conf/innomatic.ini');

        // Authenticates the user.
        $auth = \Innomatic\Desktop\Auth\DesktopAuthenticatorHelperFactory::getAuthenticatorHelper(\Innomatic\Core\InnomaticContainer::MODE_DOMAIN);
        if (!$auth->authenticate()) {
            return;
        }

        $domainName = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDomainId();

        $location     = explode('/', $req->getPathInfo());
        if (!count($location) == 8) {
            $res->sendError(\Innomatic\Webapp\WebAppResponse::SC_NOT_FOUND, $req->getRequestURI());
        }

        $pageModule   = isset($location[1]) ? $location[1] : '';
        $pageName     = isset($location[2]) ? $location[2] : '';
        $pageId       = isset($location[3]) ? $location[3] : 0;
        $blockModule  = isset($location[4]) ? $location[4] : '';
        $blockName    = isset($location[5]) ? $location[5] : '';
        $blockCounter = isset($location[6]) ? $location[6] : 1;
        $fileId       = isset($location[7]) ? $location[7] : 1;

        // Handle case of image in site wide parameters
        if (!(strlen($pageModule) && strlen($pageName))) {
            $pageModule = 'site';
            $pageName   = 'global';
        }

        // Handle case of not content page
        if (!strlen($pageId)) {
            $pageId = '0';
        }

        $error = false;

        // Check if the page is valid
        if (!empty($_FILES)) {
            $tempFile = $_FILES['file']['tmp_name'];
            $targetPath = $innomatic_home.'core/temp/dropzone/'.$domainName.'/'.$pageModule.'/'.$pageName.'/'.$pageId.'/'.$blockModule.'/'.$blockName.'/'.$blockCounter.'/'.$fileId.'/';
            $targetFile = $targetPath.$_FILES['file']['name'];

            if (!file_exists($targetPath)) {
                \Innomatic\Io\Filesystem\DirectoryUtils::mktree($targetPath, 0750);
            }

            $error = !move_uploaded_file($tempFile, $targetFile);
        } else {
            $error = true;
        }

        if ($error !== false) {
            $res->sendError(\Innomatic\Webapp\WebAppResponse::SC_NOT_FOUND, $req->getRequestURI());
        }
    }

    public function doPost(\Innomatic\Webapp\WebAppRequest $req, \Innomatic\Webapp\WebAppResponse $res)
    {
        // We do get instead
        $this->doGet($req, $res);
    }

    /**
     * Destroys the webapp handler.
     */
    public function destroy()
    {}
}

?>

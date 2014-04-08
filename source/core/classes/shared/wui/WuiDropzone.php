<?php
namespace Shared\Wui;

class WuiDropzone extends \Innomatic\Wui\Widgets\WuiWidget
{
    public function __construct (
        $elemName,
        $elemArgs = '',
        $elemTheme = '',
        $dispEvents = ''
    ) {
        parent::__construct($elemName, $elemArgs, $elemTheme, $dispEvents);
    }

    protected function generateSource()
    {
        static $included;

        $dropzoneJs = '';

        $container = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer');

        if (!isset($included)) {
            $dropzoneJs = '<script src="'.$container->getBaseUrl(false).'/shared/dropzone.js"></script>
                <link href="'.$container->getBaseUrl(false).'/shared/dropzone.css" type="text/css" rel="stylesheet">';
            $included = true;
        }
/*
        $objResponse->addIncludeScript($container->getBaseUrl(false).'/shared/dropzone.js');
        $objResponse->addScript('
            var myDropzone = new Dropzone("#dropzone", { url: "/file/post"});
document.querySelector("#dropzone").classList.add("dropzone");
console.log(myDropzone);
        ');

*/
        /*
<div id="dropzone" style="width: 100%; height: 200px;"><form action="http://www.torrentplease.com/dropzone.php" class="dropzone" id="demo-upload">
</form></div>
         */

        $id           = isset($this->mArgs['id']) ? $this->mArgs['id'] : $this->mName;
        $pageModule   = $this->mArgs['pagemodule'];
        $pageName     = $this->mArgs['pagename'];
        $pageId       = $this->mArgs['pageid'];
        $blockModule  = $this->mArgs['blockmodule'];
        $blockName    = $this->mArgs['blockname'];
        $blockCounter = $this->mArgs['blockcounter'];
        $fileId       = $this->mArgs['fileid'];

        $fileParameters = $pageModule.'/'.$pageName.'/'.$pageId.'/'.$blockModule.'/'.$blockName.'/'.$blockCounter.'/'.$fileId;

        $this->mLayout = $dropzoneJs.'
<div id="'.$id.'"></div>
<script>
var dropzone = new Dropzone("#'.$id.'", { url: "'.$container->getBaseUrl(false).'/dropzone/'.$fileParameters.'"';

        if (isset($this->mArgs['maxfiles'])) {
            $this->mLayout .= ', maxFiles: '.$this->mArgs['maxfiles'];
        }

        $this->mLayout .= '});';

$this->mLayout .=
'document.querySelector("#'.$id.'").classList.add("dropzone");
console.log(myDropzone);
</script>';

/*
        $this->mLayout = ($this->mComments ? '<!-- begin ' . $this->mName . ' dropzone -->' : '') .
            $dropzoneJs.
            '<form action="/file/upload" class="dropzone" id="'.$id.'dropzone"></form>'.
            ($this->mComments ? '<!-- end ' . $this->mName . " dropzone -->\n" : '');
 */
        return true;
    }
}

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

        $id           = isset($this->mArgs['id']) ? $this->mArgs['id'] : $this->mName;
        $pageModule   = $this->mArgs['pagemodule'];
        $pageName     = $this->mArgs['pagename'];
        $pageId       = strlen($this->mArgs['pageid']) ? $this->mArgs['pageid'] : '0';
        $blockModule  = $this->mArgs['blockmodule'];
        $blockName    = $this->mArgs['blockname'];
        $blockCounter = $this->mArgs['blockcounter'];
        $fileId       = $this->mArgs['fileid'];

        $fileParameters = $pageModule.'/'.$pageName.'/'.$pageId.'/'.$blockModule.'/'.$blockName.'/'.$blockCounter.'/'.$fileId;

        $this->mLayout = ($this->mComments ? '<!-- begin ' . $this->mName . ' dropzone -->' : '') .
        $this->mLayout .= $dropzoneJs.'
<div id="'.$id.'"></div>
<script>
var dropzone = new Dropzone("#'.$id.'", { url: "'.$container->getBaseUrl(false).'/dropzone/'.$fileParameters.'"';

        if (isset($this->mArgs['maxfiles'])) {
            $this->mLayout .= ', maxFiles: '.$this->mArgs['maxfiles'];
        }

        $this->mLayout .= '});';

$this->mLayout .=
'document.querySelector("#'.$id.'").classList.add("dropzone");
</script>';

        $this->mLayout .= $this->mComments ? '<!-- end ' . $this->mName . " dropzone -->\n" : '';

        return true;
    }
}

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

        if (!isset($included)) {
            $container = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer');
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
        $this->mLayout = $dropzoneJs.'
<div id="'.$this->mName.'"></div>
<script>
alert(\'pino\');
var myDropzone = new Dropzone("#'.$this->mName.'", { url: "/file/post"});
document.querySelector("#'.$this->mName.'").classList.add("dropzone");
console.log(myDropzone);
</script>';
        $event_data = new \Innomatic\Wui\Dispatch\WuiEventRawData($this->mArgs['disp'], $this->mName, 'file');
/*
        $this->mLayout = ($this->mComments ? '<!-- begin ' . $this->mName . ' dropzone -->' : '') .
            $dropzoneJs.
            '<form action="/file/upload" class="dropzone" id="'.$this->mName.'dropzone"></form>'.
            ($this->mComments ? '<!-- end ' . $this->mName . " dropzone -->\n" : '');
 */
        return true;
    }
}

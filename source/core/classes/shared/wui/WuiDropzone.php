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
        $maxFiles     = $this->mArgs['maxfiles'];
        $fieldName    = isset($this->mArgs['fieldname']) ? $this->mArgs['fieldname'] : '';
        $paramPrefix  = isset($this->mArgs['paramprefix']) ? $this->mArgs['paramprefix'] : '';

        // Handle case of image in site wide parameters
        if (!(strlen($pageModule) && strlen($pageName))) {
            $pageModule = 'site';
            $pageName   = 'global';
        }

        $fileParameters = $pageModule.'/'.$pageName.'/'.$pageId.'/'.$blockModule.'/'.$blockName.'/'.$blockCounter.'/'.$fileId;

        // Start Add thumbnail
        $page = $pageModule.'/'.$pageName;
        $block = $blockModule.'/'.$blockName;

        $containerDropzoneId = "container_$id";
        $this->mLayout = ($this->mComments ? '<!-- begin ' . $this->mName . ' dropzone -->' : '') .
        $this->mLayout .= $dropzoneJs.'<div id="'.$containerDropzoneId.'">
            <div id="'.$id.'"></div>
            <script>
            var dropzone'.$id.' = new Dropzone("#'.$id.'", { url: "'.$container->getBaseUrl(false).'/dropzone/'.$fileParameters.'"';

        if (isset($maxFiles)) {
            $this->mLayout .= ', maxFiles: '.$maxFiles;
        }

        $this->mLayout .= ', addRemoveLinks: true, 
            removedfile: function(file) {
                var mediaId = file.mediaid;
                var mediaName = file.name;       

                if (mediaId != null) {
                    xajax_WuiDropzoneRemoveMedia(
                        \''.$containerDropzoneId.'\', 
                        \''.$page.'\', 
                        \''.$pageId.'\', 
                        \''.$block.'\', 
                        \''.$blockCounter.'\', 
                        \''.$fileId.'\', 
                        \''.$maxFiles.'\', 
                        \''.$fieldName.'\', 
                        mediaId, 
                        mediaName, 
                        \''.$paramPrefix.'\'
                    );

                } else {

                    var _ref;
                    return (_ref = file.previewElement) != null ? _ref.parentNode.removeChild(file.previewElement) : void 0;
                }
            }
        });
        ';

        $list_media = \Innomedia\Media::getMediaByParams($this->mArgs);

        $count = 0; 
        $hiddenInput = '';
        foreach ($list_media as $key => $media) {
            $mediaid   = $media['id'];
            $name      = $media['name'];
            $path      = $media['path'];
            
            $webappurl = $container->getCurrentDomain()->domaindata['webappurl'];
            $last_char = substr($webappurl, -1);  
            $separetor = $last_char == '/' ? '' : '/';

            $filetype  = $media['filetype']; 
            $typepath  = \Innomedia\Media::getTypePath($filetype);

            $pathfull  = $webappurl.$separetor.'/storage/'.$typepath.'/'.$path;

            $size = filesize(
                $container->getHome().'/../'
                .$container->getCurrentDomain()->domaindata['domainid']
                .'/storage/'.$typepath.'/'.$path
            );

            if ($paramPrefix) {
                $hiddenInput .= '<input id="'.$paramPrefix.'_'.$fieldName.'" type="hidden" name="wui[][evd]['.$fieldName.']" value="'.$mediaid.'">';
            }

            $this->mLayout .= '
                var mockFile = { name: "'.$name.'", size: "'.$size.'", mediaid: "'.$mediaid.'"};
                dropzone'.$id.'.options.addedfile.call(dropzone'.$id.', mockFile);'
                .($filetype != 'file' ? 'dropzone'.$id.'.options.thumbnail.call(dropzone'.$id.', mockFile, "'.$pathfull.'");' : '');

            $count++;
        }
        // End Add thumbnail

        $this->mLayout .= ' document.querySelector("#'.$id.'").classList.add("dropzone");
            var existingFileCount = '.$count.'; // The number of files already uploaded
            dropzone'.$id.'.options.maxFiles = dropzone'.$id.'.options.maxFiles - existingFileCount;
            </script>'
            .$hiddenInput.'
            </div>';

        $this->mLayout .= $this->mComments ? '<!-- end ' . $this->mName . " dropzone -->\n" : '';

        return true;
    }

    /**
     * Remove the given image represented by it's media id ($mediaid)
     * @param  string  $containerDropzoneId id of div container of the dropzone
     * @param  string  $page                name of page
     * @param  integer $pageId              id of page
     * @param  string  $block               name of block
     * @param  integer $blockCounter        if of block
     * @param  string  $fileId              type of media
     * @param  integer $maxFiles            number max of image for gallery
     * @param  integer $fieldName           name of field image in innomedia_blocks
     * @param  integer $mediaId             id of media
     * @param  string  $mediaName           name of media
     * @param  string  $paramPrefix         field used to create the parameter that will passed to the blockmanager's 
     *                                      parameter management system
     * @return XajaxResponse                return a ajax object
     */
    public static function ajaxRemoveMedia($containerDropzoneId, $page, $pageId, $block, $blockCounter, $fileId, $maxFiles, $fieldName, $mediaId, $mediaName, $paramPrefix='')
    {

        // Delete image from Innomedia_media
        $image = new \Innomedia\Media($mediaId);
        $image->retrieve();
        $image->delete($fieldName);
                
        // Update widget Dropzone
        $objResponse = new XajaxResponse();

        list($pageModule, $pageName) = explode("/", $page);
        list($blockModule, $blockName) = explode("/", $block);

        $xml = '<dropzone><args>
                  <maxfiles>'.$maxFiles.'</maxfiles>
                  <pagemodule>'.$pageModule.'</pagemodule>
                  <pagename>'.$pageName.'</pagename>
                  <pageid>'.$pageId.'</pageid>
                  <blockmodule>'.$blockModule.'</blockmodule>
                  <blockname>'.$blockName.'</blockname>
                  <blockcounter>'.$blockCounter.'</blockcounter>
                  <fileid>'.$fileId.'</fileid>
                  <fieldname>'.$fieldName.'</fieldname>
                  <paramprefix>'.$paramPrefix.'</paramprefix>
                </args></dropzone>';

        $html = WuiXml::getContentFromXml('', $xml);
        
        $objResponse->addAssign($containerDropzoneId, "innerHTML", $html);

        return $objResponse;
    }
}

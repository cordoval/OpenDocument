<?php
/**
* PEAR OpenDocument package
* 
* PHP version 5
*
* @category File_Formats
* @package  OpenDocument
* @author   Christian Weiske <cweiske@php.net>
* @license  http://www.gnu.org/copyleft/lesser.html  Lesser General Public License 2.1
* @version  CVS: $Id$
* @link     http://pear.php.net/package/OpenDocument
* @since    File available since Release 0.2.0
*/

require_once 'OpenDocument/Manifest.php';
require_once 'OpenDocument/Storage.php';

/**
 * Zip storage - the default OpenDocument Packages storage.
 * Creates one zip file containing several XML files.
 *
 * @category File_Formats
 * @package  OpenDocument
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.gnu.org/copyleft/lesser.html Lesser General Public License 2.1
 * @link     http://pear.php.net/package/OpenDocument
 */
class OpenDocumentPackage_Storage_Zip implements OpenDocumentPackage_Storage {

    /**
     * File name to store file as
     *
     * @var string
     */
    protected $file = null;

    /**
     * Zip document
     *
     * @var ZipArchive
     */
    protected $zip = null;

    /**
     * DOM document containing the manifest data
     *
     * @var DOMDocument
     */
    protected $manifestDom = null;
	
    protected $mimetype;

    /**
     * Opens the given file, loading the XML into memory
     *
     * @param string $file Path of the file to open.
     *
     * @return void
     *
     * @throws OpenDocument_Exception In case loading the file
     *                                did not work or the file
     *                                does not exist.
     *
     * @see create()
     */
    public function open($file)
    {
        $this->checkReadability($file);
        $this->checkWritability($file);

        $this->loadFile($file);
    }//public function open(..)



    /**
     * Checks if the given file is writable
     *
     * @param string $file Path of file
     *
     * @return void
     *
     * @throws OpenDocument_Exception In case the file is not writable
     */
    public function checkWritability($file)
    {
        if (is_writable($file)) {
            return;
        }
        throw new OpenDocument_Exception('File is not writable: ' . $file);
    }



    /**
     * Checks if the given file is readable
     *
     * @param string $file Path of file
     *
     * @return void
     *
     * @throws OpenDocument_Exception In case the file is not readable
     */
    public function checkReadability($file)
    {
        if (is_readable($file)) {
            return;
        }
        throw new OpenDocument_Exception('File is not readable: ' . $file);
    }

    /**
     * Loads content of the given file.
     *
     * Sets $this->file to $file.
     * One needs to make sure the file is readable before calling
     * this method.
     *
     * @param string $file Filename
     *
     * @return void
     *
     * @throws OpenDocument_Exception When the file is corrupt or
     *                                does not exist.
     */
	protected function loadFile($file)
    {
        $this->zip = new ZipArchive();
        
        if ($this->zip->open($file) !== true) {
            throw new OpenDocument_Exception('Cannot open ZIP file: ' . $file);
        }
        $this->manifestDom     = $this->loadDomFromZip($this->zip, 'META-INF/manifest.xml');
        
        // load from included 'mimetype' file contents.
        // may need to load from manifest file
        $file = 'mimetype';
        $index = $this->zip->locateName($file);
        if ($index === false) {
            throw new OpenDocument_Exception('File not found in zip: ' . $file);
        }
        $this->mimetype = $this->zip->getFromIndex($index);
    }

    /**
     * Loads the DOM document of the given file name from the zip archive
     *
     * @param ZipArchive $zip  Opened ZIP file object
     * @param string     $file Relative path of file to load from zip
     *
     * @return DOMDocument Document of XML file
     *
     * @throws OpenDocument_Exception In case the file does not exist in 
     *                                the zip.
     */
    protected function loadDomFromZip(ZipArchive $zip, $file)
    {
        $index = $zip->locateName($file);
        if ($index === false) {
            throw new OpenDocument_Exception('File not found in zip: ' . $file);
        }

        $dom = new DOMDocument();
        $dom->loadXML($zip->getFromIndex($index));

        return $dom;
    }



    /**
     * Returns the MIME type of the opened file.
     *
     * @return string MIME Type.
     */
    public function getMimeType()
    {
        return $this->mimetype;
    }

    /**
     * Returns the DOM object containing the meta data.
     *
     * @return DOMDocument
     */
    public function getManifestDom()
    {
        return $this->manifestDom;
    }
    

    // FIXME : save()

    /**
     * Saves the file as the given file name.
     *
     * @param string $file Path of the file to save.
     *
     * @return void
     *
     * @throws OpenDocument_Exception In case saving the file
     *                                did not work.
     *
     * @see create()
     * @see open()
     */
    public function save($file = null)
    {
        if ($file === null) {
            $file = $this->file;
        }
        if ($file === null) {
            throw new OpenDocument_Exception(
                'No file name given for saving'
            );
        }

        $zip = new ZipArchive();
        $res = $zip->open($file, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
        if ($res !== true) {
            //FIXME: find a better way to pass on the zip error code
            throw new OpenDocument_Exception(
               'Failed to open zip file for saving: ' . $file,
               $res
            );
        }
        //as soon as ZipArchive exposes compression options,
        // FIXME this and make it uncompressed
        $mimetype = $this->getMimeType();
        $zip->addFromString('mimetype', $mimetype);

        $manifest = new OpenDocument_Manifest();
        $manifest->addMimeType($mimetype);

        $this->saveContents($manifest, $zip);
        
        $zip->addFromString('META-INF/manifest.xml', (string)$manifest);

        $zip->close();
    }//public function save(..)
    
    protected function saveContents(& $manifest, & $zip) {
        // FIXME do nothing more for the moment : this class standalone works in read only
        
    }

    

    /**
     * Adds a file to the document.
     * Returns the file name that has to be used to reference
     * the file in the document content.
     *
     * @param string $path     File path
     * @param string $mimetype MIME type of the file. Leave it null
     *                         for auto detection.
     *
     * @return string Relative filename that has to be used to
     *                reference the file in content.
     *
     * @see removeFile()
     */
    public function addFile($path, $mimetype = null)
    {
        throw new OpenDocument_Exception('Adding files not supported yet');
    }



    /**
     * Removes an already added file from the document.
     *
     * @param string $relpath Relative path that was returned
     *                        by addFile()
     *
     * @return void
     *
     * @see addFile()
     */
    public function removeFile($relpath)
    {
        throw new OpenDocument_Exception('Removing files not supported yet');
    }

    
}

/**
 * Zip storage - the default OpenDocument storage.
 * Creates one zip file containing several XML files.
 *
 * @category File_Formats
 * @package  OpenDocument
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.gnu.org/copyleft/lesser.html Lesser General Public License 2.1
 * @link     http://pear.php.net/package/OpenDocument
 */
class OpenDocument_Storage_Zip extends OpenDocumentPackage_Storage_Zip implements OpenDocument_Storage
{
    /**
     * DOM document containing the content
     *
     * @var DOMDocument
     */
    protected $contentDom = null;

    /**
     * DOM document containing the meta data
     *
     * @var DOMDocument
     */
    protected $metaDom = null;

    /**
     * DOM document containing the settings
     *
     * @var DOMDocument
     */
    protected $settingsDom = null;

    /**
     * DOM document containing the styles
     *
     * @var DOMDocument
     */
    protected $stylesDom = null;

    /**
     * Creates a new file.
     * The file name may be passed, but can be omitted if the
     * final storage location is not known yet.
     *
     * Storage drivers may choose to create temporary files or
     * directories in case no file name is given here.
     *
     * @param string $type Document type ('text', 'spreadsheet')
     * @param string $file Name of the file to be created
     *
     * @return void
     *
     * @throws OpenDocument_Exception In case creating the given file
     *                                is not possible.
     */
    public function create($type, $file = null)
    {
        if ($file !== null) {
            $this->checkWritability($file);
        }

        //load file content
        $this->loadFile(self::getTemplateFile($type));

        //reset file name to our new file to prevent overwriting the template
        $this->file = $file;
    }//public function create(..)

    /**
     * Loads content of the given file.
     *
     * Sets $this->file to $file.
     * One needs to make sure the file is readable before calling
     * this method.
     *
     * @param string $file Filename
     *
     * @return void
     *
     * @throws OpenDocument_Exception When the file is corrupt or
     *                                does not exist.
     */
    protected function loadFile($file)
    {
        parent::loadFile($file);
        
        $this->contentDom  = $this->loadDomFromZip($this->zip, 'content.xml');
        $this->metaDom     = $this->loadDomFromZip($this->zip, 'meta.xml');
        $this->settingsDom = $this->loadDomFromZip($this->zip, 'settings.xml');
        $this->stylesDom   = $this->loadDomFromZip($this->zip, 'styles.xml');
        //FIXME: what to do with embedded files (e.g. images)?
    }

    /**
     * Returns the DOM object containing the content.
     *
     * @return DOMDocument
     */
    public function getContentDom()
    {
        return $this->contentDom;
    }

    /**
     * Returns the DOM object containing the meta data.
     *
     * @return DOMDocument
     */
    public function getMetaDom()
    {
        return $this->metaDom;
    }

    /**
     * Returns the DOM object containing the settings.
     *
     * @return DOMDocument
     */
    public function getSettingsDom()
    {
        return $this->settingsDom;
    }

    /**
     * Returns the DOM object containing the styles.
     *
     * @return DOMDocument
     */
    public function getStylesDom()
    {
        return $this->stylesDom;
    }

    /**
     * Saves the file as the given file name.
     *
     * @param string $file Path of the file to save.
     *
     * @return void
     *
     * @throws OpenDocument_Exception In case saving the file
     *                                did not work.
     *
     * @see create()
     * @see open()
     */
    protected function saveContents(& $manifest, & $zip) {
        
        parent::saveContents($manifest, $zip);
        
        $manifest->addFile('content.xml', 'text/xml');
        $zip->addFromString('content.xml', $this->contentDom->saveXML());

        $manifest->addFile('meta.xml', 'text/xml');
        $zip->addFromString('meta.xml', $this->metaDom->saveXML());

        $manifest->addFile('settings.xml', 'text/xml');
        $zip->addFromString('settings.xml', $this->settingsDom->saveXML());

        $manifest->addFile('styles.xml', 'text/xml');
        $zip->addFromString('styles.xml', $this->stylesDom->saveXML());

        //FIXME: add image files added with addFile()

    }//public function saveContents(..)


    /**
     * Returns the MIME type of the opened file.
     *
     * @return string MIME Type.
     */
    public function getMimeType()
    {
        return $this->getMimeTypeFromContent($this->contentDom);
    }
    
    /**
     * Extracts the textual MIME type from the content DOM object
     *
     * @param DOMDocument $content DOM object of content
     *
     * @return string MIME type
     */
    protected function getMimeTypeFromContent(DOMDocument $content)
    {
        //FIXME: read root mime type attribute from dom
        return 'application/vnd.oasis.opendocument.text';
    }//protected function getMimeTypeFromContent(..)



    /**
     * Sets the DOM object containing the content.
     * <office:document-content>
     *
     * @param DOMDocument $content Content object
     *
     * @return void
     */
    public function setContentDom(DOMDocument $content)
    {
        $this->contentDom = $content;
    }



    /**
     * Sets the DOM object containing the meta data.
     * <office:document-meta>
     *
     * @param DOMDocument $meta Meta object
     *
     * @return void
     */
    public function setMetaDom(DOMDocument $meta)
    {
        $this->metaDom = $meta;
    }



    /**
     * Sets the DOM object containing the settings.
     * <office:document-settings>
     *
     * @param DOMDocument $settings Settings object
     *
     * @return void
     */
    public function setSettingsDom(DOMDocument $settings)
    {
        $this->settingsDom = $settings;
    }



    /**
     * Sets the DOM object containing the styles.
     * <office:document-styles>
     *
     * @param DOMDocument $styles Styles object
     *
     * @return void
     */
    public function setStylesDom(DOMDocument $styles)
    {
        $this->stylesDom = $styles;
    }



    /**
     * Returns the path of a template file for the given file type.
     *
     * @param string $type File type ('text', 'spreadsheet')
     *
     * @return string Path of the file, null if there is no file for it
     */
    public static function getTemplateFile($type)
    {
        $file = null;
        switch ($type) {
        case 'text':
            $file = 'default.odt';
            break;
        case 'spreadsheet':
            $file = 'default.ods';
            break;
        }

        if (!$file) {
            return null;
        }

        if ('@data_dir@' == '@' . 'data_dir@') {
            $path = dirname(__FILE__) . '/../../data/templates/' . $file;
        } else {
            include_once "PEAR/Config.php";
            $path = PEAR_Config::singleton()->get('data_dir')
                . '/OpenDocument/data/templates/' . $file;
        }

        return $path;
    }



    /**
     * Imports data from another storage object
     *
     * @param OpenDocument_Storage $storage Storage object
     *
     * @return void
     *
     * @throws OpenDocument_Exception In case something goes wrong
     */
    public function import(OpenDocument_Storage $storage)
    {
        $this->setContentDom($storage->getContentDom());
        $this->setMetaDom($storage->getMetaDom());
        $this->setSettingsDom($storage->getSettingsDom());
        $this->setStylesDom($storage->getStylesDom());
        //FIXME: files
        //FIXME: mime type
    }
}
    /*
	public function loadContentsFromZip($filepath)
    {
        $index = $this->zip->locateName($filepath);
        if ($index === false) {
            throw new OpenDocument_Exception('File not found in zip: ' . $filepath);
        }

        return $this->zip->getFromIndex($index);
    }
*/

?>

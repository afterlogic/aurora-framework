<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * CFileStorageItem class summary
 * 
 * @property string $Id
 * @property int $Type
 * @property string $TypeStr
 * @property string $Path
 * @property string $FullPath
 * @property string $Name
 * @property int $Size
 * @property bool $IsFolder
 * @property bool $IsLink
 * @property int $LinkType
 * @property string $LinkUrl
 * @property bool $LastModified
 * @property string $ContentType
 * @property bool $Thumb
 * @property bool $Iframed
 * @property string $ThumbnailLink
 * @property string $OembedHtml
 * @property string $Hash
 * @property bool $Shared
 * @property string $Owner
 * @property string $Content
 * @property bool $IsExternal
 * 
 * @package FileStorage
 * @subpackage Classes
 */
class CFileStorageItem  extends api_AContainer
{
	public function __construct()
	{
		parent::__construct(get_class($this));

		$this->SetDefaults(array(
			'Id' => '',
			'Type' => \EFileStorageType::Personal,
			'TypeStr' => \EFileStorageTypeStr::Personal,
			'Path' => '',
			'FullPath' => '',
			'Name' => '',
			'Size' => 0,
			'IsFolder' => false,
			'IsLink' => false,
			'LinkType' => EFileStorageLinkType::Unknown,
			'LinkUrl' => '',
			'LastModified' => 0,
			'ContentType' => '',
			'Thumb' => false,
			'Iframed' => false,
			'ThumbnailLink' => '',
			'OembedHtml' => '',
			'Hash' => '',
			'Shared' => false,
			'Owner' => '',
			'Content' => '',
			'IsExternal' => false
		));
	}

	/**
	 * @return array
	 */
	public function getMap()
	{
		return self::getStaticMap();
	}

	/**
	 * @return array
	 */
	public static function getStaticMap()
	{
		return array(
			'Id' => array('string'),
			'Type' => array('int'),
			'TypeStr' => array('string'),
			'FullPath' => array('string'),
			'Path' => array('string'),
			'Name' => array('string'),
			'Size' => array('int'),
			'IsFolder' => array('bool'),
			'IsLink' => array('bool'),
			'LinkType' => array('int'),
			'LinkUrl' => array('string'),
			'LastModified' => array('int'),
			'ContentType' => array('string'),
			'Thumb' => array('bool'),
			'Iframed' => array('bool'),
			'ThumbnailLink' => array('string'),
			'OembedHtml' => array('string'),
			'Hash' => array('string'),
			'Shared' => array('bool'),
			'Owner' => array('string'),		
			'Content' => array('string'),
			'IsExternal' => array('bool')
		);
	}
	
	public function toResponseArray()
	{
		return array(
			'Id' => $this->Id,
			'Type' => $this->TypeStr,
			'Path' => $this->Path,
			'FullPath' => $this->FullPath,
			'Name' => $this->Name,
			'Size' => $this->Size,
			'IsFolder' => $this->IsFolder,
			'IsLink' => $this->IsLink,
			'LinkType' => $this->LinkType,
			'LinkUrl' => $this->LinkUrl,
			'LastModified' => $this->LastModified,
			'ContentType' => $this->ContentType,
			'Iframed' => $this->Iframed,
			'Thumb' => $this->Thumb,
			'ThumbnailLink' => $this->ThumbnailLink,
			'OembedHtml' => $this->OembedHtml,
			'Hash' => $this->Hash,
			'Shared' => $this->Shared,
			'Owner' => $this->Owner,
			'Content' => $this->Content,
			'IsExternal' => $this->IsExternal
		);		
	}
}

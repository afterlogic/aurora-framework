<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * Collection for work with attachments.
 * 
 * @package Mail
 * @subpackage Classes
 */
class CApiMailAttachmentCollection extends \MailSo\Base\Collection
{
	/**
	 * @return void
	 */
	protected function __construct()
	{
		parent::__construct();
	}

	/**
	 * Creates new instance of the object.
	 * 
	 * @return CApiMailAttachmentCollection
	 */
	public static function createInstance()
	{
		return new self();
	}

	/**
	 * Returns count of inline attachments in collection.
	 * 
	 * @param bool $bCheckContentID = false. If **true** excludes attachments without content id.
	 * 
	 * @return int
	 */
	public function getInlineCount($bCheckContentID = false)
	{
		$aList = $this->FilterList(function ($oAttachment) use ($bCheckContentID) {
			return $oAttachment && $oAttachment->isInline() &&
				($bCheckContentID ? ($oAttachment->getCid() ? true : false) : true);
		});

		return is_array($aList) ? count($aList) : 0;
	}

	/**
	 * Indicates if collection includes not inline attachments.
	 * 
	 * @return bool
	 */
	public function hasNotInlineAttachments()
	{
		return 0 < $this->Count() && $this->Count() > $this->getInlineCount(true);
	}

	/**
	 * Indicates if collection includes at least one vcard attachment.
	 * 
	 * @return bool
	 */
	public function hasVcardAttachment()
	{
		$aList = $this->FilterList(function ($oAttachment) {
			return $oAttachment && $oAttachment->isVcard();
		});

		return is_array($aList) && 0 < count($aList);
	}

	/**
	 * Indicates if collection includes at least one ical attachment.
	 * 
	 * @return bool
	 */
	public function hasIcalAttachment()
	{
		$aList = $this->FilterList(function ($oAttachment) {
			return $oAttachment && $oAttachment->isIcal();
		});

		return is_array($aList) && 0 < count($aList);
	}
}
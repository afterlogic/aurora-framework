<?php

/* -AFTERLOGIC LICENSE HEADER- */

namespace Core;

/**
 * @category Core
 */
abstract class ActionsBase
{
	/**
	 * @var \MailSo\Base\Http
	 */
	protected $oHttp;

	/**
	 * @var array
	 */
	protected $aCurrentActionParams = array();

	/**
	 * @var \CApiContactsMainManager
	 */
	private $oApiContacts = null;

	/**
	 * @var \CApiGcontactsManager
	 */
	private $oApiGcontacts = null;

	/**
	 * @return \CApiEcontactsManager
	 */
	public function ApiContacts()
	{
		if (null === $this->oApiContacts)
		{
//			$this->oApiContacts = \CApi::Manager('contacts');
		}

		return $this->oApiContacts;
	}

	/**
	 * @return \CApiGcontactsManager
	 */
	public function ApiGContacts()
	{
		if (null === $this->oApiGcontacts )
		{
//			$this->oApiGcontacts  = \CApi::Manager('gcontacts');
		}

		return $this->oApiGcontacts;
	}

	/**
	 * @param \CAccount $oAccount
	 * @param string $sActionName
	 * @param mixed $mResult = false
	 *
	 * @return array
	 */
	public function DefaultResponse($oAccount, $sActionName, $mResult = false)
	{
		$sActionName = 'Ajax' === substr($sActionName, 0, 4)
			? substr($sActionName, 4) : $sActionName;

		$aResult = array('Action' => $sActionName);
		if ($oAccount instanceof \CAccount)
		{
			$aResult['AccountID'] = $oAccount->IdAccount;
		}

		$aResult['Result'] = $this->responseObject($oAccount, $mResult, $sActionName);
		$aResult['@Time'] = microtime(true) - PSEVEN_APP_START;
		return $aResult;
	}

	/**
	 * @param \CAccount $oAccount
	 * @param string $sActionName
	 *
	 * @return array
	 */
	public function TrueResponse($oAccount, $sActionName)
	{
		return $this->DefaultResponse($oAccount, $sActionName, true);
	}

	/**
	 * @param \CAccount $oAccount
	 * @param string $sActionName
	 * @param int $iErrorCode
	 * @param string $sErrorMessage
	 * @param array $aAdditionalParams = null
	 *
	 * @return array
	 */
	public function FalseResponse($oAccount, $sActionName, $iErrorCode = null, $sErrorMessage = null, $aAdditionalParams = null)
	{
		$aResponseItem = $this->DefaultResponse($oAccount, $sActionName, false);

		if (null !== $iErrorCode)
		{
			$aResponseItem['ErrorCode'] = (int) $iErrorCode;
			if (null !== $sErrorMessage)
			{
				$aResponseItem['ErrorMessage'] = null === $sErrorMessage ? '' : (string) $sErrorMessage;
			}
		}

		if (is_array($aAdditionalParams))
		{
			foreach ($aAdditionalParams as $sKey => $mValue)
			{
				$aResponseItem[$sKey] = $mValue;
			}
		}

		return $aResponseItem;
	}

	/**
	 * @param \CAccount $oAccount
	 * @param string $sActionName
	 * @param \Exception $oException
	 * @param array $aAdditionalParams = null
	 *
	 * @return array
	 */
	public function ExceptionResponse($oAccount, $sActionName, $oException, $aAdditionalParams = null)
	{
		$iErrorCode = null;
		$sErrorMessage = null;

		$bShowError = \CApi::GetConf('labs.webmail.display-server-error-information', false);

		if ($oException instanceof \Core\Exceptions\ClientException)
		{
			$iErrorCode = $oException->getCode();
			$sErrorMessage = null;
			if ($bShowError)
			{
				$sErrorMessage = $oException->getMessage();
				if (empty($sErrorMessage) || 'ClientException' === $sErrorMessage)
				{
					$sErrorMessage = null;
				}
			}
		}
		else if ($bShowError && $oException instanceof \MailSo\Imap\Exceptions\ResponseException)
		{
			$iErrorCode = \Core\Notifications::MailServerError;
			
			$oResponse = /* @var $oResponse \MailSo\Imap\Response */ $oException->GetLastResponse();
			if ($oResponse instanceof \MailSo\Imap\Response)
			{
				$sErrorMessage = $oResponse instanceof \MailSo\Imap\Response ?
					$oResponse->Tag.' '.$oResponse->StatusOrIndex.' '.$oResponse->HumanReadable : null;
			}
		}
		else
		{
			$iErrorCode = \Core\Notifications::UnknownError;
//			$sErrorMessage = $oException->getCode().' - '.$oException->getMessage();
		}

		return $this->FalseResponse($oAccount, $sActionName, $iErrorCode, $sErrorMessage, $aAdditionalParams);
	}

	/**
	 * @param \MailSo\Base\Http $oHttp
	 *
	 * @return void
	 */
	public function SetHttp($oHttp)
	{
		$this->oHttp = $oHttp;
	}

	/**
	 * @param array $aCurrentActionParams
	 *
	 * @return void
	 */
	public function SetActionParams($aCurrentActionParams)
	{
		$this->aCurrentActionParams = $aCurrentActionParams;
	}

	/**
	 * @return array
	 */
	public function GetActionParams()
	{
		return $this->aCurrentActionParams;
	}

	/**
	 * @param string $sKey
	 * @param mixed $mValue
	 *
	 * @return void
	 */
	public function setParamValue($sKey, $mValue)
	{
		$this->aCurrentActionParams[$sKey] = $mValue;
	}

	/**
	 * @param string $sKey
	 * @param mixed $mDefaul = null
	 *
	 * @return mixed
	 */
	public function getParamValue($sKey, $mDefaul = null)
	{
		return is_array($this->aCurrentActionParams) && isset($this->aCurrentActionParams[$sKey])
			? $this->aCurrentActionParams[$sKey] : $mDefaul;
	}

	/**
	 * @param string $sObjectName
	 *
	 * @return string
	 */
	protected function objectNames($sObjectName)
	{
		$aList = array(
			'CApiMailMessageCollection' => 'MessageCollection',
			'CApiMailMessage' => 'Message',
			'CApiMailFolderCollection' => 'FolderCollection',
			'CApiMailFolder' => 'Folder',
			'Email' => 'Email'
		);

		return !empty($aList[$sObjectName]) ? $aList[$sObjectName] : $sObjectName;
	}
	/**
	 * @param \CAccount $oAccount
	 * @param object $oData
	 * @param string $sParent
	 *
	 * @return array | false
	 */
	protected function objectWrapper($oAccount, $oData, $sParent, $aParameters)
	{
		$mResult = false;
		if (is_object($oData))
		{
			$aNames = explode('\\', get_class($oData));
			$sObjectName = end($aNames);

			$mResult = array(
				'@Object' => $this->objectNames($sObjectName)
			);

			if ($oData instanceof \MailSo\Base\Collection)
			{
				$mResult['@Object'] = 'Collection/'.$mResult['@Object'];
				$mResult['@Count'] = $oData->Count();
				$mResult['@Collection'] = $this->responseObject($oAccount, $oData->CloneAsArray(), $sParent, $aParameters);
			}
			else
			{
				$mResult['@Object'] = 'Object/'.$mResult['@Object'];
			}
		}

		return $mResult;
	}

	/**
	 * @param \CAccount $oAccount
	 * @param mixed $mResponse
	 * @param string $sParent
	 * @param array $aParameters = array()
	 *
	 * @return mixed
	 */
	protected function responseObject($oAccount, $mResponse, $sParent, $aParameters = array())
	{
		$mResult = $mResponse;

		if (is_object($mResponse))
		{
			$sClassName = get_class($mResponse);
			if ('CHelpdeskThread' === $sClassName)
			{
				$mResult = array_merge($this->objectWrapper($oAccount, $mResponse, $sParent, $aParameters), array(
					'IdHelpdeskThread' => $mResponse->IdHelpdeskThread,
					'ThreadHash' => $mResponse->StrHelpdeskThreadHash,
					'IdOwner' => $mResponse->IdOwner,
					'Owner' => $mResponse->Owner,
					'Type' => $mResponse->Type,
					'Subject' => $mResponse->Subject,
					'IsRead' => $mResponse->IsRead,
					'IsArchived' => $mResponse->IsArchived,
					'ItsMe' => $mResponse->ItsMe,
					'HasAttachments' => $mResponse->HasAttachments,
					'PostCount' => $mResponse->PostCount,
					'Created' => $mResponse->Created,
					'Updated' => $mResponse->Updated
				));
			}
			else if ('CHelpdeskPost' === $sClassName)
			{
				$mResult = array_merge($this->objectWrapper($oAccount, $mResponse, $sParent, $aParameters), array(
					'IdHelpdeskPost' => $mResponse->IdHelpdeskPost,
					'IdHelpdeskThread' => $mResponse->IdHelpdeskThread,
					'IdOwner' => $mResponse->IdOwner,
					'Owner' => $mResponse->Owner,
					'Attachments' => $this->responseObject($oAccount, $mResponse->Attachments, $sParent),
					'IsThreadOwner' => $mResponse->IsThreadOwner,
					'ItsMe' => $mResponse->ItsMe,
					'Type' => $mResponse->Type,
					'SystemType' => $mResponse->SystemType,
					'Text' => \MailSo\Base\HtmlUtils::ConvertPlainToHtml($mResponse->Text),
					'Created' => $mResponse->Created
				));
			}
			else if ('CHelpdeskAttachment' === $sClassName)
			{
				$iThumbnailLimit = 1024 * 1024 * 2; // 2MB

				/* @var $mResponse CHelpdeskAttachment */
				$mResult = array_merge($this->objectWrapper($oAccount, $mResponse, $sParent, $aParameters), array(
					'IdHelpdeskAttachment' => $mResponse->IdHelpdeskAttachment,
					'IdHelpdeskPost' => $mResponse->IdHelpdeskPost,
					'IdHelpdeskThread' => $mResponse->IdHelpdeskThread,
					'SizeInBytes' => $mResponse->SizeInBytes,
					'FileName' => $mResponse->FileName,
					'MimeType' => \MailSo\Base\Utils::MimeContentType($mResponse->FileName),
					'Thumb' => \CApi::GetConf('labs.allow-thumbnail', true) &&
						$mResponse->SizeInBytes < $iThumbnailLimit &&
						\api_Utils::IsGDImageMimeTypeSuppoted(
							\MailSo\Base\Utils::MimeContentType($mResponse->FileName), $mResponse->FileName),
					'Hash' => $mResponse->Hash,
					'Content' => $mResponse->Content,
					'Created' => $mResponse->Created
				));
			}
			else if ('CApiMailFolderCollection' === $sClassName)
			{
				$mResult = array_merge($this->objectWrapper($oAccount, $mResponse, $sParent, $aParameters), array(
					'Namespace' => $mResponse->GetNamespace()
				));
			}
			else if ($mResponse instanceof \MailSo\Base\Collection)
			{
				$aCollection = $mResponse->GetAsArray();
				if (150 < \count($aCollection) && $mResponse instanceof \MailSo\Mime\EmailCollection)
				{
					$aCollection = \array_slice($aCollection, 0, 150);
				}
				
				$mResult = $this->responseObject($oAccount, $aCollection, $sParent, $aParameters);
				unset($aCollection);
			}
			else if ('CSocial' === $sClassName)
			{
				$mResult = array_merge($this->objectWrapper($oAccount, $mResponse, $sParent, $aParameters), $mResponse->toArray());
			}			
	
			else
			{
				$mResult = '['.$sClassName.']';
			}
		}

		unset($mResponse);
		return $mResult;
	}
}

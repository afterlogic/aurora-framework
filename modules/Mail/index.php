<?php

class MailModule extends AApiModule
{
	public $oApiAccountsManager = null;
	
	public function init() 
	{
//		$this->oApiAccountsManager = $this->GetManager('accounts', 'db');
		
		$this->setObjectMap('CUser', array(
				'MailsPerPage'	=> array('int', '20'),
				'UseThreads'						=> array('bool', true), //'use_threads'),
				'SaveRepliedMessagesToCurrentFolder' => array('bool', false), //'save_replied_messages_to_current_folder'),
			)
		);
	}
}

return new MailModule('1.0');

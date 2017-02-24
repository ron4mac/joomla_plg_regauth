<?php
/**
* @version		$Id: regauth.php
* @package		Registration Authorization
* @copyright	Copyright (C) 2012 Ron Crans. All rights reserved.
* @license		GNU/GPL
* Registration Authorization is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die;

class plgUserRegAuth extends JPlugin
{

	function plgUserRegAuth(&$subject, $config) {
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	// here we insert an 'authorization' field into the registration form
	function onContentPrepareForm($form, $data)
	{

		if (!($form instanceof JForm))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');
			return false;
		}

		// Check we are manipulating the correct form.
		$name = $form->getName();
		if (!in_array($name, array('com_users.registration'))) {
			return true;
		}

		// Add the authorization field to the form.
		JForm::addFormPath(dirname(__FILE__).'/authform');
		$form->loadFile('authform', false);

		return true;
	}

	// here we check that the correct authorization value was entered
	function onUserBeforeSave($user, $isnew, $new)
	{
		$app = JFactory::getApplication();

		if(!$isnew || $app->isAdmin()) return;

		$authCode = $this->params->get('authcode','@oH*_,G');
		$jform = JRequest::getVar('jform', array());
		if ($jform['authcode'] !== $authCode) {
			throw new Exception(JText::_('INVALID_AUTHORIZATION_CODE'));
			return false;
		}

		return true;
	}

	// here we set some user default settings
	function onContentPrepareData($context, $data)
	{
		if ($context == 'com_users.registration')
			$data->params = array('timezone'=>'America/New_York');
		return true;
	}

}

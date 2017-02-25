<?php
/*
* @package    Registration Authorization User Plugin
* @copyright  (C) 2016 RJCreations. All rights reserved.
* @license    GNU General Public License version 3 or later; see LICENSE.txt
*/
defined('_JEXEC') or die;

class plgUserRegAuth extends JPlugin
{

	function plgUserRegAuth (&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	// here we insert an 'authorization' field into the registration form
	function onContentPrepareForm ($form, $data)
	{

		if (!($form instanceof JForm)) {
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
	function onUserBeforeSave ($user, $isnew, $new)
	{
		$app = JFactory::getApplication();

		if (!$isnew || $app->isAdmin()) return true;

		$authCode = $this->params->get('authcode','@oH*_,G');
		$jform = JRequest::getVar('jform', array());
		if ($jform['authcode'] !== $authCode) {
			throw new Exception(JText::_('INVALID_AUTHORIZATION_CODE'));
			return false;
		}

		return true;
	}

	// here we set some user default settings
	function onContentPrepareData ($context, $data)
	{
		if ($context == 'com_users.registration')
			$data->params = array('timezone'=>'America/New_York');
		return true;
	}

}

<?php
/*
* @package    Registration Authorization User Plugin
* @copyright  (C) 2016 RJCreations. All rights reserved.
* @license    GNU General Public License version 3 or later; see LICENSE.txt
*/
defined('_JEXEC') or die;

class plgUserRegAuth extends JPlugin
{
	protected $autoloadLanguage = true;
	protected $app;
	protected $codes = array();

	public function __construct (&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
		if (!isset($this->app)) $this->app = JFactory::getApplication();
		// get all auth code and group specifications
		for ($i=1; $i<7; $i++) {
			$code = trim($this->params->get('authcode'.$i, ''));
			if ($code) {
				$this->codes[$code] = $this->params->get('groups'.$i, null);
			}
		}
	}

	// here we insert an 'authorization' field into the registration form
	public function onContentPrepareForm ($form, $data)
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

	//	echo'<xmp>';var_dump($this->codes);debug_print_backtrace();echo'</xmp>';

		return true;
	}

	// here we check that the correct authorization value was entered
	public function onUserBeforeSave ($user, $isnew, $new)
	{
		if (!$isnew || $this->app->isAdmin()) return true;

	//	$authCode = $this->params->get('authcode','@oH*_,G');
		$jform = $this->app->input->post->get('jform', array(), 'array');
		$code = trim($jform['authcode']);
		if (!array_key_exists($code, $this->codes)) {
			throw new Exception(JText::_('INVALID_AUTHORIZATION_CODE'));
			return false;
		}

		return true;
	}

	// here we set some user default settings
	public function onContentPrepareData ($context, $data)
	{
		if ($context == 'com_users.registration') {
			$data->params = array('timezone'=>'America/New_York');
		}
		return true;
	}

	public function onUserBeforeDataValidation ($form, &$data)
	{
		if ($form->getName() == 'com_users.registration' && !empty($data['authcode'])) {
			$code = trim($data['authcode']);
			if (array_key_exists($code, $this->codes)) {
				if ($this->codes[$code]) $data['groups'] = $this->codes[$code];
			}
		//	file_put_contents('REGAUTH.LOG', print_r(array($form, $data), true), FILE_APPEND);
		}
	}

}

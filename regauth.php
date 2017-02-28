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
		// Check we are manipulating the correct form.
		$name = $form->getName();
		if (!in_array($name, array('com_users.registration'))) {
			return true;
		}

		// keep from using a cached time check value
		unset($data->sbtmck);

		// Add the authorization field to the form.
		JForm::addFormPath(dirname(__FILE__).'/authform');
		$form->loadFile('authform', false);

		// set a timecheck value to defeat rapid 'bot submissions
		$shh = JFactory::getConfig()->get('secret');
		$form->setValue('sbtmck', null, $this->encrypt(time(), $shh));

		return true;
	}

	// here we check that the form wasn't submitted too quickly (bot?)
	//	and that the correct authorization value was entered
	public function onUserBeforeSave ($user, $isnew, $new)
	{
		if (!$isnew || $this->app->isAdmin()) return true;

		$jform = $this->app->input->post->get('jform', array(), 'array');

		// check for a submission (bot?) that is too quick
		$shh = JFactory::getConfig()->get('secret');
		$sbtm = $this->decrypt($jform['sbtmck'], $shh);
		if ((time() - $sbtm) < 10) {
			throw new Exception(JText::_('PLG_USER_REGAUTH_TOOQUICK'));
			return false;
		}

		// check for a valid authoriztion code
		$code = trim($jform['authcode']);
		if (!array_key_exists($code, $this->codes)) {
			throw new Exception(JText::_('PLG_USER_REGAUTH_BADAUTH'));
			return false;
		}

		return true;
	}

	// here we can set some user default settings
	public function onContentPrepareData ($context, $data)
	{
		if ($context == 'com_users.registration') {
	//		$data->params = array('timezone'=>'America/New_York');
		}
		return true;
	}

	// if a valid authcode has been entered, inject any configured group membership
	public function onUserBeforeDataValidation ($form, &$data)
	{
		if ($form->getName() == 'com_users.registration' && !empty($data['authcode'])) {
			$code = trim($data['authcode']);
			if (array_key_exists($code, $this->codes)) {
				if ($this->codes[$code]) $data['groups'] = $this->codes[$code];
			}
		}
	}


	const METHOD = 'aes-128-ctr';

	private function encrypt ($message, $key)
	{
		$nonceSize = openssl_cipher_iv_length(self::METHOD);
		$nonce = openssl_random_pseudo_bytes($nonceSize);

		$ciphertext = openssl_encrypt(
			$message,
			self::METHOD,
			$key,
			OPENSSL_RAW_DATA,
			$nonce
		);

		return base64_encode($nonce.$ciphertext);
	}

	private function decrypt ($message, $key)
	{
		$message = base64_decode($message);
		$nonceSize = openssl_cipher_iv_length(self::METHOD);
		$nonce = mb_substr($message, 0, $nonceSize, '8bit');
		$ciphertext = mb_substr($message, $nonceSize, null, '8bit');

		$plaintext = openssl_decrypt(
			$ciphertext,
			self::METHOD,
			$key,
			OPENSSL_RAW_DATA,
			$nonce
		);

		return $plaintext;
	}

}

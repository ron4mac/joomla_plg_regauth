<?php
/**
 * @package		Registration Authorization User Plugin
 * @copyright	(C) 2016-2024 RJCreations. All rights reserved.
 * @license		GNU General Public License version 3 or later; see LICENSE.txt
 * @since		1.4.1
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;

class plgUserRegAuth extends JPlugin
{
	protected $autoloadLanguage = true;
	protected $app;
	protected $codes = [];

	public function __construct (&$subject, $config)
	{
		parent::__construct($subject, $config);

		if (!isset($this->app)) $this->app = Factory::getApplication();
		// get all auth code and group specifications
		$authcodes = $this->params->get('authcode', []);
		foreach ($authcodes as $ac) {
			$this->codes[$ac->code] = empty($ac->groups) ? null : $ac->groups;
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

		if (is_object($data)) {
			// keep from using a cached time check value
			unset($data->sbtmck);
			// quiet complaint about array value for hidden field
			$data->groups = 2;
		}

		// Add the authorization field to the form.
		Form::addFormPath(dirname(__FILE__).'/authform');
		$refer = $this->app->input->server->getRaw('HTTP_REFERER');
file_put_contents('BUG.txt',"REFERRER $refer\n",FILE_APPEND);
file_put_contents('BUG.txt','CONTENT-PREPARE '.print_r($data,true).print_r($form->getField('authicode'),true),FILE_APPEND);
		$astr = $this->app->input->get('_rga', '', 'base64');
		list($t, $authcode) = explode('||', $this->orca(base64_decode($astr)));
if ($t < time()) {
$this->app->enqueueMessage(Text::_('PLG_USER_REGAUTH_INVEXP'),'error');
return false;
}
		
		if ($authcode || strpos($refer,'_rga=') || ($data && isset($data->authicode))) {
			$form->loadFile('authiform', true);
			$form->setValue('authicode', null, $authcode);
			$form->setValue('authcode', null, $authcode);
		} else {
			$form->loadFile('authform', true);
			$form->setValue('authcode', null, '');
		}

		// set a timecheck value to defeat rapid 'bot submissions
		$shh = Factory::getConfig()->get('secret');
		$form->setValue('sbtmck', null, $this->encrypt(time(), $shh));

		return true;
	}


	// here we check that the form wasn't submitted too quickly (bot?)
	//	and that the correct authorization value was entered
	public function onUserBeforeSave ($user, $isnew, $new)
	{
		if (!$isnew || $this->app->isClient('administrator')) return true;

		$jform = $this->app->input->post->get('jform', [], 'array');

		// check for a submission (bot?) that is too quick
		$shh = Factory::getConfig()->get('secret');
		$sbtm = $this->decrypt($jform['sbtmck'], $shh);
		if ((time() - $sbtm) < 10) {
			throw new Exception(Text::_('PLG_USER_REGAUTH_TOOQUICK'));
			return false;
		}

		// check for a valid authoriztion code
		$code = trim($jform['authcode']);
		if (!array_key_exists($code, $this->codes)) {
			throw new Exception(Text::_('PLG_USER_REGAUTH_BADAUTH'));
			return false;
		}

		return true;
	}


	// here we can set some user default settings
	public function onContentPrepareData ($context, $data)
	{
		if ($context == 'com_users.registration') {
file_put_contents('BUG.txt','PREPARE-DATA '.print_r($data,true),FILE_APPEND);
			if (!isset($data->regauth) && $this->params->get('usenote', 0))
				$this->app->enqueueMessage($this->params->get('authnote', ''),'warning');
			// flag to avoid multiple message
			$data->regauth = 1;
			$data->authcode='';
		}
		return true;
	}


	// if a valid authcode has been entered, inject any configured group membership
	public function onUserBeforeDataValidation ($form, &$data)
	{
file_put_contents('BUG.txt','BEFORE-VALIDATE '.print_r($data,true),FILE_APPEND);
		if ($form->getName() == 'com_users.registration' && (!empty($data['authcode']) || !empty($data['authicode']))) {
			if (!empty($data['authicode'])) $data['authcode'] = $data['authicode'];
			$code = trim($data['authcode']);
			if (array_key_exists($code, $this->codes)) {
				$data['groups'] = $this->codes[$code] ?: [2];
			} else $data['groups'] = [2];	// <- required to prevent failure when bad authcode
		}
	}


	// triggered by the registration form authcode validation rule
	public function onPlgRegAuthValidate ($authcode)
	{
		return array_key_exists($authcode, $this->codes);
	}


	const METHOD = 'aes-128-ctr';

	private function encrypt ($message, $key)
	{
		$nonceSize = openssl_cipher_iv_length(self::METHOD);
		$nonce = openssl_random_pseudo_bytes($nonceSize);
		$ciphertext = openssl_encrypt($message, self::METHOD, $key, OPENSSL_RAW_DATA, $nonce);
		return base64_encode($nonce.$ciphertext);
	}

	private function decrypt ($message, $key)
	{
		$message = base64_decode($message);
		$nonceSize = openssl_cipher_iv_length(self::METHOD);
		$nonce = mb_substr($message, 0, $nonceSize, '8bit');
		$ciphertext = mb_substr($message, $nonceSize, null, '8bit');
		$plaintext = openssl_decrypt($ciphertext, self::METHOD, $key, OPENSSL_RAW_DATA, $nonce);
		return $plaintext;
	}

	private function orca ($p)
	{
		$q = Factory::getConfig()->get('secret');
		$l = strlen($q);
		$r = '';
		while ($p) {
			$r .= substr($p, 0, $l) ^ substr($q, 0, strlen($p));
			$p = substr($p, $l);
		}
		return $r;
	}


}

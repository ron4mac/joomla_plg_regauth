<?php
/**
* @package		plg_user_regauth
* @copyright	Copyright (C) 2022-2025 RJCreations. All rights reserved.
* @license		GNU General Public License version 3 or later; see LICENSE.txt
* @since		1.5.2
*/
namespace RJCreations\Plugin\User\Regauth\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;

final class Regauth extends CMSPlugin
{
	protected $autoloadLanguage = true;
	protected $app;
	protected $secret;
	protected $codes = [];

	public function __construct (&$subject, $config)
	{
		parent::__construct($subject, $config);

		if (!isset($this->app)) $this->app = Factory::getApplication();
		// get Joomla instance secret
		$this->secret = Factory::getConfig()->get('secret');
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
			//var_dump($data);
			// keep from using a cached time check value
			unset($data->sbtmck);
			// and authcode
			unset($data->authcode);
			// quiet complaint about array value for hidden field
			$data->groups = 2;
			// may be recycled
			$astr = $data->invite??null;
		}

		$refer = $this->app->input->server->getRaw('HTTP_REFERER');
		if (!isset($astr)) $astr = $this->app->input->get('_rga', '', 'base64');
		$rsvp = (bool)$astr;
		$authcode = '';
		// see if there is a valid invitation
		if ($rsvp) $rsvp = $this->validInvite($astr,$authcode);
		// Add the authorization field to the form.
		Form::addFormPath(dirname(dirname(dirname(__FILE__))).'/authform');
		if ($rsvp) {
			$form->loadFile('authiform', true);
			$form->setValue('authicode', null, $authcode);
			$form->setValue('authcode', null, $authcode);
			$form->setValue('invite', null, $astr);
		} else {
			$form->loadFile('authform', true);
			$form->setValue('authcode', null, '');
		}

		if (($refer && strpos($refer??'','registration')===false) && !$astr) $this->app->enqueueMessage($this->params->get('authnote', ''),'warning');

		// set a timecheck value to defeat rapid 'bot submissions
		$form->setValue('sbtmck', null, base64_encode($this->orca(bin2hex(time()))));

		return true;
	}


	// here we check that the form wasn't submitted too quickly (bot?)
	//	and that the correct authorization value was entered
	public function onUserBeforeSave ($user, $isnew, $new)
	{
		if (!$isnew || $this->app->isClient('administrator')) return true;

		$jform = $this->app->input->post->get('jform', [], 'array');

		// check for a submission (bot?) that is too quick
		//$sbtm = $this->decrypt($jform['sbtmck']);
		$sbtm = hex2bin($this->orca(base64_decode($jform['sbtmck'])));
		if ((time() - $sbtm) < 10) {
			throw new \Exception(Text::_('PLG_USER_REGAUTH_TOOQUICK'));
			return false;
		}

		// check for a valid authoriztion code
		$code = trim($jform['authcode']);
		if (!array_key_exists($code, $this->codes)) {
			throw new \Exception(Text::_('PLG_USER_REGAUTH_BADAUTH')." -- $code");
			return false;
		}

		// if through invitation, record the invitation useage
		if (isset($jform['invite'])) {
			$this->storeInvite($jform['invite']);
		}

		return true;
	}


	// here we can set some user default settings
	public function onContentPrepareData ($context, $data=null)
	{
		if ($context == 'com_users.registration') {
			// flag to avoid multiple message
			$data->regauth = 1;
		//	$data->authcode='';
		}
		return true;
	}


	// if a valid authcode has been entered, inject any configured group membership
	public function onUserBeforeDataValidation ($form, &$data)
	{
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

	private function storeInvite ($key)
	{
		list($t, $code, $uses) = array_pad(explode(chr(0), $this->orca(base64_decode($key))),3,'');
		$db = Factory::getContainer()->get('DatabaseDriver');
		$q = $db->getQuery(true);
		// see if it is already recorded
		$q->select('*')->from('#__regauth_invites')->where('`key`="'.$key.'"');
		$db->setQuery($q);
		$row = $db->loadAssoc();
		$q->clear();
		if (empty($row)) {
			// insert it
			$q->insert('#__regauth_invites')->columns(['`key`','uses','expires'])->values(implode(',', [$db->quote($key),$uses-1,time()+86400*60]));
			$db->setQuery($q)->execute();
		} elseif ($row['uses']>0) {
			// update it
			$q->update('#__regauth_invites')->set('uses='.--$row['uses'])->where('`key`="'.$key.'"');
			$db->setQuery($q)->execute();
		}
		
	}

	private function validInvite ($key, &$authcode)
	{
		list($t, $code, $uses) = array_pad(explode(chr(0), $this->orca(base64_decode($key))),3,'');
		if (!$uses) {
			$this->app->enqueueMessage(Text::_('PLG_USER_REGAUTH_INVINV'),'error');
			return false;
		}
		if ($t < time()) {
			$this->app->enqueueMessage(Text::_('PLG_USER_REGAUTH_INVEXP'),'error');
			return false;
		}

		$db = Factory::getContainer()->get('DatabaseDriver');
		// clear historically expired items
		$q = $db->getQuery(true)
			->delete('#__regauth_invites')
			->where('expires<'.time());
		$db->setQuery($q)->execute();
		$q->clear();
		$q->select('*')
			->from('#__regauth_invites')
			->where('`key` = "'.$key.'"');
		$db->setQuery($q);
		$row = $db->loadAssoc();
		if (empty($row) || $row['uses']>0) {
			$authcode = $code;
			return true;
		}
		$this->app->enqueueMessage(Text::_('PLG_USER_REGAUTH_INVINV'),'error');
		return false;
	}

	// simple but sufficiently effective XOR encrypt/decrypt
	private function orca ($p)
	{
		$q = $this->secret;
		$l = strlen($q);
		$r = '';
		while ($p) {
			$r .= substr($p, 0, $l) ^ substr($q, 0, strlen($p));
			$p = substr($p, $l);
		}
		return $r;
	}


}

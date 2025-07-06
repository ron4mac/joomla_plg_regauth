<?php
/**
* @package		plg_user_regauth
* @copyright	Copyright (C) 2022-2025 RJCreations. All rights reserved.
* @license		GNU General Public License version 3 or later; see LICENSE.txt
* @since		1.5.2
*/
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;

class plgUserRegauthInstallerScript extends InstallerScript
{
	protected $minimumJoomla = '4.0';
	protected $deleteFiles = ['/plugins/user/regauth/regauth.php'];

	public function install ($parent) 
	{
		$this->convertParams();
		return true;
	}

	public function uninstall ($parent) 
	{
		return true;
	}

	public function update ($parent) 
	{
		$this->convertParams();
		// get the version number being installed/updated
		if (method_exists($parent,'getManifest')) {
			$version = $parent->getManifest()->version;
		} else {
			$version = $parent->get('manifest')->version;
		}
		echo '<p>The <em>regauth</em> plugin has been updated to version' . $version . '.</p>';
		return true;
	}

	public function preflight ($type, $parent) 
	{
		return true;
	}

	public function postflight ($type, $parent) 
	{
		if ($type === 'update') {
			$this->removeFiles();
		}
		return true;
	}

	// convert any old plugin parameters to new style
	private function convertParams ()
	{
		$db = Factory::getDbo();
		$db->setQuery("SELECT params FROM #__extensions WHERE name = 'plg_user_regauth'");
		$json = $db->loadResult();
		if ($json) {
			$prms = json_decode($json, true);
			if (isset($prms['authcode1'])) {
				$authcodes = [];
				foreach (['authcode1','authcode2','authcode3','authcode4','authcode5','authcode6'] as $c) {
					if (!empty($prms[$c])) {
						$k = substr($c, -1);
						$authcodes['authcode'.$k] = ['code'=>$prms[$c]];
						if (!empty($prms['groups'.$k])) $authcodes['authcode'.$k]['groups'] = $prms['groups'.$k];
					}
				}
				$db->setQuery("UPDATE #__extensions SET params = ".$db->quote(json_encode(['authcode' => $authcodes]))." WHERE name = 'plg_user_regauth'");
				$db->query();
				Factory::getApplication()->enqueueMessage('The <em>regauth</em> plugin parameters have been upgraded to a new format. Please ensure that the plugin configuration is correct.', 'warning');
			}
		}
	}

}

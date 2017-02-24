<?php
/**
 * @package		plg_user_regauth
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

class JFormRuleRegAuthCode extends JFormRule
{
	public function test(&$element, $value, $group = null, &$input = null, &$form = null) {
		$plugin = JPluginHelper::getPlugin('user', 'regauth');
		$pParams = new JRegistry();
		$pParams->loadString($plugin->params);

		$authcode = $pParams->get('authcode');

		if ($value !== $authcode)
			return false;

		return true;
	}
}
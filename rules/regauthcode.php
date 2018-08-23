<?php
/*
* @package    Registration Authorization User Plugin
* @copyright  (C) 2016 RJCreations. All rights reserved.
* @license    GNU General Public License version 3 or later; see LICENSE.txt
*/
defined('_JEXEC') or die;

use Joomla\Registry\Registry;

class JFormRuleRegAuthCode extends JFormRule
{
	public function test (SimpleXMLElement $element, $value, $group = null, Registry $input = null, JForm $form = null)
	{
		$plugin = JPluginHelper::getPlugin('user', 'regauth');
		$pParams = new JRegistry();
		$pParams->loadString($plugin->params);

		$authcode = $pParams->get('authcode');

	//	if ($value !== $authcode)
	//		return false;

		return true;
	}

}
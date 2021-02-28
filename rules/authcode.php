<?php
/*
* @package    Registration Authorization User Plugin
* @copyright  (C) 2016-2021 RJCreations. All rights reserved.
* @license    GNU General Public License version 3 or later; see LICENSE.txt
*/
defined('_JEXEC') or die;

use Joomla\Registry\Registry;

class JFormRuleAuthcode extends JFormRule
{
	public function test(SimpleXMLElement $element, $value, $group = null, Registry $input = null, JForm $form = null)
	{
		$authcode = trim($value);
		if (!$authcode) return false;

		$params = new Registry(JPluginHelper::getPlugin('user','regauth')->params);
		$codes = [];
		for ($i=1; $i<7; $i++) {
			$code = trim($params->get('authcode'.$i, ''));
			if ($code) {
				$codes[] = $code;
			}
		}

		if (in_array($authcode, $codes)) return true;
		return false;
	}

}
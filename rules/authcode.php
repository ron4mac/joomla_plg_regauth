<?php
/**
* @package		plg_user_regauth
* @copyright	Copyright (C) 2022-2024 RJCreations. All rights reserved.
* @license		GNU General Public License version 3 or later; see LICENSE.txt
* @since		1.5.0
*/
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Form\FormRule;

class JFormRuleAuthcode extends FormRule
{
	public function test(SimpleXMLElement $element, $value, $group = null, Registry $input = null, JForm $form = null)
	{
		$authcode = trim($value);
		if (!$authcode) return false;

		$result = Factory::getApplication()->triggerEvent('onPlgRegAuthValidate', [$authcode]);
		if (is_array($result)) return $result[0];

		return false;
	}

}

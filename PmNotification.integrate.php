<?php
/**
 * PM Notifications
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.1
 */

class PmNotification_Integrate
{
	public static function menu_buttons()
	{
		global $user_info;

		if (isset($_REQUEST['api']) || isset($_REQUEST['xml']))
			return;

		if (!empty($user_info['unread_messages']))
		{
			loadJavascriptFile('//ajax.googleapis.com/ajax/libs/angularjs/1.2.23/angular.min.js');
			loadJavascriptFile('//ajax.googleapis.com/ajax/libs/angularjs/1.2.23/angular-animate.js');
			loadJavascriptFile('pm_notifications.js');
			loadCSSFile('PmNotification.css');
			loadTemplate('Pmnotification');
			loadLanguage('Pmnotification');
			Template_Layers::getInstance()->addEnd('pmnotifications');
		}
	}
}
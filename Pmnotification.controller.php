<?php
/**
 * PM Notifications
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.1
 */

class Pmnotification_Controller extends Action_Controller
{
	public function pre_dispatch()
	{
		require_once(SUBSDIR . '/Pmnotification.class.php');
		require_once(SUBSDIR . '/PmnotificationDb.class.php');
	}

	public function action_index()
	{
		$this->action_get();
	}

	public function action_get()
	{
		global $context, $user_info;

		$pm = new Pmnotification_Class(new Pmnotification_Db(database(), $user_info['id']));

		$context['json_data'] = $pm->getNew();
		loadTemplate('Json');
		$context['sub_template'] = 'send_json';
	}
}
<?php
/**
 * PM Notifications
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.1
 */

class Pmnotification_Class
{
	protected $notif_db = null;

	public function __construct($notif_db)
	{
		$this->notif_db = $notif_db;
	}

	public function getNew($amount = 5)
	{
		$data = $this->notif_db->getNew($amount);

		return $data;
	}
}
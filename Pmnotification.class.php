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
	protected $user_id = null;

	public $max_pm_recipients = 0;
	protected $pm_posts_verification = 0;
	protected $pm_posts_per_hour = 0;

	public function __construct($notif_db, $user = null)
	{
		$this->notif_db = $notif_db;
		if ($user !== null)
			$this->notif_db->setUser($user['id']);
		$this->user = $user;
	}

	public function getNew($amount = 5)
	{
		$data = $this->notif_db->getNew($amount);

		return $data;
	}

	public function tooManySent($limits)
	{
		// Extract out the spam settings - it saves database space!
		list ($this->max_pm_recipients, $this->pm_posts_verification, $this->pm_posts_per_hour) = explode(',', $limits);

		// Check whether we've gone over the limit of messages we can send per hour - fatal error if fails!
		if (!empty($this->pm_posts_per_hour) && !allowedTo(array('admin_forum', 'moderate_forum', 'send_mail')) && $this->user['mod_cache']['bq'] == '0=1' && $this->user['mod_cache']['gq'] == '0=1')
		{
			require_once(SUBSDIR . '/PersonalMessage.subs.php');

			// How many have they sent this last hour?
			$pmCount = pmCount($this->user['id'], 3600);

			if (!empty($pmCount) && $pmCount >= $modSettings['pm_posts_per_hour'])
				return true;
		}

		return false;
	}

	public function tooManyRecipients($to, $bbc)
	{
		return !empty($this->max_pm_recipients) && count($to) + count($bbc) > $this->max_pm_recipients && !allowedTo(array('moderate_forum', 'send_mail', 'admin_forum'));
	}

	public function getRecipients($id_pm)
	{
		return array_diff($this->notif_db->getRecipients($id_pm), array($this->user['id']));
	}

	public function getSubject($id_pm)
	{
		return $this->appendRe($this->notif_db->getSubject($id_pm));
	}

	protected function appendRe($subject)
	{
		// Figure out which flavor or 'Re: ' to use
		$response_prefix = response_prefix();

		// Add 'Re: ' to it....
		if (trim($response_prefix) != '' && Util::strpos($subject, trim($response_prefix)) !== 0)
			$subject = $response_prefix . $subject;

		return $subject;
	}

	public function getHead($id_pm)
	{
		return $this->notif_db->getHead($id_pm);
	}

	public function wrongVerification($post_errors)
	{
		if (!$this->user['is_admin'] && !empty($this->pm_posts_verification) && $this->user['posts'] < $this->pm_posts_verification)
		{
			require_once(SUBSDIR . '/VerificationControls.class.php');

			$verificationOptions = array(
				'id' => 'pm',
			);
			$require_verification = create_control_verification($verificationOptions, true);

			if (is_array($require_verification))
			{
				foreach ($require_verification as $error)
					$post_errors->addError($error);
			}
		}
	}

	protected function cleanRecipients($to, $bbc)
	{
		// Construct the list of recipients.
		$recipientList = array(
			'to' => array_map('intval', $to),
			'bbc' => array_map('intval', $bbc),
		);
		$namedRecipientList = array();
		$namesNotFound = array();

		foreach (array('to', 'bcc') as $recipientType)
		{
			// Make sure we don't include the same name twice
			$recipientList[$recipientType] = array_unique($recipientList[$recipientType]);
		}

		return $recipientList;
	}
}
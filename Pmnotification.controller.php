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
		$this->action_get_api();
	}

	public function action_get_api()
	{
		global $user_info;

		$pm = new Pmnotification_Class(new Pmnotification_Db(database(), $user_info['id']));

		return $this->sendResponse($pm->getNew());
	}

	public function action_send_api()
	{
		global $txt, $context, $user_info, $modSettings;

		// All the helpers we need
		require_once(SUBSDIR . '/Auth.subs.php');
		require_once(SUBSDIR . '/Post.subs.php');

		loadLanguage('PersonalMessage', '', false);
		loadLanguage('Pmnotification', '', false);

		$pm = new Pmnotification_Class(new Pmnotification_Db(database()), $user_info);
		$json = array();

		// Initialize the errors we're about to make.
		$post_errors = Error_Context::context('pm', 1);

// 		fatal_lang_error('pm_too_many_per_hour', true, array($modSettings['pm_posts_per_hour']));
		if ($pm->tooManySent($modSettings['pm_spam_settings']))
			$post_errors->addError('pm_too_many_per_hour');

		// If your session timed out, show an error, but do allow to re-submit.
		if (checkSession('post', '', false) != '')
			$post_errors->addError('session_timeout');

		// Check if there's at least one recipient.
		$id_pm = isset($_REQUEST['id_pm']) ? (int) $_REQUEST['id_pm'] : 0;
		if (empty($id_pm))
			$post_errors->addError('pmnot_wrong_pm');

		// Did they make any mistakes like no subject or message?
		$subject = $pm->getSubject($id_pm);
		if ($subject == '')
			$post_errors->addError('no_subject');

		if (!isset($_REQUEST['message']) || $_REQUEST['message'] == '')
			$post_errors->addError('no_message');
		elseif (!empty($modSettings['max_messageLength']) && Util::strlen($_REQUEST['message']) > $modSettings['max_messageLength'])
			$post_errors->addError('long_message');
		else
		{
			// Preparse the message.
			$message = $_REQUEST['message'];
			preparsecode($message);

			// Make sure there's still some content left without the tags.
			if (Util::htmltrim(strip_tags(parse_bbc(Util::htmlspecialchars($message, ENT_QUOTES), false), '<img>')) === '' && (!allowedTo('admin_forum') || strpos($message, '[html]') === false))
				$post_errors->addError('no_message');
		}

		// Wrong verification code?
		$pm->wrongVerification($post_errors);

		$recipientList = array(
			'to' => $pm->getRecipients($id_pm),
			'bcc' => array()
		);

		if ($pm->tooManyRecipients($recipientList['to'], $recipientList['bcc']))
		{
			$post_errors->addError(sprintf($txt['pm_too_many_recipients'], $pm->max_pm_recipients));
		}

		// Protect from message spamming.
		if (spamProtection('pm', false))
			$post_errors->addError('pm_WaitTime_broken');

		// If they made any errors, give them a chance to make amends.
		if ($post_errors->hasErrors())
		{
			$json['errors'] = $this->encodeErrors($post_errors);
			return $this->sendResponse($json);
		}

		// Prevent double submission of this form.
		if (!checkSubmitOnce('check', false))
			$post_errors->addError('error_form_already_submitted');

		// Finally do the actual sending of the PM.
		if (!empty($recipientList['to']) || !empty($recipientList['bcc']))
		{
			require_once(SUBSDIR . '/PersonalMessage.subs.php');
			$context['send_log'] = sendpm($recipientList, $subject, $_REQUEST['message'], true, null, $pm->getHead($id_pm));
		}
		else
			$context['send_log'] = array(
				'sent' => array(),
				'failed' => array()
			);

		// Mark the message as "replied to".
		if (!empty($context['send_log']['sent']))
		{
			require_once(SUBSDIR . '/PersonalMessage.subs.php');
			setPMRepliedStatus($user_info['id'], $id_pm);
		}

		// If one or more of the recipients were invalid, go back to the post screen with the failed usernames.
		if (!empty($context['send_log']['failed']))
		{
			$post_errors->addError($this->addFailedNamesErrors(array(
				'to' => array_intersect($recipientList['to'], $context['send_log']['failed']),
				'bcc' => array_intersect($recipientList['bcc'], $context['send_log']['failed'])
			)));
		}

		if ($post_errors->hasErrors())
			$json['errors'] = $this->encodeErrors($post_errors);

		$json['success'] = $txt['pm_sent'];

		return $this->sendResponse($json);
	}

	protected function addFailedNamesErrors($post_errors, $ids)
	{
		$allRecipients = array_merge($ids['to'], $ids['bcc']);

		require_once(SUBSDIR . '/Members.subs.php');

		// Get the latest activated member's display name.
		$result = getBasicMemberData($allRecipients);
		foreach ($result as $row)
		{
			$names[] = $row['real_name'];
		}

		return $names;
	}

	protected function encodeErrors($errors)
	{
		$enc_errors = array();
		foreach ($errors->prepareErrors() as $key => $message)
			$enc_errors[] = $message;

		return $enc_errors;
	}

	protected function sendResponse($json)
	{
		global $context;

		$context['json_data'] = $json;
		loadTemplate('Json');
		$context['sub_template'] = 'send_json';
	}
}
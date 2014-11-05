<?php
/**
 * PM Notifications
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.1
 */

class Pmnotification_Db
{
	protected $db = null;
	protected $user_id = null;

	public function __construct($db, $user = null)
	{
		$this->db = $db;
		$this->user_id = $user;
	}

	public function setUser($user)
	{
		$this->user_id = $user;
	}

	public function getNew($amount)
	{
		$request = $this->db->query('', '
			SELECT pr.id_pm, pr.labels, pr.is_read, pr.is_new, pr.deleted, pr.id_pm_head,
				pm.id_pm_head, pm.id_member_from, IFNULL(s.real_name, pm.from_name) AS from_name,
				pm.msgtime, pm.subject, pm.body, IFNULL(s.id_member, 0) AS not_guest, s.avatar,
				IFNULL(a.id_attach, 0) AS id_attach, a.filename, a.attachment_type, s.email_address
			FROM {db_prefix}pm_recipients AS pr
				LEFT JOIN {db_prefix}personal_messages AS pm ON (pr.id_pm = pm.id_pm)
				LEFT JOIN {db_prefix}members AS s ON (s.id_member = pm.id_member_from)
				LEFT JOIN {db_prefix}attachments AS a ON (a.id_member = s.id_member AND a.id_member != 0)
			WHERE pr.id_member = {int:this_member}
				AND pr.is_read = {int:is_read}
			ORDER BY pr.id_pm DESC
			LIMIT {int:limit}',
			array(
				'this_member' => $this->user_id,
				'is_read' => 0,
				'limit' => $amount,
			)
		);

		$pms = array();
		while ($row = $this->db->fetch_assoc($request))
		{
			$row['body'] = censorText($row['body']);
			$row['body'] = parse_bbc($row['body']);
			$row['avatar'] = determineAvatar($row);
			unset($row['email_address']);

			$pms[] = $row;
		}

		$this->db->free_result($request);

		return $pms;
	}

	public function getSubject($id_pm)
	{
		$request = $this->db->query('', '
			SELECT subject
			FROM {db_prefix}personal_messages
			WHERE id_pm = {int:current_pm}',
			array(
				'current_pm' => $id_pm,
			)
		);
		list ($subject) = $this->db->fetch_row($request);
		$this->db->free_result($request);

		return $subject;
	}

	public function getRecipients($id_pm)
	{
		$recipients = array();
		$request = $this->db->query('', '
			SELECT id_member_from
			FROM {db_prefix}personal_messages
			WHERE id_pm = {int:current_pm}',
			array(
				'current_pm' => $id_pm,
				'no_bcc' => 0,
			)
		);
		while ($row = $this->db->fetch_assoc($request))
			$recipients[] = $row['id_member_from'];
		$this->db->free_result($request);

		$request = $this->db->query('', '
			SELECT id_member
			FROM {db_prefix}pm_recipients
			WHERE id_pm = {int:current_pm}
				AND bcc = {int:no_bcc}',
			array(
				'current_pm' => $id_pm,
				'no_bcc' => 0,
			)
		);
		while ($row = $this->db->fetch_assoc($request))
			$recipients[] = $row['id_member'];
		$this->db->free_result($request);

		return $recipients;
	}

	public function getHead($id_pm)
	{
		$request = $this->db->query('', '
			SELECT id_pm_head
			FROM {db_prefix}personal_messages
			WHERE id_pm = {int:current_pm}',
			array(
				'current_pm' => $id_pm,
			)
		);
		list ($pm_head) = $this->db->fetch_row($request);
		$this->db->free_result($request);

		return $pm_head;
	}
}
<?php

/**
 * @author yereth
 *
 * This is the systems messaging system
 *
 * TODO: Abstract some of the contact handling to a new Contact Tool class.
 *
 */
class ConversationsModel extends CMS_Model {
	private $locations;
	
	public $actions = array('replyMessage' => false, 'deleteMessage' => false, 'newConversation' => false);
	
	public function init($args) {
		
	}
	
	/************** ACTIONS **************/
	
	public function deleteMessage($data = array()) {
		global $user;
		if (!$conversation_id = $this->arg('id')) HTTP::redirect($this->url());
		if (!$id = $this->arg('post_id') or !is_numeric($id)) {
			unset($this->model->args['post_id']);
			HTTP::redirect($this->url(false, false, true));
		}
		
		$conversation = $this->isAllowed($conversation_id);
		
		$result = $this->db()->update('conversations_replies_status', array('user_id' => $user->getUserID(), 'reply_id' => $id), array('deleted' => 1));
		//var_dump($result, $this->db()->getQuery());die();
		if ($result) unset($this->model->args['post_id']);
		return $result > 0;
	}
	
	public function replyMessage($data = array()) {
		global $user;
		
		$conversation_id = request($this->model->args['id']);
		
		if (!$message = request($data['message']) or !$message = trim(strip_tags($data['message']))) return false;
		
		return $this->addMessage($conversation_id, $user->getUserID(), $message);
	}
	
	public function newConversation($data = array()) {
		global $user, $site;
		
		if (!$user->isLoggedIn()) HTTP::disallowed();
		
		if (!$data) HTTP::redirect($this->url(array('view' => 'start')));
		
		$recipients = request($data['recipients']);
		$subject = strip_tags(trim(request($data['subject'])));
		$message = strip_tags(trim(request($data['message'])));
		
		if (!$recipients) {
			$this->logL(LOG_USER_WARNING, 'E_NO_RECIPIENTS');
			return false;
		}
		if (!$subject) {
			$this->logL(LOG_USER_WARNING, 'E_NO_SUBJECT');
			return false;
		}
		if (!$recipients) {
			$this->logL(LOG_USER_WARNING, 'E_EMPTY_MESSAGE');
			return false;
		}
		
		$recipients = explode(',', $recipients);
		foreach($recipients as $rec) {
			if (!is_numeric($rec) or ($tempUser = $site->getUser($rec) and !$tempUser->getUserID())) {
				$this->logL(LOG_USER_WARNING, 'E_INVALID_RECIPIENT');
				return false;
			}
		}
		
		array_push($recipients, $user->getUserID());
		$recipients = array_unique($recipients);
		
		if (count($recipients) < 2) {
			$this->logL(LOG_USER_WARNING, 'E_NO_RECIPIENTS');
			return false;
		}
		
		// Check if the message is double (spammer perhaps?)
		$double = $this->db()->query('conversations_replies', array('!reply_date > DATE_SUB(NOW(), INTERVAL 1 DAY)', 'user_id' => $user->getUserID(), 'message' => $message));
		if ($double) {
			// Error message? An identical message has been sent in the last
			$this->logL(LOG_USER_WARNING, 'E_DUPLICATE_MESSAGE_24_HOURS');
			return false;
		}
		
		// Find existing conversations with the same name
		$existing = $this->db()->query(
			'conversations',
			array('subject' => $subject),
			array(
				'join' => array(
					'table' => 'conversations_users',
					'on' => array('id', 'conversation_id'),
					'where' => array('user_id' => $recipients)
				), 'transpose' => 'id'
			)
		);
		
		if ($existing) {
			foreach($existing as $id) {
				$members = $this->db()->query('conversations_users', array('conversation_id' => $id), array('transpose' => 'user_id'));
				sort($recipients); sort($members);
				if ($recipients == $members) {
					$c_id = $id;
					break;
				}
			}
		}
		
		// If an existing conversation is not found
		if (!$c_id) {
			$time = unixToDatetime();
			
			$c_id = $this->db()->insert('conversations', array(
				'subject' => $subject,
				'created' => $time,
				'user_id' => $user->getUserID()
			));
			
			if (!$c_id) return;
			
			foreach($recipients as $rec) {
				$this->db()->insert('conversations_users', array(
					'conversation_id' => $c_id,
					'user_id' => $rec,
					'date_joined' => $time
				));
			}
		}
		
		if ($this->addMessage($c_id, $user->getUserID(), $message)) HTTP::redirect($this->url(array('id' => $c_id, 'name' => $subject)));
		return false;
	}
	
	public function addMessage($conversation_id, $user_id, $message) {
		$conversation = $this->isAllowed($conversation_id, $user_id);
		
		$members = $this->getMembers($conversation_id);
		
		$message = strip_tags(trim($message));
		if (!$message) {
			$this->logL(LOG_USER_WARNING, 'E_EMPTY_MESSAGE');
			return false;
		}
		
		$this->db()->transaction();
		
		$reply_id = $this->db()->insert('conversations_replies', array(
			'conversation_id' => $conversation_id,
			'reply_date' => unixToDatetime(),
			'message' => strip_tags(trim($message)),
			'user_id' => $user_id
		));
		
		if (!$reply_id) {
			$this->logL(LOG_USER_ERROR, 'E_MESSAGE_NOT_ADDED');
			return false;
		}
		
		$status = array();
		foreach($members as $member) {
			$status[] = array(
				'member_id' => $member['id'],
				'user_id' => $member['user_id'],
				'reply_id' => $reply_id,
				'read' => $member['user_id'] == $user_id ? 1 : 0
			);
		}
		if ($this->db()->insert('conversations_replies_status', $status)) {
			$this->db()->commit();
			return true;
		} else {
			$this->db()->rollback();
			$this->logL(LOG_USER_ERROR, 'E_MESSAGE_NOT_ADDED');
			return false;
		}
	}
	
	public function isAllowed($conversation_id, $user_id = false) {
		if ($user_id) $user = $GLOBALS['site']->getUser($user_id);
		else $user = $GLOBALS['user'];
		
		if (!$conversation_id or !is_numeric($conversation_id) or !$conversation = $this->db()->query('conversations', $conversation_id)) HTTP::throw404();
		
		if (!$user or !$user->isLoggedIn() or !$this->isMember($conversation_id, $user->getUserID())) HTTP::disallowed();
		
		return $conversation;
	}
	
	public function getConversation($id) {
		global $site, $user;
		
		$conversation = $this->isAllowed($id);
		
		$this->css('conversation');
		$this->js('conversation');
		
		$members = $this->getMembers($id, $user->getUserID());
		
		$replies = $this->db()->query(
			'conversations_replies',
			array('conversation_id' => $id),
			array(
				'orderBy' => 'reply_date',
				'order' => 'DESC',
				'join' => array(
					'table' => 'conversations_replies_status',
					'on' => array('id', 'reply_id'),
					'where' => array('user_id' => $user->getUserID(), 'deleted' => 0),
					'fields' => array('read')
				)
			)
		);
		
		// Mark messages as read
		$update = array();
		foreach($replies as $reply) if ($reply['read'] == 0) $update[] = $reply['id'];
		if ($update) $result = $this->db()->update('conversations_replies_status', array('user_id' => $user->getUserID(), 'reply_id' => $update), array('read' => 1));
		
		/*
		$options = sprintf('<a title="%s" class="tooltip" href="%s"><span class="icon icon-flag"> </span><span class="tooltip-text">%s</span></a>',
			$this->lang('REPORT_POST', false, false, true),
			$this->url(array('action' => 'reportMessage', 'post_id' => '_post_id'), false, true),
			$this->lang('REPORT_POST')
		);*/
		$options .= sprintf(
			'<a title="%s" class="tooltip" href="%s"><span class="icon icon-trash"> </span><span class="tooltip-text">%s</span></a>',
			$this->lang('DELETE_POST', false, false, true),
			$this->url(array('action' => 'deleteMessage', 'post_id' => '_post_id'), false, true),
			$this->lang('DELETE_POST')
		);
		
		$latest = $replies[0]['reply_date'];
		$html = '';
		foreach($replies as $reply) {
			$html .= sprintf('
				<div class="reply%s%s">
					%s
					%s
					<div class="controls">
						%s
					</div>
					<div class="body">
						%s
					</div>
				</div>
				',
				$reply['read'] ? ' read' : ' unread',
				$user->getUserID() == $reply['user_id'] ? ' myMessage' : '',
				$site->getUser($reply['user_id'])->getLinkedNickname(),
				$this->tool('datetime')->getDateTag($reply['reply_date'], false, false, true),
				str_replace('_post_id', $reply['id'], $options),
				ContentCleaner::linkURLs(ContentCleaner::PWrap($reply['message']))
			);
		}
		
		$userList = array();
		foreach($members as $user_id => $members) {
			$userClass = $site->getUser($user_id);
			$userList[] = $userClass ? $userClass->getLinkedNickname() : '[User not found]';
		}
		
		$form = FormHelper::open($this->url(array('action' => 'replyMessage'), false, true), array('class' => 'prettyForm', 'id' => 'replyForm'));
		$f = array();
		$f[] = FormHelper::textarea(false, 'message', false, array('placeholder' => $this->lang('INPUT_MESSAGE_PLACEHOLDER', false, false, true), 'mandatory' => true));
		$f[] = FormHelper::submit($this->lang('SUBMIT_SEND', false, false, true));
		$form .= FormHelper::fieldset('', $f);
		$form .= FormHelper::close();
		
		$messages = sprintf('
			<h1 class="dark"><a href="%s">%s</a></h1>
			<section id="conversation">
				<header id="contentSectionHeader">
					<h1>"%s"</h1>
					<div id="conversationMembers">%s: %s</div>
					%s
				</header>
				%s
				<div class="clearfix" id="conversationReplies">
					%s
				</div>
			</section>
			',
			$this->url(),
			$this->lang('PRIVATE_MESSAGES'),
			$conversation['subject'],
			$this->lang('WITH_MEMBERS'),
			implode(', ', $userList),
			$this->tool('datetime')->getDateTag($latest, 'latestPostDate', false, true),
			$form,
			$html
		);
		//$messages .= '<span class="icon icon-trash"> </span><span class="icon icon-flag"> </span>';
		
		return $messages;
	}
	
	public function _start($data = false) {
		global $user;
		
		$this->tool('html')->addJS($this->model->urls['root'] . '/js/jquery/jquery.tokeninput.js');
		$this->tool('html')->addCSS('tokenInputCSS', $this->model->urls['root'] . '/css/plugins/token-input-facebook.css');
		$this->js('newmessage');
		
		$form = FormHelper::open($this->url(array('action' => 'newConversation'), false, true), array('class' => 'prettyForm', 'id' => 'newConversationForm'));
		$f = array();
		$f[] = FormHelper::input(false, 'recipients', request($data['recipients']), array('placeholder' => $this->lang('INPUT_RECIPIENTS_PLACEHOLDER', false, false, true), 'mandatory' => true));
		$f[] = FormHelper::input(false, 'subject', request($data['subject']), array('placeholder' => $this->lang('INPUT_SUBJECT_PLACEHOLDER', false, false, true), 'mandatory' => true));
		$f[] = FormHelper::textarea(false, 'message', request($data['message']), array('placeholder' => $this->lang('INPUT_MESSAGE_PLACEHOLDER', false, false, true), 'mandatory' => true));
		$f[] = FormHelper::submit($this->lang('SUBMIT_SEND', false, false, true));
		$form .= FormHelper::fieldset('', $f);
		$form .= FormHelper::close();
		
		$content = sprintf('
			<h1 class="dark"><a href="%s">%s</a></h1>
			%s
			<section id="newConversation">
				<header id="contentSectionHeader">
					<h1>%s</h1>
				</header>
				<div id="newMessageFormWrapper">
					%s
				</div>
				',
			$this->url(),
			$this->lang('PRIVATE_MESSAGES'),
			$this->tool('log')->sprintLog(),
			$this->lang('NEW_CONVERSATION'),
			$form
		);
		$content .= '</section>';
		
		return $content;
	}
	
	public function getContent($options = array()) {
		global $user, $site;
		
		if (!$user->isLoggedIn()) HTTP::disallowed();
		
		if ($id = request($this->model->args['id'])) return $this->getConversation($id);
		
		$pager = new Pager;
		
		$conversations = $this->getConversations($pager, $user->getUserID());
		
		$messages = sprintf('
				<h1 class="dark"><a href="%s">%s</a></h1>
				<div id="controls">
					<a class="button" href="%s"><span class="icon icon-edit"> </span> %s</a>
				</div>
				<section id="conversations">',
				$this->url(),
				$this->lang('PRIVATE_MESSAGES'),
				$this->url(array('view' => 'start')),
				$this->lang('NEW_CONVERSATION'),
				$this->lang('CONVERSATIONS')
		);
		if ($conversations) {
			$messages .= '<div class="itemList messagesList">';
			foreach($conversations as $data) {
				$members = $this->getMembers($data['id'], $user->getUserID());
				$latest = $this->db()->query(
					'conversations_replies',
					array('conversation_id' => $data['id']),
					array(
						'orderBy' => 'reply_date',
						'order' => 'DESC',
						'singleResult' => true,
						'join' => array(
							'table' => 'conversations_replies_status',
							'on' => array('id', 'reply_id'),
							'where' => array('user_id' => $user->getUserID(), 'deleted' => 0)
						)
					)
				);
				
				$userList = array();
				foreach($members as $user_id => $members) {
					$userClass = $site->getUser($user_id);
					$userList[] = $userClass ? $userClass->getNickname() : '[User not found]';
				}
				$messages .= sprintf(
					'<article><a class="item %s" href="%s">
						<span class="icon icon-%s"> </span>
						%s
						<h1>%s</h1>
						<h2>%s <span class="messageCount">(%d %s)</span></h2>
						<span class="icon replyIcon icon-arrow-%s"> </span><p>%s</p>
					</a></article>',
					$data['isRead'] ? 'read' : 'unread',
					$this->url(array('id' => $data['id'], 'name' => $data['subject'])),
					$data['isRead'] ? 'envelope-2' : 'envelope-3',
					$this->tool('datetime')->getDateTag($data['reply_date'], false, true),
					implode(', ', $userList),
					$data['subject'],
					$data['count'],
					$this->lang($data['count'] == 1 ? 'MESSAGE': 'MESSAGES'),
					$latest['user_id'] == $user->getUserID() ? 'right' : 'left',
					summarizeWords($latest['message'])
				);
			}
			$messages .= '</div>' . $pager->getNav();
		}
		$messages .= '</section>';
		
		return $messages;
	}
	
	public function isMember($conversation_id, $user_id) {
		return false != $this->db()->query('conversations_users', array('conversation_id' => $conversation_id, 'user_id' => $user_id), array('selectField' => 'id'));
	}
	
	public function getMembers($conversation_id, $user_id = false) {
		$users = $this->db()->query('conversations_users', array('conversation_id' => $conversation_id), array('transpose' => array('user_id', true)));
		if ($user_id) unset($users[$user_id]);
		return $users;
	}
	
	public function getConversations($pager, $user_id = false) {
		global $user, $site;
		
		if (!$user_id) {
			if (!$user->isLoggedIn()) return array();
			$user_id = $user->getUserID();
		}
		
		$rs = $pager->setSQL('
			SELECT c.*, MAX(reply_date) AS reply_date, MIN(`read`) AS isRead, AVG(`deleted`) AS isDeleted, COUNT(r.id) AS count
			FROM conversations AS c
			LEFT JOIN conversations_users AS u ON (c.id = u.conversation_id)
			LEFT JOIN conversations_replies AS r ON (c.id = r.conversation_id)
			LEFT JOIN conversations_replies_status AS s ON (r.id = s.reply_id)
			WHERE u.user_id = ' . $user_id . '
			AND s.user_id = ' . $user_id . '
			AND deleted = 0
			GROUP BY c.id
			HAVING isDeleted != 1
			ORDER BY reply_date DESC
		');
		$conversations = array();
		while ($row = $rs->getRow()) {
			$conversations[] = $row;
		}
		return $conversations;
	}
	
	public function processConversation($data) {
		return $data;
	}
}

?>
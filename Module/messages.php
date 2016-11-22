<?

require_once('model.class.php');

$time = microtime_float();

$options = array(
	'title' => 'GoKunming',
	'debug' => false,
	'noRedirect' => true,
	'db_explain' => false);

// Let's create our model
$model = new MainModel(array(), $options);

$db = $model->db();

$messages = $db->run_select(
	'SELECT DISTINCT m.* , COUNT( message ) AS count
	FROM pm_messages AS m
	WHERE replyto_id = 0
	GROUP BY from_id, message
	HAVING count > 10'
);
$users = 0;
$deleted = 0;
foreach($messages as $message) {
	$users += $db->update('public_users', array('user_id' => $message['from_id']), array('status' => 0));
	$deleted += $db->delete('pm_messages', array('from_id' => $message['from_id']));
}
echo 'Users set non active: ' . $users . '<br>';
echo 'Messages deleted: ' . $deleted . '<br>';

$conversationsDeleted = $db->delete('conversations', '*');

echo 'Conversations deleted: ' . $deleted . '<br>';

$messages = $db->run_select('
	SELECT DISTINCT *
	FROM pm_messages
	WHERE replyto_id =0
	GROUP BY from_id, to_id, subject
');

//echo HTMLHelper::assocToTable($messages);die();

echo '<br>Conversations: ' .  count($messages) . '<br>';

function getReplies($pm_id) {
	$ids = $GLOBALS['db']->query('pm_messages', array('replyto_id' => $pm_id), array('transpose' => 'pm_id'));
	if ($ids) {
		$_ids = array();
		foreach($ids as $id) {
			$__ids = getReplies($id);
			if ($__ids) $_ids = array_merge($_ids, $__ids);
		}
		$ids = array_merge($ids, $_ids);
	}
	return $ids;
}

foreach($messages as $message) {
	$ids = getReplies($message['pm_id']);
	array_unshift($ids, $message['pm_id']);
	$user_args = array($message['from_id'], $message['to_id']);
	$replies = $db->query('pm_messages', array('from_id' => $user_args, 'to_id' => $user_args, "!subject = 'Re: " . $db->escape_clause($message['subject']) . "'"), array('transpose' => 'pm_id'));
	if ($replies) $ids = array_values(array_unique(array_merge($ids, $replies)));
	$ms = $db->query('pm_messages', array('pm_id' => $ids),
		array(
			'getFields' => array('from_id', 'to_id', 'message', 'ts', 'status'),
			'join' => array(
				'table' => 'public_users',
				'on' => array('to_id', 'user_id'),
				'fields' => 'nickname'
			)
		)
	);
	if (!$ms) {
		echo $db->getQuery();
		die('no messages');
	}
	
	$c_id = $db->insert('conversations', array(
		'subject' => $message['subject'],
		'created' => $message['ts'],
		'user_id' => $message['from_id']
	));
	$from_id = $db->insert('conversations_users', array(
		'conversation_id' => $c_id,
		'user_id' => $message['from_id'],
		'date_joined' => $message['ts']
	));
	$to_id = $db->insert('conversations_users', array(	
		'conversation_id' => $c_id,
		'user_id' => $message['to_id'],
		'date_joined' => $message['ts']
	));
	foreach($ms as $m) {
		$pos = mb_strpos($m['message'], $m['nickname'] . ' wrote:');
		if ($pos > 0) $m['message'] = mb_substr($m['message'], 0, $pos);
		$reply_id = $db->insert('conversations_replies', array(
			'conversation_id' => $c_id,
			'reply_date' => $m['ts'],
			'message' => trim($m['message']),
			'user_id' => $m['from_id']
		));
		$db->insert('conversations_replies_status', array(
			array(
				'member_id' => $from_id,
				'user_id' => $m['from_id'],
				'reply_id' => $reply_id,
				'read' => 1,
				'deleted' => ($m['status']) & 1 !== 0
			), array(
				'member_id' => $to_id,
				'user_id' => $m['to_id'],
				'reply_id' => $reply_id,
				'read' => ($m['status'] & 4) !== 0,
				'deleted' => ($m['status'] & 2) !== 0
			)
		));
	}
	echo 'Conversation "' . $message['subject'] . '", total messages: ' . count($ids) . '<br>';
}

//echo sprint_rf($db->getInfo());

echo $time = microtime_float() - $time;

?>
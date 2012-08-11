<?php
require_once 'utils/initPage.php';

if (isset($_POST['action'])
		&& ($_POST['action'] == 'add' || $_POST['action'] == 'remove')) {
	if ($_POST['action'] == 'add') {
		if (isset($_POST['targetId']) && isset($_POST['targetTable'])
				&& isset($_POST['body'])) {
			if (isBlank($_POST['body'])) {
				saveMessage(
						new Message("Commnet body must not be blank",
								Message::SEVERITY_ERROR));
			} else {
				$body = trim($_POST['body']);
				Comment::addCommentFor($_POST['targetId'],
						$_POST['targetTable'], $_SESSION['user']->getId(),
						$body, $db);
			}
		} else {
			saveMessage(
					new Message("Incomplete comment", Message::SEVERITY_ERROR));
		}
	} else if ($_POST['action'] == 'remove') {
		if (isset($_POST['id'])) {
			Comment::removeComment($_POST['id'], $_SESSION['user']->getId(),
					$db);
		} else {
			saveMessage(
					new Message("Missing comment id", Message::SEVERITY_ERROR));
		}
	}
} else {
	saveMessage(new Message("Missing action", Message::SEVERITY_ERROR));
}

if (isset($_POST['referer'])) {
	header("Location: " . $_POST['referer']);
} else if (isset($_SERVER['HTTP_REFERER'])) {
	header("Location: " . $_SERVER['HTTP_REFERER']);
} else {
	saveMessage(new Message("Missing referer", Message::SEVERITY_WARN));
	header("Location: " . url("index.php", NULL));
}
exit();
?>
<?php

require_once 'utils/initPage.php';
require_once 'utils/file.php';
require_once 'utils/search.php';

if (isset($_POST['root'])) {
	$root = $_POST['root'];
} else {
	saveMessage(new Message("Missing root id", Message::SEVERITY_ERROR));
	header("Location: " . url("index.php", NULL));
	exit();
}

$rootFile = getRootFile($root, $db);
if (isset($_POST['ancestor'])) {
	$file = getFile($_POST['ancestor'], $rootFile, $root, $db);
} else {
	$file = $rootFile;
}

$ancestor = $file->getId();

// name select args
if (isset($_POST['nameLike']) && !isBlank($_POST['nameLike'])) {
	$nameLike = trim($_POST['nameLike']);
} else if (isset($_POST['nameLike']) && isBlank($_POST['nameLike'])
		&& strlen($_POST['nameLike']) > 0) {
	saveMessage(
			new Message("Name is blank, will be ignored",
					Message::SEVERITY_WARN));
	$nameLike = NULL;
} else {
	$nameLike = NULL;
}

if (isset($_POST['historyNames']) && $_POST['historyNames'] == 1) {
	$historyNames = true;
} else {
	$historyNames = false;
}

if (isset($_POST['nameCaseSensitive']) && $_POST['nameCaseSensitive'] == 1) {
	$nameCaseSensitive = true;
} else {
	$nameCaseSensitive = false;
}

// plain select args
if (isset($_POST['isDirectory']) && $_POST['isDirectory'] != -1) {
	$isDirectory = $_POST['isDirectory'] == 1;
} else {
	$isDirectory = NULL;
}

if (isset($_POST['isDeleted']) && $_POST['isDeleted'] != -1) {
	$isDeleted = $_POST['isDeleted'] == 1;
} else {
	$isDeleted = NULL;
}

if (isset($_POST['lengthLess']) && strlen($_POST['lengthLess']) > 0) {
	$len = convertLength($_POST['lengthLess']);
	if ($len <= 0) {
		saveMessage(
				new Message(
						"Upper length limit is out or range, will be ignored",
						Message::SEVERITY_WARN));
		$lengthLess = NULL;
	} else {
		$lengthLess = $len;
	}
} else {
	$lengthLess = NULL;
}

if (isset($_POST['lengthGreater']) && strlen($_POST['lengthGreater']) > 0) {
	$len = convertLength($_POST['lengthGreater']);
	if ($len < 0 || ($lengthLess != NULL && $len > $lengthLess)) {
		saveMessage(
				new Message(
						"Lower length limit is out or range, will be ignored",
						Message::SEVERITY_WARN));
		$lengthGreater = NULL;
	} else {
		$lengthGreater = $len;
	}
} else {
	$lengthGreater = NULL;
}

if (isset($_POST['versionLess']) && strlen($_POST['versionLess']) > 0) {
	$ver = intval($_POST['versionLess']);
	if ($ver < 0) {
		saveMessage(
				new Message(
						"Upper version limit is out or range, will be ignored",
						Message::SEVERITY_WARN));
		$versionLess = NULL;
	} else {
		$versionLess = $ver;
	}
} else {
	$versionLess = NULL;
}

if (isset($_POST['versionGreater']) && strlen($_POST['versionGreater']) > 0) {
	$ver = intval($_POST['versionGreater']);
	if ($ver < 0 || ($versionLess != NULL && $ver > $versionLess)) {
		saveMessage(
				new Message(
						"Lower version limit is out or range, will be ignored",
						Message::SEVERITY_WARN));
		$versionGreater = NULL;
	} else {
		$versionGreater = $ver;
	}
} else {
	$versionGreater = NULL;
}

if (isset($_POST['modifiedBefore']) && !isBlank($_POST['modifiedBefore'])) {
	$date = convertDate($_POST['modifiedBefore'], true);
	if (!$date) {
		saveMessage(
				new Message(
						"Upper modified limit is out or range, will be ignored",
						Message::SEVERITY_WARN));
		$modifiedBefore = NULL;
	} else {
		$modifiedBefore = $date;
	}
} else {
	$modifiedBefore = NULL;
}

if (isset($_POST['modifiedAfter']) && !isBlank($_POST['modifiedAfter'])) {
	$date = convertDate($_POST['modifiedAfter'], false);
	if (!$date || ($modifiedBefore != NULL && $modifiedBefore < $date)) {
		saveMessage(
				new Message(
						"Lower modified limit is out or range, will be ignored",
						Message::SEVERITY_WARN));
		$modifiedAfter = NULL;
	} else {
		$modifiedAfter = $date;
	}
} else {
	$modifiedAfter = NULL;
}

if (isset($_POST['mimeLike']) && !isBlank($_POST['mimeLike'])) {
	$mimeLike = trim($_POST['mimeLike']);
} else if (isset($_POST['mimeLike']) && isBlank($_POST['mimeLike'])
		&& strlen($_POST['mimeLike']) > 0) {
	saveMessage(
			new Message("Mime is blank, will be ignored",
					Message::SEVERITY_WARN));
	$mimeLike = NULL;
} else {
	$mimeLike = NULL;
}

// first mention select args
if (isset($_POST['firstMentionBefore'])
		&& !isBlank($_POST['firstMentionBefore'])) {
	$date = convertDate($_POST['firstMentionBefore'], true);
	if (!$date) {
		saveMessage(
				new Message(
						"Upper first mention limit is out or range, will be ignored",
						Message::SEVERITY_WARN));
		$firstMentionBefore = NULL;
	} else {
		$firstMentionBefore = $date;
	}
} else {
	$firstMentionBefore = NULL;
}

if (isset($_POST['firstMentionAfter']) && !isBlank($_POST['firstMentionAfter'])) {
	$date = convertDate($_POST['firstMentionAfter'], false);
	if (!$date || ($firstMentionBefore != NULL && $firstMentionBefore < $date)) {
		saveMessage(
				new Message(
						"Lower first mention limit is out or range, will be ignored",
						Message::SEVERITY_WARN));
		$firstMentionAfter = NULL;
	} else {
		$firstMentionAfter = $date;
	}
} else {
	$firstMentionAfter = NULL;
}

// tag select args
if (isset($_POST['tag']) && !isBlank($_POST['tag'])) {
	$tag = trim($_POST['tag']);
} else if (isset($_POST['tag']) && isBlank($_POST['tag'])
		&& strlen($_POST['tag']) > 0) {
	saveMessage(
			new Message("Tag is blank, will be ignored", Message::SEVERITY_WARN));
	$tag = NULL;
} else {
	$tag = NULL;
}

// comment select args
if (isset($_POST['comment']) && !isBlank($_POST['comment'])) {
	$comment = trim($_POST['comment']);
} else if (isset($_POST['comment']) && isBlank($_POST['comment'])
		&& strlen($_POST['comment']) > 0) {
	saveMessage(
			new Message("Comment is blank, will be ignored",
					Message::SEVERITY_WARN));
	$comment = NULL;
} else {
	$comment = NULL;
}

if (isset($_POST['historyComments']) && $_POST['historyComments'] == 1) {
	$historyComments = true;
} else {
	$historyComments = false;
}

// ancestor select args
// ancestor already processed
if (isset($_POST['historyAncestors']) && $_POST['historyAncestors'] == 1) {
	$historyAncestors = true;
} else {
	$historyAncestors = false;
}

// routine select args
if (isset($_POST['routine']) && $_POST['routine'] != -1) {
	try {
		$r = Routine::getRoutine($_POST['routine'], $db);
		$routine = $r->getId();
	} catch (Exception $e) {
		saveMessage(
				new Message("Routine does not exist, will be ignored",
						Message::SEVERITY_WARN));
		$routine = NULL;
	}
} else {
	$routine = NULL;
}

$_SESSION['search'] = array($nameLike, $historyNames, $nameCaseSensitive,
		$isDirectory, $isDeleted, $lengthLess, $lengthGreater, $versionLess,
		$versionGreater, $modifiedBefore, $modifiedAfter, $mimeLike,
		$firstMentionBefore, $firstMentionAfter, $tag, $comment,
		$historyComments, $ancestor, $historyAncestors, $routine);

header(
		'Location: '
				. url('search.php',
						array('root' => $root, 'ancestor' => $ancestor)));
exit();

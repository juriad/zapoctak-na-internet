<?php

require_once 'utils/initPage.php';

if (isset($_POST['paginationPerPage'])
		&& isNumberOnly($_POST['paginationPerPage'])
		&& $_POST['paginationPerPage'] >= 10) {
	$_SESSION['paginationPerPage'] = $_POST['paginationPerPage'];
	unset($_POST['paginationPerPage']);
} else if (isset($_GET['sortColumn']) && isNumberOnly($_GET['sortColumn'])) {
	$_SESSION['currentSortCol'] = $_GET['sortColumn'];
	unset($_GET['sortColumn']);
	if (isset($_GET['sortOrder']) && $_GET['sortOrder'] != 'asc') {
		$_SESSION['currentSortOrder'] = 'desc';
	} else {
		$_SESSION['currentSortOrder'] = 'asc';
	}
} else {
	saveMessage(new Message("No action specified", Message::SEVERITY_ERROR));
}

if (isset($_POST['referer'])) {
	header(
			"Location: "
					. preg_replace('/page=[0-9]*/', 'page=1', $_POST['referer']));
} else if (isset($_GET['referer'])) {
	header(
			"Location: "
					. preg_replace('/page=[0-9]*/', 'page=1', $_GET['referer']));
} else if (isset($_SERVER['HTTP_REFERER'])) {
	header(
			"Location: "
					. preg_replace('/page=[0-9]*/', 'page=1',
							$_SERVER['HTTP_REFERER']));
} else {
	saveMessage(new Message("Missing referer", Message::SEVERITY_WARN));
	header("Location: " . url("index.php", NULL));
}
exit();

?>
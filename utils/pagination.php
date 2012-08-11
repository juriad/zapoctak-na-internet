<?php

function paginationFilesSortLink($col, $name) {
	if (!isset($_SESSION['currentSortCol']) || $_SESSION['currentSortCol'] > 5) {
		$_SESSION['currentSortCol'] = 0;
	}
	$current = $_SESSION['currentSortCol'];
	if ($col != $current) {
		echo "<th class='header'>
					<a href='";
		u('processPagination.php',
				array('referer' => $_SERVER['REQUEST_URI'],
						'sortColumn' => $col, 'sortOrder' => 'asc'));
		echo "'>" . $name . "</a></th>";
	} else {
		if (!isset($_SESSION['currentSortOrder'])) {
			$_SESSION['currentSortOrder'] = 'asc';
		}
		$order = $_SESSION['currentSortOrder'];
		if ($order == 'asc') {
			echo "<th class='header headerSortDown'>
					<a href='";
			u('processPagination.php',
					array('referer' => $_SERVER['REQUEST_URI'],
							'sortColumn' => $col, 'sortOrder' => 'desc'));
			echo "'>" . $name . "</a></th>";
		} else {
			echo "<th class='header headerSortUp'>
					<a href='";
			u('processPagination.php',
					array('referer' => $_SERVER['REQUEST_URI'],
							'sortColumn' => $col, 'sortOrder' => 'asc'));
			echo "'>" . $name . "</a></th>";
		}
	}
}

// session must contain valid column and order
// select must be valid select returning id
function createFileIdsOrderSelect($select) {
	switch ($_SESSION['currentSortCol']) {
	case 0: // type
		return "SELECT x.id AS id
				FROM ($select) AS x, files f
				WHERE f.id = x.id
				ORDER BY f.isDirectory "
				. ($_SESSION['currentSortOrder'] == 'asc' ? 'ASC' : 'DESC')
				. ", f.name ASC";
	case 1: // name
		return "SELECT x.id AS id
				FROM ($select) AS x, files f
				WHERE f.id = x.id
				ORDER BY f.name "
				. ($_SESSION['currentSortOrder'] == 'asc' ? 'ASC' : 'DESC');
	case 2: // size
		return "SELECT x.id AS id
				FROM ($select) AS x, files f
				WHERE f.id = x.id
				ORDER BY f.length "
				. ($_SESSION['currentSortOrder'] == 'asc' ? 'ASC' : 'DESC');
	case 3: // modified
		return "SELECT x.id AS id
				FROM ($select) AS x, files f
				WHERE f.id = x.id
				ORDER BY f.modified "
				. ($_SESSION['currentSortOrder'] == 'asc' ? 'ASC' : 'DESC');
	case 4: // version
		return "SELECT x.id AS id
				FROM ($select) AS x, files f
				WHERE f.id = x.id
				ORDER BY f.version "
				. ($_SESSION['currentSortOrder'] == 'asc' ? 'ASC' : 'DESC');
	case 5: // first mention
		return "SELECT x.id AS id
				FROM ($select) AS x, files f
					LEFT JOIN history h ON (f.id = h.fileId AND h.version = 0)
				WHERE f.id = x.id
				ORDER BY CASE WHEN h.modified IS NULL THEN f.modified ELSE h.modified END "
				. ($_SESSION['currentSortOrder'] == 'asc' ? 'ASC' : 'DESC');
	case 6: // parent
		return "SELECT x.id AS id
				FROM ($select) AS x, files f1, files f2
				WHERE f1.id = x.id AND f1.parent = f2.id
				ORDER BY f2.name "
				. ($_SESSION['currentSortOrder'] == 'asc' ? 'ASC' : 'DESC');
	}
}

?>
<?php

function s($escape) {
	return sqlite_escape_string($escape);
}

function rangeClause($field, $less, $greater) {
	if ($less === NULL && $greater !== NULL) {
		return "$field >= $greater";
	} else if ($less !== NULL && $greater === NULL) {
		return "$field <= $less";
	} else {
		return "$field BETWEEN $greater AND $less";
	}
}

function likeClause($field, $like, $caseSensitive) {
	if ($caseSensitive) {
		return "$field GLOB '" . s($like) . "'";
	} else {
		$sqlike = preg_replace(array('/\*/', '/\?/'), array('%', '_'), $like);
		return "$field LIKE '" . s($sqlike) . "'";
	}
}

function boolClause($field, $bool) {
	if ($bool) {
		return "$field = 1";
	} else {
		return "$field = 0";
	}
}

function convertLength($length) {
	$len = floatval($length);
	if (preg_match('/K/i', $length)) {
		$len *= 1;
	} else if (preg_match('/K/i', $length)) {
		$len *= 1024;
	} else if (preg_match('/M/i', $length)) {
		$len *= 1024 * 1024;
	} else if (preg_match('/G/i', $length)) {
		$len *= 1024 * 1024 * 1024;
	} else if (preg_match('/T/i', $length)) {
		$len *= 1024 * 1024 * 1024 * 1024;
	} else {
		$len *= 1;
	}
	return (int) $len;
}

function convertDate($date, $end = false) {
	list($year, $month, $day) = explode('-', $date) + Array(0, 0, 0);
	$year = intval($year);
	$month = intval($month);
	$day = intval($day);
	if ($year <= 0 || $month <= 0 || $day <= 0) {
		return false;
	}
	$time = mktime(0, 0, 0, $month, $day, $year);
	if ($time && !$end) {
		return $time;
	} else if ($time && $end) {
		return $time + 24 * 60 * 60 - 1;
	} else {
		return false;
	}
}

function formatDate($date) {
	if ($date === NULL) {
		return NULL;
	} else {
		return date('Y-m-d', $date);
	}
}

function selectByName($nameLike, $historyNames, $nameCaseSensitive) {
	if ($nameLike === NULL) {
		return NULL;
	}
	$select = '';
	if ($historyNames) {
		$select .= 'SELECT id FROM ( ';
	}
	$select .= 'SELECT f.id AS id FROM files f WHERE '
			. likeClause('f.name', $nameLike, $nameCaseSensitive);
	if ($historyNames) {
		$select .= ' UNION ';
		$select .= 'SELECT h.fileId AS id FROM history h WHERE '
				. likeClause('h.name', $nameLike, $nameCaseSensitive);
		$select .= ' )';
	}
	return $select;
}

function selectByPlain($isDirectory, $isDeleted, $lengthLess, $lengthGreater,
		$versionLess, $versionGreater, $modifiedBefore, $modifiedAfter,
		$mimeLike) {
	$i = 0;
	$select = 'SELECT f.id AS id FROM files f WHERE';
	if ($isDirectory !== NULL) {
		$select .= $i++ > 0 ? ' AND' : ' ';
		$select .= boolClause('f.isDirectory', $isDirectory);
	}
	if ($isDeleted !== NULL) {
		$select .= $i++ > 0 ? ' AND ' : ' ';
		$select .= boolClause('f.isDeleted', $isDeleted);
	}
	if ($lengthLess !== NULL || $lengthGreater !== NULL) {
		$select .= $i++ > 0 ? ' AND ' : ' ';
		$select .= rangeClause('f.length', $lengthLess, $lengthGreater);
	}
	if ($versionLess !== NULL || $versionGreater !== NULL) {
		$select .= $i++ > 0 ? ' AND ' : ' ';
		$select .= rangeClause('f.version', $versionLess, $versionGreater);
	}
	if ($modifiedBefore !== NULL || $modifiedAfter !== NULL) {
		$select .= $i++ > 0 ? ' AND ' : ' ';
		$select .= rangeClause('f.modified', $modifiedBefore, $modifiedAfter);
	}
	if ($mimeLike !== NULL) {
		$select .= $i++ > 0 ? ' AND ' : ' ';
		$select .= likeClause('f.mime', "%$mimeLike%", false);
	}
	if ($i > 0) {
		return $select;
	} else {
		return NULL;
	}
}

function selectByFirstMention($firstMentionBefore, $firstMentionAfter) {
	if ($firstMentionBefore === NULL && $firstMentionAfter === NULL) {
		return NULL;
	}
	$select = 'SELECT id FROM ( ';
	$select .= 'SELECT h.fileId AS id FROM history h WHERE h.version = 0 AND '
			. rangeClause('h.modified', $firstMentionBefore, $firstMentionAfter);
	$select .= ' UNION ';
	$select .= 'SELECT f.id AS id FROM files f WHERE f.version = 0 AND '
			. rangeClause('f.modified', $firstMentionBefore, $firstMentionAfter);
	$select .= ' )';
	return $select;
}

function selectByTag($tag) {
	if ($tag === NULL) {
		return NULL;
	}
	$select = 'SELECT fileId AS id FROM files_tags ft, tags t WHERE ft.tagId = t.id AND '
			. 't.name = \'' . s($tag) . '\'';
	return $select;
}

function selectByComment($comment, $historyComments) {
	if ($comment === NULL) {
		return NULL;
	}
	$select = '';
	if ($historyComments) {
		$select .= 'SELECT id FROM ( ';
	}
	$select .= 'SELECT c.targetId AS id FROM comments c, commentBodies cb WHERE '
			. 'c.id = cb.id AND c.targetTable = \'files\' AND '
			. 'cb.body MATCH \'' . s($comment) . '\'';
	if ($historyComments) {
		$select .= ' UNION ';
		$select .= 'SELECT h.fileId AS id FROM history h, comments c, commentBodies cb WHERE '
				. 'c.id = cb.id AND c.targetTable = \'history\' AND h.id = c.targetId AND '
				. 'cb.body MATCH \'' . s($comment) . '\'';
		$select .= ' )';
	}
	return $select;
}

function selectByAncestor($ancestor, $historyAncestors) {
	if ($ancestor === NULL) {
		return NULL;
	}
	$select = '';
	if ($historyAncestors) {
		$select .= 'SELECT id FROM ( ';
	}
	$select .= 'SELECT f.id AS id FROM files f WHERE ' . 'f.path LIKE '
			. "'%/$ancestor/%'";
	if ($historyAncestors) {
		$select .= ' UNION ';
		$select .= 'SELECT h.fileId AS id FROM history h WHERE '
				. 'h.path LIKE ' . "'%/$ancestor/%'";
		$select .= ' )';
	}
	return $select;
}

function selectByRoutine($routine) {
	if ($routine === NULL) {
		return NULL;
	}
	$select = 'SELECT r.fileId AS id FROM rules r WHERE r.routineId = '
			. $routine;
	return $select;
}

function appendIfNotNull($select, &$selects) {
	if ($select !== NULL) {
		$selects[] = $select;
	}
}

// at least ancestor is set
function search($nameLike, $historyNames, $nameCaseSensitive, $isDirectory,
		$isDeleted, $lengthLess, $lengthGreater, $versionLess, $versionGreater,
		$modifiedBefore, $modifiedAfter, $mimeLike, $firstMentionBefore,
		$firstMentionAfter, $tag, $comment, $historyComments, $ancestor,
		$historyAncestors, $routine, PDO $db) {
	$selects = array();
	appendIfNotNull(
			selectByName($nameLike, $historyNames, $nameCaseSensitive),
			$selects);
	appendIfNotNull(
			selectByPlain($isDirectory, $isDeleted, $lengthLess,
					$lengthGreater, $versionLess, $versionGreater,
					$modifiedBefore, $modifiedAfter, $mimeLike), $selects);
	appendIfNotNull(
			selectByFirstMention($firstMentionBefore, $firstMentionAfter),
			$selects);
	appendIfNotNull(selectByTag($tag), $selects);
	appendIfNotNull(selectByComment($comment, $historyComments), $selects);
	appendIfNotNull(selectByAncestor($ancestor, $historyAncestors), $selects);
	appendIfNotNull(selectByRoutine($routine), $selects);

	$select = implode(' INTERSECT ', $selects);
	require_once 'utils/pagination.php';
	$statement = $db->query(createFileIdsOrderSelect($select));
	return $statement->fetchAll(PDO::FETCH_COLUMN);
}

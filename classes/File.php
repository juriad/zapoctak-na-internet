<?php

class File extends HistoryItem {

	private $isDirectory, $isDeleted, $inode, $firstMention;

	private $history, $tags;

	function __construct($id, PDO $db) {
		$selectFile = $db
				->prepare(
						'SELECT f.id AS id, f.version AS version, f.name AS name,
						f.isDirectory AS isDirectory, f.isDeleted AS isDeleted, f.inode AS inode,
						datetime(f.modified, \'unixepoch\', \'localtime\') AS modified, f.mime AS mime,
						f.length AS length, datetime(s.time, \'localtime\') AS scan,
						f.parent AS parent, f.path AS path, f.realPath AS realPath
						FROM files f
						LEFT JOIN scans s ON s.id=f.scanId
						WHERE f.id=?');
		execute($selectFile, $id, PDO::PARAM_INT);
		$row = $selectFile->fetch(PDO::FETCH_ASSOC);
		if (!$row) {
			throw new InvalidArgumentException('no root with such id');
		}
		$this->id = $id;
		$this->fileId = $id;
		$this->version = $row['version'];
		$this->name = $row['name'];
		$this->isDirectory = $row['isDirectory'] == 1;
		$this->isDeleted = $row['isDeleted'] == 1;
		$this->inode = $row['inode'];
		$this->modified = $row['modified'];
		$this->length = $row['length'];
		$this->mime = $row['mime'];
		$this->scan = $row['scan'];
		$this->parent = $row['parent'];
		$this->path = $row['path'];
		$this->realPath = $row['realPath'];
		$selectMention = $db
				->prepare(
						'SELECT datetime(h.modified, \'unixepoch\', \'localtime\') AS mention
						FROM history h
						WHERE h.fileId = ? AND h.version=0');
		execute($selectMention, $id, PDO::PARAM_STR);
		$row = $selectMention->fetch(PDO::FETCH_ASSOC);
		if (!$row) {
			$this->firstMention = $this->modified;
		} else {
			$this->firstMention = $row['mention'];
		}
	}

	function isDirectory() {
		return $this->isDirectory;
	}

	function isDeleted() {
		return $this->isDeleted;
	}

	function getInode() {
		return $this->inode;
	}

	function getFirstMention() {
		return $this->firstMention;
	}

	// bare
	function getAncestors(File $rootFile, PDO $db) {
		$selAnc = $db
				->prepare(
						'SELECT id, name
						FROM files
						WHERE ? LIKE path||\'%\' AND path LIKE ?||\'%\'
						ORDER BY path');
		execute($selAnc, $this->getPath(), PDO::PARAM_STR,
				$rootFile->getPath(), PDO::PARAM_STR);
		$ancestors = $selAnc->fetchAll(PDO::FETCH_ASSOC);
		$ids = array();
		foreach ($ancestors as $anc) {
			array_push($ids, $anc['id']);
			array_push($ids, $anc['name']);
		}
		return $ids;
	}

	function isDescendantOf($rootFileId) {
		return preg_match("#/$rootFileId/#", $this->getPath()) == 1;
	}

	function getChildrenIds($deleted, $start, $limit, PDO $db) {
		require_once 'utils/pagination.php';
		$coreSelect = 'SELECT id FROM files WHERE parent = ?'
				. (!$deleted ? ' AND isDeleted = 0' : '');
		$orderSelect = createFileIdsOrderSelect($coreSelect)
				. (isset($start) && isset($limit) ? " LIMIT $start, $limit" : '');
		$selChild = $db->prepare($orderSelect);
		execute($selChild, $this->getId(), PDO::PARAM_INT);
		$childrenIds = $selChild->fetchAll(PDO::FETCH_ASSOC);
		$children = array();
		foreach ($childrenIds as $child) {
			array_push($children, $child['id']);
		}
		return $children;
	}

	function getChildren($deleted, $start, $limit, PDO $db) {
		$children = array();
		foreach ($this->getChildrenIds($deleted, $start, $limit, $db) as $child) {
			array_push($children, new File($child, $db));
		}
		return $children;
	}

	function fetchHistory(PDO $db) {
		if (!isset($this->history)) {
			$this->history = new History($this, $db);
		}
		return $this->history;
	}

	function fetchTags(PDO $db) {
		if (!isset($this->tags)) {
			$selTags = $db
					->prepare(
							'SELECT t.name AS name, m.userId AS userId,
							datetime(m.added, \'localtime\') AS added
							FROM tags t, files_tags m
							WHERE m.tagId = t.id AND m.fileId = ?
							ORDER BY m.added');
			execute($selTags, $this->getId(), PDO::PARAM_INT);
			$ttags = $selTags->fetchAll(PDO::FETCH_ASSOC);
			$this->tags = array();
			foreach ($ttags as $tag) {
				array_push($this->tags, new Tag($tag));
			}
		}
		return $this->tags;
	}

	function removeTag($name, $userId, PDO $db) {
		$remove = $db
				->prepare(
						'DELETE FROM files_tags
						WHERE fileId = ? AND tagId = (SELECT id FROM tags WHERE name = ?) AND userId = ?');
		execute($remove, $this->getId(), PDO::PARAM_INT, $name, PDO::PARAM_STR,
				$userId, PDO::PARAM_INT);
	}

	function addTag($name, $userId, PDO $db) {
		$selId = $db->prepare('SELECT id FROM tags WHERE name = ?');
		execute($selId, $name, PDO::PARAM_STR);
		$row = $selId->fetch(PDO::FETCH_ASSOC);
		if (!$row) {
			$insertName = $db->prepare('INSERT INTO tags (name) VALUES (?)');
			execute($insertName, $name, PDO::PARAM_STR);
			$id = $db->lastInsertId();
		} else {
			$id = $row['id'];
		}
		$insertTag = $db
				->prepare(
						'INSERT INTO files_tags (fileId, tagId, userId)
						VALUES (?, ?, ?)');
		execute($insertTag, $this->getId(), PDO::PARAM_INT, $id,
				PDO::PARAM_INT, $userId, PDO::PARAM_INT);
	}

	// set userId to NULL to fetch all
	function fetchRuleIds($userId, PDO $db) {
		$selRules = $db
				->prepare(
						"SELECT r.id AS id
						FROM rules r, files f
						WHERE r.fileId = f.id AND r.subdirectories = 1 AND
				 		(SELECT path FROM files WHERE id = ?) LIKE f.path || '%'"
								. (isset($userId) ? " AND userId = ?" : "")
								. "UNION
						SELECT r.id AS id
						FROM rules r
						WHERE (r.fileId = (SELECT parent FROM files WHERE id = ? ) OR r.fileId = ?)
				 		AND r.subdirectories = 0"
								. (isset($userId) ? " AND userId = ?" : ""));
		if (isset($userId)) {
			execute($selRules, $this->getId(), PDO::PARAM_INT, $userId,
					PDO::PARAM_INT, $this->getId(), PDO::PARAM_INT,
					$this->getId(), PDO::PARAM_INT, $userId, PDO::PARAM_INT);
		} else {
			execute($selRules, $this->getId(), PDO::PARAM_INT, $this->getId(),
					PDO::PARAM_INT, $this->getId(), PDO::PARAM_INT);
		}
		$ruleIds = $selRules->fetchAll(PDO::FETCH_ASSOC);
		$rules = array();
		foreach ($ruleIds as $ruleId) {
			array_push($rules, $ruleId['id']);
		}
		return $rules;
	}

	// set userId to NULL to fetch all
	function fetchRules($userId, PDO $db) {
		$rules = array();
		foreach (self::fetchRuleIds($userId, $db) as $ruleId) {
			array_push($rules, new Rule($ruleId, $db));
		}
		return $rules;
	}
}

?>
<?php

class Rule {

	private $id, $userId, $name, $criterion, $value, $added, $routineId, $fileId, $subdirectories, $lastScan;

	private $routine;

	function __construct($id, PDO $db) {
		$selectRule = $db
				->prepare(
						'SELECT r.id AS id, r.userId AS userId, r.name AS name,
						r.criterion AS criterion, r.value AS value, r.routineId AS routineId,
						datetime(r.added, \'localtime\') AS added, r.fileId AS fileId,
						r.subdirectories AS subdirectories, datetime(s.time, \'localtime\') AS lastScan
						FROM rules r
						LEFT JOIN scans s ON r.lastScanId = s.id
						WHERE r.id = ?');
		execute($selectRule, $id, PDO::PARAM_INT);
		$row = $selectRule->fetch(PDO::FETCH_ASSOC);
		if (!$row) {
			throw new InvalidArgumentException('no rule with such id');
		}
		$this->id = $id;
		$this->userId = $row['userId'];
		$this->name = $row['name'];
		$this->criterion = $row['criterion'];
		$this->value = $row['value'];
		$this->added = $row['added'];
		$this->routineId = $row['routineId'];
		$this->fileId = $row['fileId'];
		$this->subdirectories = $row['subdirectories'];
		$this->lastScan = $row['lastScan'];
	}

	function getId() {
		return $this->id;
	}

	function getUserId() {
		return $this->userId;
	}

	function getName() {
		return $this->name;
	}

	function getCriterion() {
		return $this->criterion;
	}

	function getValue() {
		return $this->value;
	}

	function getAdded() {
		return $this->added;
	}

	function getRoutineId() {
		return $this->routineId;
	}

	function getFileId() {
		return $this->fileId;
	}

	function isSubdirectories() {
		return $this->subdirectories;
	}

	function getLastScan() {
		return $this->lastScan;
	}

	function getRoutine(PDO $db) {
		return Routine::getRoutine($this->getRoutineId(), $db);
	}

	static function createRule($userId, $name, $criterion, $value, $routineId,
			$fileId, $subdirectories, PDO $db) {
		if (!($update = $db
				->prepare(
						'INSERT INTO rules (userId, name, criterion, value, routineId, fileId, subdirectories)
						VALUES (?, ?, ?, ?, ?, ?, ?)'))) {
			return false;
		}
		if (!execute($update, $userId, PDO::PARAM_INT, $name, PDO::PARAM_STR,
				$criterion, PDO::PARAM_STR, $value, PDO::PARAM_STR, $routineId,
				PDO::PARAM_INT, $fileId, PDO::PARAM_INT,
				$subdirectories == 1 ? 1 : 0, PDO::PARAM_INT)) {
			return false;
		}
		return true;
	}

	function removeRule(PDO $db) {
		if (!($update = $db->prepare('DELETE FROM rules
				WHERE id = ?'))) {
			return false;
		}
		if (!execute($update, $this->getId(), PDO::PARAM_INT)) {
			return false;
		}
		return true;
	}
}

?>
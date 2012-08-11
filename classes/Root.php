<?php

class Root {

	private $id, $name, $path, $state, $lastScan, $scanInterval, $added, $rootFile;

	function __construct($id, PDO $db) {
		$selectRoot = $db
				->prepare(
						'SELECT r.id AS id, r.name AS name, r.path AS path, r.state AS state,
						datetime(s.time, \'localtime\') AS lastScan, r.scanInterval AS scanInterval,
						datetime(r.added, \'localtime\') AS added, r.rootFile AS rootFile
						FROM roots r
						LEFT JOIN scans s ON s.id=lastScanId
						WHERE r.id=?');
		execute($selectRoot, $id, PDO::PARAM_INT);
		$row = $selectRoot->fetch(PDO::FETCH_ASSOC);
		if (!$row) {
			throw new InvalidArgumentException('no root with such id');
		}
		$this->id = $id;
		$this->name = $row['name'];
		$this->path = $row['path'];
		$this->state = $row['state'];
		$this->lastScan = $row['lastScan'];
		$this->scanInterval = $row['scanInterval'];
		$this->added = $row['added'];
		$this->rootFile = $row['rootFile'];
	}

	function getId() {
		return $this->id;
	}

	function getName() {
		return $this->name;
	}

	function getPath() {
		return $this->path;
	}

	function getState() {
		return $this->state;
	}

	function getLastScan() {
		return $this->lastScan;
	}

	function getScanInterval() {
		return $this->scanInterval;
	}

	function getAdded() {
		return $this->added;
	}

	function getRootFile() {
		return $this->rootFile;
	}

	function setState($state, PDO $db) {
		if ($this->getState() == $state) {
			return true;
		}
		if (!($update = $db
				->prepare('UPDATE roots
						SET state = ?
						WHERE id = ?'))) {
			return false;
		}
		if (!execute($update, $state ? 1 : 0, PDO::PARAM_INT, $this->getId(),
				PDO::PARAM_INT)) {
			return false;
		}
		$this->state = $state;
		return true;
	}

	function setScanInterval($interval, PDO $db) {
		if ($this->getScanInterval() == $interval) {
			return true;
		}
		if (!($update = $db
				->prepare(
						'UPDATE roots
						SET scanInterval = ?
						WHERE id = ?'))) {
			return false;
		}
		if (!execute($update, $interval, PDO::PARAM_INT, $this->getId(),
				PDO::PARAM_INT)) {
			return false;
		}
		$this->scanInterval = $interval;
		return true;
	}

	static function createNewRoot($name, $path, $interval, $active, PDO $db) {
		if (!($insert = $db
				->prepare(
						'INSERT INTO roots (name, path, scanInterval, state)
						VALUES (?, ?, ?, ?)'))) {
			return false;
		}
		if (!execute($insert, $name, PDO::PARAM_STR, $path, PDO::PARAM_STR,
				$interval, PDO::PARAM_INT, $active ? 1 : 0, PDO::PARAM_INT)) {
			return false;
		}
		return $db->lastInsertId();
	}

	// $users is cached array, will not be modified
	function getUserRoots($users, PDO $db) {
		$us = array();
		foreach ($users as $user) {
			$us[$user->getId()] = $user;
		}
		return UserRoot::getUserRootsForRoot($this, $us, $db);
	}

	static function getAllRoots(PDO $db) {
		$selectRoots = $db
				->prepare('SELECT id
							FROM roots
							ORDER BY id');
		$selectRoots->execute();
		$rootIds = $selectRoots->fetchAll(PDO::FETCH_ASSOC);
		$roots = array();
		foreach ($rootIds as $rootId) {
			array_push($roots, new Root($rootId['id'], $db));
		}
		return $roots;
	}

	function addUser($userId, $db) {
		if (!($update = $db
				->prepare(
						'INSERT INTO user_roots (userId, rootId, active)
						VALUES (?, ?, ?)'))) {
			return false;
		}
		if (!execute($update, $userId, PDO::PARAM_INT, $this->getId(),
				PDO::PARAM_INT, 0, PDO::PARAM_INT)) {
			return false;
		}
		return true;
	}

	function removeUser($userId, $db) {
		if (!($update = $db
				->prepare(
						'DELETE FROM user_roots
						WHERE userId = ? AND rootId = ?'))) {
			return false;
		}
		if (!execute($update, $userId, PDO::PARAM_INT, $this->getId(),
				PDO::PARAM_INT)) {
			return false;
		}
		return true;
	}
}

?>
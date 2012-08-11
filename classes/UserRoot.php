<?php

class UserRoot {

	private $id, $user, $root, $active, $added;

	// $users is associates userId to user; $roots as well
	function __construct($id, &$users, &$roots, PDO $db) {
		$selectUserRoot = $db
				->prepare(
						'SELECT id, userId, rootId, active,
						datetime(added, \'localtime\') AS added
						FROM user_roots
						WHERE id=?');
		execute($selectUserRoot, $id, PDO::PARAM_INT);
		$row = $selectUserRoot->fetch(PDO::FETCH_ASSOC);
		if (!$row) {
			throw new InvalidArgumentException('no user root with such id');
		}

		$this->id = $id;
		$this->user = $this->getCacheUser($row['userId'], $users, $db);
		$this->root = $this->getCacheRoot($row['rootId'], $roots, $db);
		$this->active = $row['active'] == 1;
		$this->added = $row['added'];
	}

	function getId() {
		return $this->id;
	}

	function getUser() {
		return $this->user;
	}

	function getRoot() {
		return $this->root;
	}

	function isActive() {
		return $this->active;
	}

	function getAdded() {
		return $this->added;
	}

	function setActive($state, PDO $db) {
		if ($this->isActive() == $state) {
			return true;
		}
		if (!($update = $db
				->prepare(
						'UPDATE user_roots
						SET active = ?
						WHERE id = ?'))) {
			return false;
		}
		if (!execute($update, $state ? 1 : 0, PDO::PARAM_INT, $this->getId(),
				PDO::PARAM_INT)) {
			return false;
		}
		$this->active = $state;
		return true;
	}

	private function getCacheUser($userId, &$users, PDO $db) {
		if (!isset($users[$userId])) {
			if (isset($_SESSION['user'])
					&& $_SESSION['user']->getId() == $userId) {
				$users[$userId] = $_SESSION['user'];
			} else {
				$users[$userId] = new User($userId, $db);
			}
		}
		return $users[$userId];
	}

	private function getCacheRoot($rootId, &$roots, PDO $db) {
		if (!isset($roots[$rootId])) {
			$roots[$rootId] = new Root($rootId, $db);
		}
		return $roots[$rootId];
	}

	static function getUserRootsForRoot(Root $root, $users, PDO $db) {
		$selectUserRoots = $db
				->prepare(
						'SELECT id
						FROM user_roots
						WHERE rootId = ?
						ORDER BY id');
		execute($selectUserRoots, $root->getId(), PDO::PARAM_INT);
		$userRootIds = $selectUserRoots->fetchAll(PDO::FETCH_ASSOC);
		$userRoots = array();
		foreach ($userRootIds as $userRootId) {
			$a = array($root->getId() => $root);
			array_push($userRoots,
					new UserRoot($userRootId['id'], $users, $a, $db));
		}
		return $userRoots;
	}

	static function getUserRootsForUser(User $user, $roots, PDO $db) {
		$selectUserRoots = $db
				->prepare(
						'SELECT id
						FROM user_roots
						WHERE userId = ?
						ORDER BY id');
		execute($selectUserRoots, $user->getId(), PDO::PARAM_INT);
		$userRootIds = $selectUserRoots->fetchAll(PDO::FETCH_ASSOC);
		$userRoots = array();
		foreach ($userRootIds as $userRootId) {
			$a = array($user->getId() => $user);
			array_push($userRoots,
					new UserRoot($userRootId['id'], $a, $roots, $db));
		}
		return $userRoots;
	}
}

?>
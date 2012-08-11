<?php

class User {

	public static $ALL_ROLES = array('users', 'roots');

	private $id, $name, $active, $added, $lastLogged, $roles;

	function __construct($id, PDO $db) {
		$selectUser = $db
				->prepare(
						'SELECT id, name, active, datetime(added, \'localtime\') AS added,
						datetime(lastLogged, \'localtime\') AS lastLogged
						FROM users
						WHERE id=?');
		execute($selectUser, $id, PDO::PARAM_INT);
		$row = $selectUser->fetch(PDO::FETCH_ASSOC);
		if (!$row) {
			throw new InvalidArgumentException('no user with such id');
		}
		$this->id = $id;
		$this->name = $row['name'];
		$this->active = $row['active'] == 1;
		$this->added = $row['added'];
		$this->lastLogged = $row['lastLogged'];

		$selectRoles = $db
				->prepare(
						'SELECT role
						FROM user_roles
						WHERE userId=?');
		execute($selectRoles, $id, PDO::PARAM_INT);
		$rows = $selectRoles->fetchAll(PDO::FETCH_ASSOC);
		$this->roles = array();
		foreach ($rows as $row) {
			array_push($this->roles, $row['role']);
		}
	}

	function getId() {
		return $this->id;
	}

	function getName() {
		return $this->name;
	}

	function isActive() {
		return $this->active;
	}

	function getAdded() {
		return $this->added;
	}

	function getLastLogged() {
		return $this->lastLogged;
	}

	function setActive($state, PDO $db) {
		if ($this->isActive() == $state) {
			return true;
		}
		if (!($update = $db
				->prepare(
						'UPDATE users
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

	function hasRole($role) {
		return in_array($role, $this->roles);
	}

	function addRole($role, PDO $db) {
		if ($this->hasRole($role)) {
			return true;
		}
		if (in_array($role, self::$ALL_ROLES))
			if (!($update = $db
					->prepare(
							'INSERT INTO user_roles (userId, role)
							VALUES (?, ?)'))) {
				return false;
			}
		if (!execute($update, $this->getId(), PDO::PARAM_INT, $role,
				PDO::PARAM_STR)) {
			return false;
		}
		array_push($this->roles, $role);
		return true;
	}

	function removeRole($role, PDO $db) {
		if (!$this->hasRole($role)) {
			return true;
		}
		if (!($update = $db
				->prepare(
						'DELETE FROM user_roles
						WHERE userId = ? AND role = ?'))) {
			return false;
		}
		if (!execute($update, $this->getId(), PDO::PARAM_INT, $role,
				PDO::PARAM_STR)) {
			return false;
		}
		unset($this->roles[array_search($role, $this->roles)]);
		$this->roles = array_values($this->roles);
		return true;
	}

	function getRoles() {
		return $this->roles;
	}

	function refreshLastLogged(PDO $db) {
		if (!($update = $db
				->prepare(
						'UPDATE users
						SET lastLogged = datetime(\'now\')
						WHERE id = ?'))) {
			return false;
		}
		if (!execute($update, $this->getId(), PDO::PARAM_INT)) {
			return false;
		}
		$select = $db
				->prepare(
						'SELECT datetime(lastLogged, \'localtime\') AS lastLogged
						FROM users
						WHERE id = ?');
		execute($select, $this->getId(), PDO::PARAM_INT);
		$row = $select->fetch(PDO::FETCH_ASSOC);
		if ($row) {
			$this->lastLogged = $row['lastLogged'];
		}
		return true;
	}

	function setPassword($password, PDO $db) {
		if (!($update = $db
				->prepare(
						'UPDATE users
						SET password = ?
						WHERE id = ?'))) {
			return false;
		}
		if (!execute($update, sha1($password), PDO::PARAM_STR, $this->getId(),
				PDO::PARAM_INT)) {
			return false;
		}
		return true;
	}

	static function createNewUser($name, $password, $active, PDO $db) {
		if (!($insert = $db
				->prepare(
						'INSERT INTO users (name, active, password)
						VALUES (?, ?, ?)'))) {
			return false;
		}
		if (!execute($insert, $name, PDO::PARAM_STR, $active ? 1 : 0,
				PDO::PARAM_INT, sha1($password), PDO::PARAM_STR)) {
			return false;
		}
		return $db->lastInsertId();
	}

	// $roots is cached array, will not be modified
	function getUserRoots($roots, PDO $db) {
		$rs = array();
		foreach ($roots as $root) {
			$rs[$root->getId()] = $root;
		}
		return UserRoot::getUserRootsForUser($this, $rs, $db);
	}

	static function getAllUserIds(PDO $db) {
		$selectUsers = $db
				->prepare('SELECT id
						FROM users
						ORDER BY id');
		$selectUsers->execute();
		$userIds = $selectUsers->fetchAll(PDO::FETCH_ASSOC);
		$ids = array();
		foreach ($userIds as $userId) {
			array_push($ids, $userId['id']);
		}
		return $ids;
	}

	static function getAllUsers(PDO $db) {
		$users = array();
		foreach (self::getAllUserIds($db) as $userId) {
			if (isset($_SESSION['user'])
					&& $userId == $_SESSION['user']->getId()) {
				$user = $_SESSION['user'];
			} else {
				$user = new User($userId, $db);
			}
			array_push($users, $user);
		}
		return $users;
	}
}

?>
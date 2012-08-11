<?php

function __autoload($class_name) {
	include 'classes/' . $class_name . '.php';
}

function saveMessage($message) {
	if (!isset($_SESSION['messages'])) {
		$_SESSION['messages'] = array($message);
	} else {
		array_push($_SESSION['messages'], $message);
	}
}

function h($string) {
	echo htmlspecialchars($string);
}

function url($page, $args) {
	$url = $page;
	if (isset($args) && count($args) > 0) {
		$url .= '?';
		$url .= http_build_query($args, '', '&');
	}
	return $url;
}

function u() {
	$page = func_get_arg(0);
	if (func_num_args() == 2) {
		$args = func_get_arg(1);
	} else {
		$args = NULL;
	}
	$url = url($page, $args);
	h($url);
}

function isAlnumOnly($string) {
	return preg_match('/^[[:alnum:]]+$/', $string);
}

function isNumberOnly($string) {
	return preg_match('/^[[:digit:]]+$/', $string);
}

function isBlank($string) {
	return strlen(trim($string)) == 0;
}

?>
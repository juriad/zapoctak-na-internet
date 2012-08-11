<?php

//this must have been included only once

try {
	$db = new PDO('sqlite:./internet.db');
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
} catch (PDOException $e) {
	die('Problem occured while connecting to database: ' . $e->getMessage());
}

function execute() {
	$st = func_get_arg(0);
	for ($i = 1, $j = 1; $i < func_num_args(); $i += 2, $j++) {
		if (!$st->bindValue($j, func_get_arg($i), func_get_arg($i + 1))) {
			return false;
		}
	}
	return $st->execute();
}

?>
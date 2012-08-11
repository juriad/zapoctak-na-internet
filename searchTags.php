<?php

require_once 'utils/initPage.php';
require_once 'classes/Tag.php';

$tags = Tag::getAllTagNames($db);

echo json_encode($tags);

?>
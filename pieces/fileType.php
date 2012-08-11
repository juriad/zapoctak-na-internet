<!-- requires $fileForType, $outputType (0 = nothing, 1 = text, 2 = icon, 3 = text and icon) -->
<?php
$fileTypeText = ($fileForType->isDeleted() ? "DELETED " : "")
		. ($fileForType->isDirectory() ? "DIR" : "FILE");
if ($outputType % 4 >= 2) {
	$fileName = ($fileForType->isDirectory() ? "folder" : "file")
			. ($fileForType->isDeleted() ? "-deleted" : "") . '.png';
?>
<img src='img/<?php h($fileName); ?>' alt='<?php h($fileTypeText); ?>'
	width='24px' height='24px' title='<?php h($fileTypeText); ?>'>
<?php
}
if ($outputType % 2 == 1) {
?>
<span class='fileTypeText'><?php h($fileTypeText); ?></span>
<?php
}
?>
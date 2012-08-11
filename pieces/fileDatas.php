<!-- requires $dataFile, $db -->
<?php
$fileDatas = $dataFile->fetchFileDatas($db);
if (count($fileDatas) == 0) {
?>
<ul class='fileDatas' id='fileData<?php echo $dataFile->getId(); ?>'>
	<li class='noFileDatas'>There are no data available</li>
<?php
} else {
?>
<ul class='fileDatas' id='fileData<?php echo $dataFile->getId(); ?>'>
<?php
	$i = 0;
	foreach ($fileDatas as $fileData) {
		$metadata = $fileData->getMetadata();
?>
	<li id='fileData<?php echo $dataFile->getId(); ?>fileData<?php echo $i; ?>'
		class='fileData<?php echo ($metadata->getType() == 'text' ? ' textFileData'
				: '')
					   ?>'>
		<div class='fileDataHeader'>
			<span class='metadataName'><?php h($metadata->getName()); ?></span>
			of type <span class='metadataType'><?php echo $metadata->getType(); ?></span>
		</div>
		<div class='fileDataBody'>
<?php
							   switch ($metadata->getType()) {
							   case "image":
								   list($width, $height, $type, $attr) = getimagesize(
										   $fileData->getFileName());
?>
			<img src='<?php u('download.php',
					array('root' => $_GET['root'], 'fileId' => $file->getId(),
							'data' => $fileData->getId()));
					  ?>'
				alt='<?php echo $metadata->getName(); ?>'
				width='<?php echo $width; ?>px'
				height='<?php echo $height; ?>px'>
<?php
								  break;
							  case "file":
?>
			<a href='<?php u('download.php',
					array('root' => $_GET['root'], 'fileId' => $file->getId(),
							'data' => $fileData->getId()));
					 ?>'>Download data file</a>
<?php
								 break;
							 case "text":
								 $tw = $fileData->fetchText($db);
?>
<pre class='<?php echo ($tw['wrap'] ? "preWrap" : "preScroll"); ?>'>
<?php h($tw['text']); ?>
</pre>
<?php
			break;
		default:
?>
			unknown type of data
<?php
			break;
		}
?>
		</div>
	</li>
<?php
		$i++;
	}
}
if (count($fileDatas) != 0 || !$readOnly) {
?>
</ul>
<?php
}
?>
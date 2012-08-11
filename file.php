<?php

require_once 'utils/initPage.php';
require_once 'utils/file.php';

$rootFile = getRootFile($_GET['root'], $db);
$file = getFile($_GET['id'], $rootFile, $_GET['root'], $db);

if ($file->isDeleted()) {
	saveMessage(new Message("This file is deleted", Message::SEVERITY_INFO));
}

$title = "File info in WebFS";
include 'pieces/page-start.php';
?>

<div id='navigation'>
<?php
$nav = $file->getAncestors($rootFile, $db);
for ($i = 0; $i < count($nav); $i += 2) {
?>
 / <a href='<?php u('files.php',
			array('root' => $_GET['root'], 'parent' => $nav[$i]));
			?>'>
<?php h($nav[$i + 1]); ?></a>
<?php
			}
?>
</div>

<div id='fileInfo'>
	<dl>
		<dt>Type</dt>
		<dd class='value'>
<?php
$fileForType = $file;
$outputType = 3;
include 'pieces/fileType.php';
?>
		</dd>

		<dt>First mention</dt>
		<dd class='value'><span data-time='<?php echo $file->getFirstMention(); ?>'
			class='time'><?php echo $file->getFirstMention(); ?></span></dd>

		<dt>Tags</dt>
		<dd>
<?php
$tagFile = $file;
$readOnly = false;
include 'pieces/tags.php';
?>
		</dd>

		<dt>Comments</dt>
		<dd>
<?php
$targetId = $file->getId();
$targetTable = 'files';
$readOnly = false;
include 'pieces/comments.php';
?>
		</dd>

		<dt>Rules</dt>
		<dd>
<?php
$ruleFile = $file;
$readOnly = false;
include 'pieces/rules.php';
?>
		</dd>
<?php
if (!$file->isDirectory() && !$file->isDeleted()) {
?>
		<dt>Download</dt>
		<dd><a href='<?php u('download.php',
			array('root' => $_GET['root'], 'fileId' => $file->getId(),
					'file' => 1));
								   ?>'>
		<?php h($file->getName()); ?></a>
		</dd>
<?php
								   } else {
?>
		<dt>Search</dt>
		<dd><a href='<?php u('searchSession.php',
			array('root' => $_GET['root'], 'ancestor' => $file->getId()));
					 ?>'>Search this directory</a>
		</dd>
<?php
					 }
?>
	</dl>
	<ul id='versions'>
<?php
$items = $file->fetchHistory($db)->getItems();
array_unshift($items, $file);
foreach ($items as $item) {
?>
		<li class='version <?php echo ($item instanceof File ? 'actualVersion'
			: 'historyVersion')
						   ?>'
			id='version<?php echo $item->getVersion(); ?>'>
			<dl>
				<dt>Name</dt>
				<dd class='value'>
					<span class='fileName'><?php h($item->getName()); ?></span>
				</dd>

				<dt>Size</dt>
				<dd class='value'>
					<span class='filesize'
						data-filesize='<?php echo $item->getLength(); ?>'>
						<?php echo $item->getHumanLength(); ?>
					</span>
				</dd>

				<dt>Modified</dt>
				<dd class='value'><span data-time='<?php echo $item
			->getModified();
												   ?>'
					class='time'><?php echo $item->getModified(); ?></span></dd>

				<dt>Mime type</dt>
				<dd class='value'><?php echo h($item->getMime()); ?></dd>

				<dt>Scanned</dt>
				<dd class='value'><span data-time='<?php echo $item->getScan(); ?>'
						class='time'><?php echo $item->getScan(); ?></span></dd>

				<dt>Parent</dt>
<?php
													   if ($item->getParent()) {
														   $parent = new File(
																   $item
																		   ->getParent(),
																   $db);
?>
				<dd>
					<a href='<?php u('file.php',
				array('root' => $_GET['root'], 'id' => $parent->getId()));
							 ?>'>
						<?php h($parent->getName()); ?></a>
<?php
								 } else {
?>
				<dd class='value'>
				this file is root
<?php
	}
?>
				</dd>

				<dt>Version</dt>
				<dd class='value'><?php echo $item->getVersion(); ?></dd>
<?php
	if (!($item instanceof File)) {
?>
				<dt>Comments</dt>
				<dd>
<?php
		$targetId = $item->getId();
		$targetTable = 'history';
		$readOnly = false;
		include 'pieces/comments.php';
?>
				</dd>
<?php
	}

	if (!$file->isDirectory()) {
?>
				<dt>Data</dt>
				<dd>
<?php
		$dataFile = $item;
		include 'pieces/fileDatas.php';
?>
				</dd>
<?php
	}
?>
			</dl>
		</li>
<?php
}
?>
	</ul>
</div>
<?php
include 'pieces/page-end.php';
?>
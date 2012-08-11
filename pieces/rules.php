<!-- requires $ruleFile, $readOnly, $db -->
<?php
$rules = $ruleFile->fetchRules(NULL, $db);
if (count($rules) == 0) {
	if (!$readOnly) {
?>
<ul class='rules' id='rules<?php echo $ruleFile->getId(); ?>'>
	<li class='noRules'>There are no rules assigned</li>
<?php
	}
} else {
?>
<ul class='rules' id='rules<?php echo $ruleFile->getId(); ?>'>
<?php
	$i = 0;
	$users = array($_SESSION['user']->getId() => $_SESSION['user']);
	foreach ($rules as $rule) {
?>
	<li id='rule<?php echo $ruleFile->getId(); ?>rule<?php echo $i; ?>'>
		<div class='ruleHeader'>
<?php
		if (!isset($users[$rule->getUserId()])) {
			$users[$rule->getUserId()] = new User($rule->getUserId(), $db);
		}
?>
			<span class='author'>
				<?php h($users[$rule->getUserId()]->getName()); ?></span>
			<span data-time='<?php echo $rule->getAdded(); ?>' class='time'>
				<?php echo $rule->getAdded(); ?></span>
<?php
		if ($rule->getUserId() == $_SESSION['user']->getId() && !$readOnly
				&& $rule->getFileId() == $ruleFile->getId()) {
?>
			<form id='removeRule<?php echo $ruleFile->getId(); ?>rule<?php echo $i; ?>'
				class='ruleForm removeRuleForm' method='post'
				action='<?php u('processRules.php'); ?>'>
				<input type="hidden" name='action' value='remove'>
				<input type="hidden" name='root' value='<?php echo $_GET['root']; ?>'>
				<input type="hidden" name='fileId'
					value='<?php echo $ruleFile->getId(); ?>'>
				<input type="hidden" name='ruleId'
					value='<?php echo $rule->getId(); ?>'>
				<input type="submit" value="x" class='removeButton'>
			</form>
<?php
		}
?>
		</div>
		<div class='ruleBody'>
			<dl>
				<dt>Name:</dt>
				<dd class='value'><?php h($rule->getName()); ?></dd>

				<dt>Criterion:</dt>
				<dd class='value'>
<?php
		if ($rule->getFileId() == $ruleFile->getId() && !$file->isDirectory()) {
?>
				This single file
<?php
		} else {
?>
				<?php h($rule->getCriterion()); ?>
					is like <?php h($rule->getValue()); ?>
<?php
		}
?>
				</dd>
<?php
		$targetFile = new File($rule->getFileId(), $db);
?>
				<dt>Target file</dt>
				<dd class='value'><a href='<?php u('file.php',
				array('root' => $_GET['root'], 'id' => $targetFile->getId()));
										   ?>'>
				<?php h($targetFile->getName()); ?></a>
<?php
												   if ($rule
														   ->isSubdirectories()) {
?>
					and its subdirectories
<?php
		}
?>
				</dd>

				<dt>Routine:</dt>
				<dd class='value'><?php h($rule->getRoutine($db)->getComment()); ?></dd>
<?php
		if ($rule->getFileId() == $ruleFile->getId()) {
?>
				<dt>Last applied:</dt>
				<dd class='value'>
<?php
			if (is_null($rule->getLastScan())) {
?>
					not yet
<?php
			} else {
?>
					<span data-time='<?php echo $rule->getLastScan(); ?>'
						class='time'><?php echo $rule->getLastScan(); ?></span>
<?php
			}
?>
				</dd>
<?php
		}
?>
			</dl>
		</div>
	</li>
<?php
		$i++;
	}
}
?>
<?php
if (!$readOnly) {
?>
	<li class='addNewRule' id='newRule<?php echo $ruleFile->getId(); ?>'>
		<form id='newRuleForm<?php echo $ruleFile->getId(); ?>'
			class='ruleForm newRuleForm' method='post'
			action='<?php u('processRules.php'); ?>'>
			<input type="hidden" name='action' value='add'>
			<input type="hidden" name='root' value='<?php echo $_GET['root']; ?>'>
			<input type="hidden" name='fileId'
				value='<?php echo $ruleFile->getId(); ?>'>
			<label for='newRuleName' class='leftLabel'>Name:</label>
				<input id='newRuleName' type="text" name='name'>
				<br>
<?php
	if ($ruleFile->isDirectory()) {
?>
			<label for='newRuleValue' class='leftLabel'>Criterion &amp; value:</label>
				<select name="criterion" id='newRuleCriterion'>
					<option value="MASK" selected="selected">MASK</option>
					<option value="IMASK">IMASK</option>
					<option value="MIME">MIME</option>
				</select>
				is like
				<input id='newRuleValue' type="text" name='value'>
				<br>
			<label for='newRuleSubdirectories' class='leftLabel'>Subdirectories:</label>
				<input id='newRuleSubdirectories' type="checkbox" name='subdirectories' checked="checked">
				<br>
<?php
	}
?>
			<label for='newRuleRoutine' class='leftLabel'>Routine:</label>
				<select name="routineId" id='newRuleRoutine'>
<?php
	foreach (Routine::getAllRoutines($db) as $routine) {
?>
					<option value='<?php echo $routine->getId(); ?>'>
						<?php h($routine->getComment()); ?></option>
<?php
	}
?>
				</select>
				<br>
			<input type="submit" value="Add">
		</form>
	</li>
<?php
}
if (count($rules) != 0 || !$readOnly) {
?>
</ul>
<?php
}
?>
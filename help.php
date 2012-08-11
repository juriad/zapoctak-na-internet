<?php

require_once 'utils/initPage.php';

$title = 'Help pages for WebFS';
include 'pieces/page-start.php';

?>
<h1>Help pages</h1>
<h2>General</h2>
<p>There are usually many files in tree struction and in various
	directories all over hard drives. Purpose of this application is to
	keep track of them and provide users a possibility to watch their
	files, search for them and access them in a simple and easy way.</p>
<h2>Users</h2>
<p>This aplication is not exposed to the big evil world ouside, so
	only chosen users are allowed to access it. If you can see this page,
	apperantly, you are one of them. Each user can configure his own
	presets, nowadays he can only change whether to allow a root or not.</p>
<p>User can have some roles, two of them in fact. One grantes him
	right to administer roots and their assigment to users and the other
	allows user to add or block users (even himself, be careful), he can
	also assign or remove roles.</p>
<h2>Roots</h2>
<p>Each tracked file tree is represented by its root. It is an
	ordinary directory which you mark as root of tree struction you are
	interested in. Application from time to time runs its crawler to fill
	new files or update current ones. Roots are entry points for crawler to
	file system, it will process all files in subdirectories.</p>
<p>Processing of root can be altered a little: you can specify how
	often or even whether you want to process each single root, changes of
	other attributes are not available, you should not need them. In
	critical cases contact your databasse admin.</p>
<p>Valid root can be assigned to ordinary users in its
	administration, until this moment, root is not available at all. If
	root is globaly deactivated, it is also shown as inactive for all
	users. Each user can set root activity particularly for himself.</p>
<p>There are two actions user can perform on roots, he can browse
	them or search them. Browsing is very similar to any other file
	manager. Searching provides far more possibilities than is usual, they
	will be described later.</p>
<h2>Files</h2>
<p>Files are not directly accessable like you are used to from other
	file managers. Instead their properties are shown and it should give
	you enough information to access the file in other file manager.</p>
<p>Each file have many information available about it. Most of them
	are common, but some are consequences of the basic architeecture of
	this application. Files are version, so will never lose information
	about any file, but you may lose the file easily, it is your
	responsibility.</p>
<p>Regular crawler scans gather as many information about file as
	possible. It tracks file name changes or even moves to different
	directory which is still inside watched roots. Each time any change is
	detected a new history record is saved and shown in file details.</p>
<p>You can assign tag to any file which can ease you finding it. Tag
	is simply a string assigned. File can have many tags and one tag can be
	assigned to many files. You will appreciate this during searching.</p>
<p>There is also another mechanism which helps you to keep track of
	files and versions, you can comment them. Either a history version or
	whole file. Unfortunately you cannot comment actual version. You can
	search comments fulltext while searching.</p>
<h2>Rules</h2>
<p>Rule is defined for directory and optionaly its subdirectories or
	a single file. It provides you a way how to automate some tasks related
	to files and their versions. Sometimes you want to see some preview,
	these can be automaticly generated always when the file changes.
	Particular possible routines are self explanatory. Rules cannot be
	deactivated, only removed.</p>
<p>MASK and IMASK requires wildcard mask, * stands for sequence of
	any characters and ? for some character. MIME requires part of mime
	type of searched file. Operator comma can be used to express
	disjunction.</p>
<h2>Search</h2>
<p>You can search any directory by many criteria. Search is
	accessable from list of roots, directory detail and directory listing.
	Nearly any attribute can be used as a criterion. Search criteria are
	remembered until next search session.</p>
<p>Some of them are special and require specific format and
	treatment. Name and mime use the same policy as IMASK in rules except
	for comma, no such operator exist here. You can force case sensitivity
	in name like criterion. Length can be a decimal number with decimal
	point with optional suffix specifying multiplier (KMGT). Version is
	plain nonnegative integer (oldest version is 0).</p>
<p>
	Modified and mention requires date in format yyyy-mm-dd (e.g.
	2012-03-01), clients with javascript have an advanced possibility of
	date selecting. Searching for tag requires full tag name, javascript
	users have autocomplete available. Comment is the most complex search
	criterion: it basicly support all features provided by <a
		href='http://www.sqlite.org/fts3.html'>FTS3</a>. Routine search
	returns only files where such routine has been defined, not all
	particular affected files.
</p>
<h2>Problems</h2>
<h3>Root became invalid</h3>
<p>This means root directory does not exist or it is not a directory
	or it has changed in some pretty bad way. You cannot control such root,
	but you can create a new one and ask database admin to handle the old
	one.</p>
<h3>I can't remove this or that</h3>
<p>Either you are not author or this action simply is not provided.</p>
<h2>Documentaion</h2>
<p>
	<a href='README.html'>Documentation</a> is also available, it describes
	how the application works and what technologies were used.
</p>
<?php
include 'pieces/page-end.php';
?>
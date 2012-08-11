#!/usr/bin/perl

use strict;
use warnings;

use feature qw( switch say);

use DBI;
use Data::Dumper;

use Log::Log4perl qw(:easy);
Log::Log4perl->easy_init(
	{ level => $DEBUG, file => ">internet.log", layout => "%d %p - %M: %m%n" }
);

use File::MimeInfo::Magic;

sub fileInfo {
	my ($path) = @_;
	my ( $isDirectory, $isDeleted, $inode, $modified, $length, $mime );
	if ( -f "$path" ) {
		$isDirectory = 0;
		$isDeleted   = 0;
	} elsif ( -d "$path" ) {
		$isDirectory = 1;
		$isDeleted   = 0;
	} elsif ( -l "$path" ) {
		WARN "TODO symlink";

		#TODO symlink
		$isDeleted = 1;    #ignore
	} else {
		$isDeleted = 1;
	}
	if ( !$isDeleted ) {
		my (
			$dev,  $ino,   $mode,  $nlink, $uid,     $gid, $rdev,
			$size, $atime, $mtime, $ctime, $blksize, $blocks
		) = stat($path);
		$inode    = $ino;
		$modified = $mtime;
		$length   = $size;
		$mime     = mimetype($path);
	}

	return ( $isDirectory, $isDeleted, $inode, $modified, $length, $mime );
}

sub markAsDeleted {
	my ( $dbh, $id, $scanId ) = @_;
	my $updateDeleted =
	  $dbh->prepare("UPDATE files SET isDeleted = 1, scanId = ? WHERE id = ?");
	$updateDeleted->execute( $scanId, $id );
	DEBUG "marked as deleted file with id $id";
	my $selectChildren =
	  $dbh->prepare("SELECT id FROM files WHERE parent = ? AND isDeleted = 0");
	$selectChildren->execute($id);
	my $children = $selectChildren->fetchall_arrayref( {} );
	for my $child (@$children) {
		markAsDeleted( $dbh, $child->{id}, $scanId );
	}
}

sub processDirectory {
	my ( $dbh, $parentId, $path, $scanId ) = @_;

	DEBUG "scanning directory $path";

	my $fs = $dbh->selectall_arrayref(
		"SELECT * FROM files WHERE isDeleted = 0 AND parent = ?",
		{ Slice => {} }, $parentId );
	my %files;
	for my $file (@$fs) {
		$files{ $file->{name} } = $file;
	}
	my $dir;
	if ( !opendir $dir, $path ) {
		ERROR "cannot scan directory $path";
		$dbh->do( "INSERT INTO errors (action, message, data) VALUES (?, ?, ?)",
			undef, "scan", "cannot open directory", "$path" );
		return 1;
	}

	my $insertNew = $dbh->prepare(
"INSERT INTO files (version, name, isDirectory, isDeleted, inode, modified, length, mime, scanId, parent, realPath)
	VALUES (0, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?)"
	);
	my $updateExisting = $dbh->prepare(
		"UPDATE files SET modified = ?, length = ?, mime = ?, scanId = ? WHERE id = ?");

	my @files = grep { !/^\./ } readdir $dir;
	for my $file (@files) {
		my ( $isDirectory, $isDeleted, $inode, $modified, $length, $mime ) =
		  fileInfo( $path . "/" . $file );
		my $fileId;
		if ( exists $files{$file} && $inode == $files{$file}->{inode} ) {
			$fileId = $files{$file}->{id};
			my %f = %{ $files{$file} };
			if ( $f{modified} != $modified || $f{length} != $length ) {
				$updateExisting->execute( $modified, $length, $mime, $scanId,
					$fileId );
				DEBUG "updated file info for $path/$file";
			}
			delete $files{$file};
		} elsif ( !$isDeleted ) {
			$insertNew->execute( $file, $isDirectory, $inode, $modified,
				$length, $mime, $scanId, $parentId, "$path/$file" );
			$fileId = $dbh->last_insert_id( undef, undef, undef, undef );
			DEBUG "inserted new file $path/$file";
		}

		if ($isDirectory) {
			processDirectory( $dbh, $fileId, "$path/$file", $scanId );
		}
	}
	closedir $dir;

	for my $file ( keys %files ) {
		markAsDeleted( $dbh, $files{$file}->{id}, $scanId );
		DEBUG "marked as deleted file $path/$file and its children";
	}
}

use File::Basename;

sub processRoot {
	my ( $dbh, $root, $scanId ) = @_;

	if ( defined $root->{lastScanId} ) {
		my $lastScan = $dbh->selectrow_hashref(
			"SELECT strftime('%s', time) as time FROM scans WHERE id = ?",
			undef, $root->{lastScanId} );

		if ( time() <= $lastScan->{time} + $root->{scanInterval} ) {
			INFO "root $root->{name} has been scaned recently";
			return 1;
		}
	}
	INFO "will scan root $root->{name} for updates";

	if ( !-d "$root->{path}" ) {
		ERROR "root file directory $root->{path} does not exist";
		$dbh->do(
			"INSERT INTO errors (action, message, data) VALUES (?, ?, ?)",
			undef, "scan", "root file directory does not exist",
			$root->{path}
		);
		INFO "disabling root";
		$dbh->do( "UPDATE roots SET state = -1 WHERE id = ?",
			undef, $root->{id} );
		return 2;
	}

	my $rootFileId;
	if ( !defined $root->{rootFile} ) {
		my $name = basename( $root->{path} );
		my ( $isDirectory, $isDeleted, $inode, $modified, $length, $mime ) =
		  fileInfo( $root->{path} );
		$dbh->do(
"INSERT INTO files (version, name, isDirectory, isDeleted, inode, modified, length, mime, scanId, realPath)
		VALUES (0, ?, 1, 0, ?, ?, ?, ?, ?, ?)",
			undef, $name, $inode, $modified, $length, $mime, $scanId, $root->{path}
		);
		$rootFileId = $dbh->last_insert_id( undef, undef, undef, undef );
		$dbh->do( "UPDATE roots SET rootFile = ? WHERE id = ?",
			undef, $rootFileId, $root->{id} );
		INFO "new root file has been added";
	} else {
		my $rootFile = $dbh->selectrow_hashref(
			"SELECT * FROM files WHERE id = $root->{rootFile}");
		$rootFileId = $rootFile->{id};
		my ( $isDirectory, $isDeleted, $inode, $modified, $length, $mime ) =
		  fileInfo( $root->{path} );
		if ( $inode != $rootFile->{inode} ) {
			ERROR
"root file directory inode has changed, this is unacceptable, disabling root";
			$dbh->do( "UPDATE roots SET state = -2 WHERE id = ?",
				undef, $root->{id} );
			return 3;
		}
		if (   $rootFile->{modified} != $modified
			|| $rootFile->{length} != $length )
		{
			INFO "updating root file information for $root->{name}";
			$dbh->do(
"UPDATE files SET modified = ?, length = ?, mime = ?, scanId = ? WHERE id = ?",
				undef, $modified, $length, $mime, $scanId, $rootFileId
			);
		}
	}

	processDirectory( $dbh, $rootFileId, $root->{path}, $scanId );

	$dbh->do( "UPDATE roots SET lastScanId = ? WHERE id = ?",
		undef, $scanId, $root->{id} );
	INFO "scan of root $root->{name} has been completed";
}

sub detectMoves {
	my ( $dbh, $scanId ) = @_;
	INFO "detecting moves during last scan";
	my $moves = $dbh->selectall_arrayref(
		"SELECT f1.id AS oldId, f2.id AS newId FROM files f1, files f2
		WHERE f1.inode = f2.inode AND f1.id != f2.id AND f1.isDeleted = 1
		AND f2.version = 0 AND f1.scanId = ? AND f2.scanId = ?",
		{ Slice => {} }, $scanId, $scanId
	);
	my $newFile         = $dbh->prepare("SELECT * FROM files WHERE id = ?");
	my $moveInformation = $dbh->prepare(
"UPDATE files SET name = ?, isDeleted = 0, modified = ?, length = ?, parent = ?, realPath = ? WHERE id = ?"
	);
	my $moveChildrenToNew =
	  $dbh->prepare("UPDATE files SET parent = ? WHERE parent = ?");
	my $deleteMoved = $dbh->prepare("DELETE FROM files WHERE id = ?");
	for my $move (@$moves) {
		my $old = $move->{oldId};
		my $new = $move->{newId};
		$newFile->execute($new);
		my $nFile = $newFile->fetchrow_hashref();
		$moveInformation->execute( $nFile->{name}, $nFile->{modified},
			$nFile->{length}, $nFile->{parent}, $nFile->{realPath}, $old );
		$moveChildrenToNew->execute( $old, $new );
		$deleteMoved->execute($new);
		DEBUG "move detected: $old -> $new";
	}
	INFO "all moves has been processed";
}

sub process {
	my $dbfile = 'internet.db';
	my $dbh =
	  DBI->connect( "dbi:SQLite:dbname=$dbfile", "", "",
		{ RaiseError => 1, AutoCommit => 1 } )
	  or die $DBI::errstr;

	$dbh->do("INSERT INTO scans DEFAULT VALUES");
	my $scanId = $dbh->last_insert_id( undef, undef, undef, undef );
	my $roots = $dbh->selectall_arrayref( "SELECT * FROM roots WHERE state = 1",
		{ Slice => {} } );

	for my $root (@$roots) {
		$dbh->begin_work();
		processRoot( $dbh, $root, $scanId );
		$dbh->commit();
	}

	$dbh->begin_work();
	detectMoves( $dbh, $scanId );
	$dbh->commit();

	$dbh->begin_work();
	runRoutines( $dbh, $scanId );
	$dbh->commit();

	$dbh->disconnect();
}

use lib '.';
use Routines;

process();


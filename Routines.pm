package Routines;

use strict;
use warnings;
use feature qw(switch say);
use Data::Dumper;

use Exporter;
use vars qw(@ISA @EXPORT);
@ISA    = qw(Exporter);
@EXPORT = qw(runRoutines);

use Regexp::Wildcards;
my $rw = Regexp::Wildcards->new( do => [qw(jokers brackets)] );
use File::MimeInfo::Magic;
use File::Basename;
use File::Copy;
use File::Slurp::Unicode;
use File::Temp qw(tmpnam);

use Log::Log4perl qw(:easy);

use Text::CSV::Encoded coder_class => 'Text::CSV::Encoded::Coder::EncodeGuess';
use Text::SimpleTable;

use IPC::Run3;

sub formatToAsciiTable {
	my ($file) = @_;

	my @rows;
	my $csv = Text::CSV::Encoded->new;
	$csv->encoding(undef);
	if ( !$csv ) {
		return;
	}
	my $fh;
	open $fh, "<", $file or $fh = undef;
	if ( !$fh ) {
		return;
	}
	my $table = undef;

	while ( my $row = $csv->getline($fh) ) {
		if ( !defined $table ) {
			my @header = (15) x ( scalar @$row );
			$table = Text::SimpleTable->new(@header);
		} else {
			$table->hr;
		}
		$table->row(@$row);
	}
	return $table->draw;
}

sub backupFile {
	my ( $dbh, $routine, $file ) = @_;
	my $insert = $dbh->prepare(
		'INSERT INTO files_metadata (fileId, version, metadataId) VALUES
		(?, (SELECT version	FROM files WHERE id = ?), ?)'
	);
	$insert->execute( $file->{id}, $file->{id}, $routine->{metadataId} );
	my $id = $dbh->last_insert_id( undef, undef, undef, undef );
	my $exit = copy( $file->{realPath}, "/var/internet/backup$id" );
	if ( !$exit ) {
		ERROR "error copying file";
		my $delete = $dbh->prepare('DELETE FROM files_metadata WHERE id = ?');
		$delete->execute($id);
		$dbh->do(
			"INSERT INTO errors (action, message, data) VALUES (?, ?, ?)",
			undef, "copy", "cannot copy file",
			$file->{realPath}
		);
		return 0;
	} else {
		my $update =
		  $dbh->prepare('UPDATE files_metadata SET fileName = ? WHERE id = ?');
		$update->execute( "/var/internet/backup$id", $id );
		return 1;
	}
}

sub imageThumbnail {
	my ( $dbh, $routine, $file ) = @_;
	my $insert = $dbh->prepare(
		'INSERT INTO files_metadata (fileId, version, metadataId) VALUES
		(?, (SELECT version	FROM files WHERE id = ?), ?)'
	);
	$insert->execute( $file->{id}, $file->{id}, $routine->{metadataId} );
	my $id = $dbh->last_insert_id( undef, undef, undef, undef );
	run3 ["convert", "$file->{realPath}", "-resize", "400x400", "/var/internet/image$id"], \undef, \undef, \undef;
	my $exit = $?;
	if ($exit) {
		ERROR "error converting file";
		my $delete = $dbh->prepare('DELETE FROM files_metadata WHERE id = ?');
		$delete->execute($id);
		$dbh->do(
			"INSERT INTO errors (action, message, data) VALUES (?, ?, ?)",
			undef, "convert", "cannot convert file",
			$file->{realPath}
		);
		return 0;
	} else {
		my $update =
		  $dbh->prepare('UPDATE files_metadata SET fileName = ? WHERE id = ?');
		$update->execute( "/var/internet/image$id", $id );
		return 1;
	}
}

sub videoThumbnail {
	my ( $dbh, $routine, $file ) = @_;
	my $insert = $dbh->prepare(
		'INSERT INTO files_metadata (fileId, version, metadataId) VALUES
		(?, (SELECT version	FROM files WHERE id = ?), ?)'
	);
	$insert->execute( $file->{id}, $file->{id}, $routine->{metadataId} );
	my $id = $dbh->last_insert_id( undef, undef, undef, undef );

	run3 ["ffmpegthumbnailer", "-i", "$file->{realPath}", "-o", "/var/internet/video$id", "-c", "jpg", "-s", "400", "-f"], \undef, \undef, \undef;
	my $exit = $?;
	if ($exit) {
		ERROR "error ffmpegthumbnailing file";
		my $delete = $dbh->prepare('DELETE FROM files_metadata WHERE id = ?');
		$delete->execute($id);
		$dbh->do(
			"INSERT INTO errors (action, message, data) VALUES (?, ?, ?)",
			undef,
			"ffmpegthumbnailer",
			"cannot ffmpegthumbnailing file",
			$file->{realPath}
		);
		return 0;
	} else {
		my $update =
		  $dbh->prepare('UPDATE files_metadata SET fileName = ? WHERE id = ?');
		$update->execute( "/var/internet/video$id", $id );
		return 1;
	}
}

sub documentPreview {
	my ( $dbh, $routine, $file ) = @_;
	my $insert = $dbh->prepare(
		'INSERT INTO files_metadata (fileId, version, metadataId) VALUES
		(?, (SELECT version	FROM files WHERE id = ?), ?)'
	);
	$insert->execute( $file->{id}, $file->{id}, $routine->{metadataId} );
	my $id = $dbh->last_insert_id( undef, undef, undef, undef );

	my $fname = File::Temp::tmpnam();

	run3 ["unoconv", "-o", "$fname", "-f", "txt", "$file->{realPath}"], \undef, \undef, \undef;
	my $exit = $?;
	if ($exit) {
		unlink $fname;
		ERROR "error unoconving document file";
		my $delete = $dbh->prepare('DELETE FROM files_metadata WHERE id = ?');
		$delete->execute($id);
		$dbh->do(
			"INSERT INTO errors (action, message, data) VALUES (?, ?, ?)",
			undef,
			"unoconv document",
			"cannot unoconving file",
			$file->{realPath}
		);
		return 0;
	} else {
		my $text = read_file($fname);
		unlink $fname;
		if ( !defined $text ) {
			ERROR "error reading file";
			my $delete =
			  $dbh->prepare('DELETE FROM files_metadata WHERE id = ?');
			$delete->execute($id);
			$dbh->do(
				"INSERT INTO errors (action, message, data) VALUES (?, ?, ?)",
				undef,
				"unoconv document",
				"cannot read temp file",
				$file->{realPath}
			);
			return 0;
		} else {
			$dbh->do(
				"INSERT INTO textData (id, content, wrap) VALUES (?, ?, ?)",
				undef, $id, $text, 1 );
			return 1;
		}
	}
}

sub spreadsheetPreview {
	my ( $dbh, $routine, $file ) = @_;
	my $insert = $dbh->prepare(
		'INSERT INTO files_metadata (fileId, version, metadataId) VALUES
		(?, (SELECT version	FROM files WHERE id = ?), ?)'
	);
	$insert->execute( $file->{id}, $file->{id}, $routine->{metadataId} );
	my $id = $dbh->last_insert_id( undef, undef, undef, undef );

	my $fname = File::Temp::tmpnam();

	run3 ["unoconv", "-o", "$fname", "-f", "csv", "$file->{realPath}"], \undef, \undef, \undef;
	my $exit = $?;
	if ($exit) {
		unlink $fname;
		ERROR "error unoconving spreadsheet file";
		my $delete = $dbh->prepare('DELETE FROM files_metadata WHERE id = ?');
		$delete->execute($id);
		$dbh->do(
			"INSERT INTO errors (action, message, data) VALUES (?, ?, ?)",
			undef,
			"unoconv spreadsheet",
			"cannot unoconving file",
			$file->{realPath}
		);
		return 0;
	} else {
		my $table = formatToAsciiTable($fname);
		unlink $fname;
		if ( !defined $table ) {
			ERROR "error formating csv file";
			my $delete =
			  $dbh->prepare('DELETE FROM files_metadata WHERE id = ?');
			$delete->execute($id);
			$dbh->do(
				"INSERT INTO errors (action, message, data) VALUES (?, ?, ?)",
				undef,
				"unoconv spreadsheet",
				"cannot format csv file",
				$file->{realPath}
			);
		} else {
			$dbh->do(
				"INSERT INTO textData (id, content, wrap) VALUES (?, ?, ?)",
				undef, $id, $table, 0 );
			return 1;
		}
	}
}

sub textContent {
	my ( $dbh, $routine, $file ) = @_;
	my $insert = $dbh->prepare(
		'INSERT INTO files_metadata (fileId, version, metadataId) VALUES
		(?, (SELECT version	FROM files WHERE id = ?), ?)'
	);
	$insert->execute( $file->{id}, $file->{id}, $routine->{metadataId} );
	my $id = $dbh->last_insert_id( undef, undef, undef, undef );

	my $text = read_file( $file->{realPath} );

	if ( !defined $text ) {
		ERROR "error reading file";
		my $delete = $dbh->prepare('DELETE FROM files_metadata WHERE id = ?');
		$delete->execute($id);
		$dbh->do(
			"INSERT INTO errors (action, message, data) VALUES (?, ?, ?)",
			undef, "read_file", "cannot read file",
			$file->{realPath}
		);
		return 0;
	} else {
		$dbh->do( "INSERT INTO textData (id, content, wrap) VALUES (?, ?, ?)",
			undef, $id, $text, 1 );
		return 1;
	}
}

sub runRoutine {
	my ( $dbh, $scanId, $routine, $file ) = @_;
	DEBUG
	  "running routine $routine->{id} ($routine->{name}) on $file->{realPath}";
	given ( $routine->{id} ) {
		when (1) {
			return backupFile( $dbh, $routine, $file );
		}
		when (2) {
			return imageThumbnail( $dbh, $routine, $file );
		}
		when (3) {
			return videoThumbnail( $dbh, $routine, $file );
		}
		when (4) {
			return documentPreview( $dbh, $routine, $file );
		}
		when (5) {
			return spreadsheetPreview( $dbh, $routine, $file );
		}
		when (6) {
			return textContent( $dbh, $routine, $file );
		}
		default {
			WARN "not implemented routine";
			return 0;
		}
	}
}

sub testMask {
	my ( $name, $mask ) = @_;
	my $re = $rw->convert($mask);
	return $name =~ qr(^$re$) ? 1 : 0;
}

sub testIMask {
	my ( $name, $imask ) = @_;
	my $re = $rw->convert($imask);
	return $name =~ qr(^$re$)i ? 1 : 0;
}

sub testMime {
	my ( $realPath, $mime ) = @_;
	my $mimeType = mimetype($realPath);

	for my $m ( split ',', $mime ) {
		return 1 if ( $mimeType =~ qr($m)i );
	}
	return 0;
}

sub runRuleOnFile {
	my ( $dbh, $scanId, $rule, $routine, $file ) = @_;
	given ( $rule->{criterion} ) {
		when (/mask/i) {
			return 0
			  unless testMask( basename( $file->{realPath} ), $rule->{value} );
		}
		when (/imask/i) {
			return 0
			  unless testIMask( basename( $file->{realPath} ), $rule->{value} )
			;
		}
		when (/mime/i) {
			return 0 unless testMime( $file->{realPath}, $rule->{value} );
		}
		when (/true/i) {

			# always
		}
	}
	return runRoutine( $dbh, $scanId, $routine, $file );
}

sub runRule {
	my ( $dbh, $scanId, $rule, $routine ) = @_;
	DEBUG "probing rule $rule->{name}";
	my $fileId     = $rule->{fileId};
	my $lastScanId = $rule->{lastScanId};
	my $filesSelect;
	if ( $rule->{subdirectories} == 1 ) {
		$filesSelect = $dbh->prepare(
			"SELECT id, realPath FROM files WHERE isDirectory = 0
			AND isDeleted = 0
			AND path LIKE (SELECT path FROM files WHERE id = ?) || '%'"
			  . ( defined $lastScanId ? " AND scanId = ?" : "" )
		);
		if ( defined $lastScanId ) {
			$filesSelect->execute( $fileId, $scanId );
		} else {
			$filesSelect->execute($fileId);
		}
	} else {
		$filesSelect = $dbh->prepare(
			"SELECT id, realPath FROM files WHERE isDirectory = 0
			AND (id = ? OR parent = ?) AND isDeleted = 0"
			  . ( defined $lastScanId ? " AND scanId = ?" : "" )
		);
		if ( defined $lastScanId ) {
			$filesSelect->execute( $fileId, $fileId, $scanId );
		} else {
			$filesSelect->execute( $fileId, $fileId );
		}
	}
	my $files = $filesSelect->fetchall_arrayref( {} );
	my $applications = 0;
	for my $file (@$files) {

		if ( runRuleOnFile( $dbh, $scanId, $rule, $routine, $file ) ) {
			$applications++;
		}
	}
	return $applications;
}

sub runRoutines {
	my ( $dbh, $scanId ) = @_;
	INFO "running routines for changed files";

	my $routinesSelect =
	  $dbh->prepare('SELECT id, name, metadataId FROM routines');
	$routinesSelect->execute();
	my %routines;
	my $routines = $routinesSelect->fetchall_arrayref( {} );
	for my $rule (@$routines) {
		$routines{ $rule->{id} } = $rule;
	}

	my $rulesSelect = $dbh->prepare(
"SELECT id, name, criterion, value, routineId, lastScanId, fileId, subdirectories FROM rules"
	);
	$rulesSelect->execute();
	my $rules = $rulesSelect->fetchall_arrayref( {} );
	for my $rule (@$rules) {
		my $applications =
		  runRule( $dbh, $scanId, $rule, $routines{ $rule->{routineId} } );
		my $refreshRule =
		  $dbh->prepare("UPDATE rules SET lastScanId = ? WHERE id = ?");
		$refreshRule->execute( $scanId, $rule->{id} );
	}
	INFO "all routines has been done";
}

1;

<?php
	$component = $argv[1];
	$rev =       $argv[2];

	$list = split( "\n", `svn log -r "$rev":HEAD trunk/$component | egrep "^r"` );

	unlink( '/tmp/log' );

	foreach ( $list as $entry )
	{
		preg_match( '@^r([0-9]+)@', $entry, $m );
		if ( $m[1] )
		{
			$rev = $m[1];
			$pRev = $rev - 1;
			`svn log -r $rev >> /tmp/log`;
			`svn diff -r $pRev:$rev | colordiff >> /tmp/log`;
			`svn diff -r $pRev:$rev > /tmp/patch-r$rev.diff.txt`;
		}
	}

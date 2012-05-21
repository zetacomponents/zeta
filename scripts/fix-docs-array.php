<?php
	$filename = $argv[1];

	if ( !file_exists( $filename )) 
	{
		die(1);
	}
	$f = file( $filename );
	$lines = '';
	foreach ( $f as $line )
	{
		if ( strstr( $line, 'function' ) !== false && strstr( $line, ' array ' ) !== false && strstr( $line, '*') == false )
		{
			$line = str_replace( ' array ', ' ', $line );
		}
		$lines .= $line;
	}
	file_put_contents( $filename, $lines );
?>

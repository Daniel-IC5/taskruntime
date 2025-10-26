#!/usr/bin/env php
<?php
declare(strict_types=1);

/* Daniels lazy IC5 framework loader */
$initPath = 'init.php';
$dir = __DIR__;
while( !file_exists( $dir . '/' . $initPath ) ) {
	$parent = dirname( $dir );
	if( $parent === $dir ) {
		break;
	}
	$dir = $parent;
}
if( file_exists( $dir . '/' . $initPath ) ) {
	require_once $dir . '/' . $initPath;
}

function getHumanReadableTime( string $duration ): string
{
	$lang = \IPS\Lang::load(\IPS\Lang::defaultLanguage());
	$interval = new DateInterval( $duration );
	$return = [];
	foreach( ['y' => 'years', 'm' => 'months', 'd' => 'days', 'h' => 'hours', 'i' => 'minutes', 's' => 'seconds'] as $k => $v )
	{
		if( $interval->$k )
		{
			$return[] = $lang->addToStack( 'every_x_'.$v, FALSE, ['pluralize' => [$interval->format( '%'.$k )]] );
		}
	}

	$return = $lang->formatList( $return );
	$lang->parseOutputForDisplay( $return );
	return $return;
}

$updatedTasks = 0;
$apps = glob( $dir . '/applications/*', GLOB_ONLYDIR );

foreach( $apps as $app )
{
	$tasksJsonPath = "$app/data/tasks.json";
	if( !file_exists( $tasksJsonPath ) )
		continue;

	$tasks = json_decode( file_get_contents( $tasksJsonPath ), TRUE );
	if( !is_array( $tasks ) )
		continue;

	foreach( glob( "$app/tasks/*.php" ) as $phpFile )
	{
		$content = file_get_contents( $phpFile );
		$lines = explode( "\n", $content );

		// Find class name and line
		$className = NULL;
		$classLine = NULL;
		foreach( $lines as $i => $line )
		{
			if( preg_match( '/^\s*class\s+([a-zA-Z0-9_]+)/', $line, $m ) )
			{
				$className = $m[ 1 ];
				$classLine = $i;
				break;
			}
		}
		if( !$className || !isset( $tasks[ $className ] ) )
			continue;

		$human = getHumanReadableTime( $tasks[ $className ] );
		// Find docblock above class
		$docStart = NULL;
		$docEnd = NULL;
		for( $i = $classLine - 1; $i >= 0; $i-- )
		{
			if( preg_match( '/^\s*\/\*\*/', $lines[ $i ] ) )
			{
				$docStart = $i;
				// Find end of docblock
				$j = $i;
				while( $j < $classLine && strpos( $lines[ $j ], '*/' ) === FALSE )
				{
					$j++;
				}
				$docEnd = $j;
				break;
			}
		}

		if( $docStart !== NULL && $docEnd !== NULL )
		{
			// Update or insert Task frequency line
			$found = FALSE;
			for( $i = $docStart; $i <= $docEnd; $i++ )
			{
				if( strpos( $lines[ $i ], 'Task frequency:' ) !== FALSE )
				{
					$lines[ $i ] = ' * Task frequency: '.$human;
					$found = TRUE;
					break;
				}
			}
			if( !$found )
			{
				// Insert before closing */
				for( $i = $docEnd; $i >= $docStart; $i-- )
				{
					if( strpos( $lines[ $i ], '*/' ) !== FALSE )
					{
						array_splice( $lines, $i, 0, ' * Task frequency: '.$human );
						break;
					}
				}
			}
			$updatedTasks++;
			file_put_contents( $phpFile, implode( "\n", $lines ) );
		}
	}
}

echo sprintf( "updated %s tasks", $updatedTasks);
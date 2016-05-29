<?php
define('FILE_SERVERS',     'servers.txt');
define('FILE_TIMESTAMP', 'timestamp.txt');
define('FILE_COUNTER',     'counter.txt');
define('FILE_LOG',            'logs.txt');

define('PATTERN_SERVER', '/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3}):(\d{1,5})/');
define('PATTERN_TIMESTAMP', '/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3}):(\d{1,5}) lastheartbeat=(\d{10,})\s$/i');

define('EXPIRATION_TIME', 30);

// don't know, which one to use for now for the future...
define('NL_WINDOWS', "\r\n"); /* => */ define('NL', NL_WINDOWS);
define('NL_UNIX', "\n");
define('NL_MAC', "\r");


// Important functions
function isStillRegistered()
{
	global $current_server;

	echo "->isStillRegistered()\n";

	$file = openFile(FILE_SERVERS, 'r');

	echo "Accessing file " . FILE_SERVERS . ", read each line until found server, otherwise return false.\n\n";
	$i = 0;
	while (!feof($file)) {
		$line = fgets($file, 1024);
		if (preg_match(PATTERN_SERVER, $line, $array_found)) {
			$found_server = $array_found[0];

			echo "[$i] Listed IP: $found_server";

			if ($current_server == $found_server) {
				echo
					" => Server has been found in list.\n" .
					"Don't add it to list.\n\n";
				return true;
			}
			echo "\n";
		}
		$i++;
	}
	fclose($file);

	echo
		"Server $current_server is new.\n" .
		"Add it to list.\n\n";
	return false;
}

/**
 *
 * @global type $query_string
 */
function addServer()
{
	global $query_string;

	echo "->addServer()\n";

	$file = fopen(FILE_SERVERS, "a");
	fputs($file, $query_string . NL);
	fclose($file);
}

function createTimeStamp()
{
	global $current_server;

	echo "->createTimeStamp()\n";

	$file = fopen(FILE_TIMESTAMP, 'a');
	fputs($file, $current_server . ' lastheartbeat=' . (!isset($_POST['remove']) ? time() : 0) . NL);
	fclose($file);
}

function refreshServer()
{
	global $current_server;
	global $query_string;

	echo "->refreshServer()\n";

	$file = openFile(FILE_SERVERS, 'r+');

	echo "Accessing file " . FILE_SERVERS . ", read each line until found server, otherwise return false.\n\n";
	$i = 0;
	while (!feof($file)) {
		$query_line = fgets($file, 1024);
		if (preg_match(PATTERN_SERVER, $query_line, $array_found)) {
			$found_server = $array_found[0];

			echo "[$i] Listed IP: $found_server";

			if ($current_server == $found_server) {
				echo
					" => Found old server.\n" .
					"Updating to current data...\n\n";

				rewind($file);
				for ($j = 0; $j < $i; $j++)
					fgets($file, 1024);

				fputs($file, $query_string . NL);
				fclose($file);
				return;
			}
			echo "\n";
		}
		$i++;
	}
	echo "Did not find the server! Create a new one...";
	addServer();

	fclose($file);
}

function updateTimeStamp()
{
	global $current_server;

	echo "->updateTimeStamp()\n";

	$file = openFile(FILE_TIMESTAMP, 'r+');

	echo "Accessing file " . FILE_TIMESTAMP . ", read each line until found server, otherwise return false.\n\n";
	$i = 0;
	while (!feof($file)) {
		$query_line = fgets($file, 1024);
		if (preg_match(PATTERN_TIMESTAMP, $query_line, $array_found)) {
			$found_server = "$array_found[1].$array_found[2].$array_found[3].$array_found[4]:$array_found[5]";
			$found_timestamp = $array_found[6];

			echo "[$i] Listed IP: $found_server Time stamp: $found_timestamp";

			if ($current_server == $found_server) {
				echo
					" => Found old server timestamp.\n" .
					"Updating to current UNIX time (" . time() . ")...\n\n";

				rewind($file);
				for ($j = 0; $j < $i; $j++)
					fgets($file, 1024);

				fputs($file, "$found_server lastheartbeat=" . (!isset($_POST['remove']) ? time() : 0));
				fclose($file);
				return;
			}
			echo "\n";
		}
		$i++;
	}
	echo "Did not find current time stamp! Create a new one...";
	createTimeStamp();

	fclose($file);
}

function cleanServerList()
{
	echo "->cleanServerList()\n";

	$lines_servers = file(FILE_SERVERS);
	$lines_timestamp = file(FILE_TIMESTAMP);

	echo 'Number of lines (servers.txt): ' . count($lines_servers) . "\n";
	echo 'Number of lines (timestamp.txt): ' . count($lines_timestamp) . "\n\n";

	$j = 0; $l = 0;
	for ($i = 0; $i < count($lines_servers); $i++) {
		echo "[$i]";

		if (preg_match(PATTERN_SERVER, $lines_servers[$i], $array_found_servers)) {
			echo " Found $lines_servers[$i]";

			$current_server = $array_found_servers[0];

			for ($k = 0; $k < count($lines_timestamp); $k++) {
				if (preg_match(PATTERN_TIMESTAMP, $lines_timestamp[$k], $array_found_timestamp)) {
					$current_timestamp_server = "$array_found_timestamp[1].$array_found_timestamp[2].$array_found_timestamp[3].$array_found_timestamp[4]:$array_found_timestamp[5]";

					if ($current_timestamp_server == $current_server) {
						$input_lines_servers[$j] = $lines_servers[$i];
						$input_lines_timestamp[$l] = $lines_timestamp[$k];
						$l++;
					}
				}
			}
			$j++;
		}
	}
	/*$input_lines_servers = array_unique($input_lines_servers);
	$input_lines_timestamp = array_unique($input_lines_timestamp);*/
	echo "\n\n";

	$file_servers = openFile(FILE_SERVERS, 'w');
	$file_timestamp = openFile(FILE_TIMESTAMP, 'w');
	for ($m = 0; $m < count($input_lines_servers); $m++) {
		echo "\$input_lines_servers[$m]: $input_lines_servers[$m]";
		fputs($file_servers, $input_lines_servers[$m]);
	}
	echo "\n";
	for ($n = 0; $n < count($input_lines_timestamp); $n++) {
		echo "\$input_lines_timestamp[$n]: $input_lines_timestamp[$n]";
		fputs($file_timestamp, $input_lines_timestamp[$n]);
	}
	fclose($file_servers);
	fclose($file_timestamp);

	echo "\n";
}

function removeExpiredServers()
{
	echo "->removeExpiredServers()\n";

	$file = openFile(FILE_TIMESTAMP, 'r+');

	echo "Accessing file " . FILE_TIMESTAMP . ", read each line until found server, otherwise return false.\n\n";

	$time = time();
	echo "Current UNIX time: $time\n";
	echo "Current UNIX time + 30: " . ($time + 30) . "\n";

	$i = 0;
	while (!feof($file)) {
		$query_line = fgets($file, 1024);
		if (preg_match(PATTERN_TIMESTAMP, $query_line, $array_found)) {
			$found_server = "$array_found[1].$array_found[2].$array_found[3].$array_found[4]:$array_found[5]";
			$found_timestamp = $array_found[6];

			echo "[$i] Listed IP: $found_server Time stamp: $found_timestamp";

			if ($time > $found_timestamp + 30) {
				echo " => Expired\n\n";

				removeLine(FILE_SERVERS, $i);
				removeLine(FILE_TIMESTAMP, $i);
				/*removeServer($server);*/

			}
			echo "\n";
		}
		$i++;
	}
	fclose($file);
}

/* UNUSED
function removeServer($server_to_be_removed)
{
	echo "->removeServers()\n";

	$file = openFile(FILE_SERVERS, 'r+');

	while (!feof($file)) {
		$query_line = fgets($file, 1024);
		if (preg_match(PATTERN_SERVER, $query_line, $found)) {
			$registered_server = $found[0];

			if ($registered_server == $server_to_be_removed) {
				fputs($file, "");
				break;
			}
		}
	}
	fclose($file);
}*/

// Utility functions
function requestVars($type = 'REQUEST')
{
	if ($type == 'REQUEST')
		$ay = $_REQUEST;
	elseif ($type == 'POST')
		$ay = $_POST;
	elseif ($type == 'GET')
		$ay = $_GET;

	$rtn = array();
	foreach ($ay as $a1 => $a2)
		$rtn[secureString($a1)] = secureString($a2);

	return $rtn;
}

function secureString($string)
{
	return trim(strip_tags(/* mysql_real_escape_string( */$string/* ) */));
}

function openFile($filename, $mode)
{
	$file = fopen($filename, $mode);
	//flock($file, LOCK_EX);
	if (!$file) {
		echo "<p><b>Could not open $file with access mode $mode!</b></p>\n";
		exit;
	}
	return $file;
}

function removeLine($filename, $line_to_be_removed)
{
	echo "->removeLine()\n";

	$lines = file($filename);
	$j = 0;
	for ($i = 0; $i < count($lines); $i++) {
		if ($i != $line_to_be_removed) {
			echo "[$i] INSERT $lines[$i]\n";
			$input_lines[$j] = $lines[$i];
			$j++;
		} else
			echo "[$i] REMOVE $lines[$i]\n";
	}

	$file = openFile($filename, 'w');
	for ($k = 0; $k < count($input_lines); $k++) {
		echo "\$input_lines[$k]: $input_lines[$k]";
		fputs($file, $input_lines[$k]);
	}
	fclose($file);

	echo "\n";
}
?>

<?php
/*ini_set('error_reporting', E_ALL ^ E_NOTICE);
ini_set('display_errors', 1);*/

include 'inc/query.inc.php';

$v = requestVars('POST');

// Usual site visit - do nothing
if (count($v) <= 0) {
	header("Location: http://www.insidexiii.eu/");
	exit;
}

// Retrieve server lines from servers.txt
if (count($v) == 1 && isset($v['request'])) {
	$data = file_get_contents(FILE_SERVERS);

	$output = explode("\n", $data);
	$output = array_filter($output, 'is_set');
	foreach ($output as $key => $value)
		echo /*$key => */"$value\n";
	exit;
}

function is_set($query_line)
{
	return !empty($query_line);
}

/*
 * Broadcast new/updated server data
 */
// Importation of all the received POST variables
$host = $_SERVER['REMOTE_ADDR'];
$port = $v['game_port'];
$current_server = $host . ':' . $port;

$servername = array('listen' => (isset($v['sn']) ? $v['sn'] : ''),
					'dedicated' => (isset($v['servername']) ? $v['servername'] : ''));

$game		= array('index' => $v['gameidx'], 'name' => $v['gametype']);
$map		= array('file' => $v['mapfile'], 'name' => $v['mapname']);
$limits		= array('frag' => $v['fraglimit'], 'time' => $v['timelimit']);
$players	= array('cur' => $v['num_players'], 'max' => $v['max_players']);

$friendlyfire = $v['friendlyfire'];
$mutators = $v['mutators'];
$private = $v['is_private'];

$query_port = $v['query_port'];

$playerstats = isset($v['playerstats']) ? $v['playerstats'] : '';

$query_string = $current_server .
				"?sn=" . $servername['listen'] . "?servername=" . $servername['dedicated'] .
				"?numplayers=" . $players['cur'] . "?maxplayers=" . $players['max'] .
				"?gameindex=" . $game['index'] . "?game=" . $game['name'] .
				"?map=" . $map['file'] . "?mapname=" . $map['name'] .
				"?friendlyfire=" . $friendlyfire .
				"?fraglimit=" . $limits['frag'] . "?timelimit=" . $limits['time'] .
				"?mutators=" . $mutators .
				"?private=" . $private .
				"?qp=" . $query_port .
				"?playerstats=" . $playerstats;

echo
	"Start querying for incoming IP: $current_server\n" .
	"==========================================================\n\n";
if (!empty($host) && (!empty($port))) {
	if (!file_exists(FILE_SERVERS) || !isStillRegistered()) {
		addServer();
		createTimeStamp();
	} else {
		refreshServer();
		updateTimeStamp();
	}
	cleanServerList();
	removeExpiredServers();
} else { // Game server sent invalid data
	echo "You are a noob!\n\n";
}
?>
<?php
date_default_timezone_set("UTC"); //set for the timezone your sign up data is using. 
error_reporting(-1);

require("config.php");
require("classes/EventImporter.php");

$metrics = new EventImporter(MIXPANEL_TOKEN, MIXPANEL_API_KEY);

$eventsFile = file_get_contents('data/km-cookisto-dev.json');

$i = 0;
foreach (explode("\n", $eventsFile) as $row)
{
	$data		= json_decode($row, true);
	if(!isset($data['_n'])) { continue; }

	$userId      = $data['_p'];
	$name        = $data['_n'];
	$timestamp   = $data['_t'];

	unset($data['_p']);
	unset($data['_n']);
	unset($data['_t']);

	// After unsetting _p, _n and _t, the remaining are event properties
	$event = $name;
	$attributes = $data;
    $attributes['distinct_id'] = $userId;
    $attributes['time'] = $timestamp;

	echo "\nLine $i: Sending $event event for ".$attributes['distinct_id']." at ".$attributes['time'].")\n";
	$metrics->track($event, $attributes);
	$i++;
}
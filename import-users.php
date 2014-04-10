<?php
date_default_timezone_set("UTC"); //set for the timezone your sign up data is using. 
error_reporting(-1);

require("config.php");
require("classes/EventImporter.php");

$metrics = new EventImporter(MIXPANEL_TOKEN, MIXPANEL_API_KEY);

// eg. dump from SELECT id,name,surname,email,DATE_FORMAT(createdAt, '%Y-%m-%dT%H:%i:%s'),CONCAT('+',telCode,tel) FROM Users
$csvFile = file_get_contents('data/all-users.csv');

$i = 0;
foreach (explode("\n", $csvFile) as $row)
{
    $data       = str_getcsv($row);
	$props      = array();
    $id         = $data[0];
	$ip         = '83.212.116.74';
    $props['$first_name']       = $data[1];
	$props['$last_name']       = $data[2];
    $props['$email']      = $data[3];
    $props['$created']  = strtotime($data[4]);
	$props['$phone']  = $data[5];

    echo "\nLine $i: Identifying user id $id\n";
    $metrics->identify($id, $ip, $props);
	$i++;
}
# Migrate from KissMetrics to Mixpanel

This script allows you to parse [exported Kissmetrics data](http://support.kissmetrics.com/apis/data/) and import it to Mixpanel.

### Step 0: KissMetrics to Mixpanel Migration Class

<a href="https://github.com/dnna/kissmetrics-to-mixpanel" target="_blank"><button>Github Repository</button></a>

### Step 1: Export the KissMetrics data

This step is simple and KissMetrics has a simple guide: [Follow this steps and come back for the next step.](http://support.kissmetrics.com/apis/data/)

### Step 2: Export your users

First you need to fetch your users from your database. You should export your userbase to a CSV file.

The required fields are: userid and ip. You can add other properties you might find useful - probably those you were already using on KissMetrics e.g. name, address or phone. 

```csv
"117","127.0.0.1","John Doe","Crampton Street 15"
...
```

### Step 3: Import your users

Feed each user to __identify($distinct_id, $ip, array $properties=array())__

This process will take a few minutes to hours depending on your userbase.

```php
date_default_timezone_set("UTC"); //set for the timezone your sign up data is using. 
error_reporting(-1);

require("config.php");
require("classes/EventImporter.php");

$metrics = new EventImporter(MIXPANEL_TOKEN, MIXPANEL_API_KEY);

// eg. dump from SELECT id,name,surname,email,DATE_FORMAT(createdAt, '%Y-%m-%dT%H:%i:%s'),CONCAT('+',telCode,tel) FROM Users
$csvFile = file_get_contents('data/all-users.csv');

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

    echo "\nIdentifying user id $id\n";
    $metrics->identify($id, $ip, $props);
}
```

### Step 4: Collect your events

After you imported all your users the S3 bucket should be filled with the content of all your KissMetrics events. The number of files can be overwhelming. They export hundreds of small files each containing several one-liners with JSON; we will need to compact it into one to facilitate the import process. Example:

```
{"platform":"iphone","_n":"login","_p":"12312","_t":1352317082}
{"platform":"web","_n":"login","_p":"2221321","_t":1352316831}
{"platform":"web","_n":"login","_p":"112123","_t":1352317100}
```

First sync the S3 bucket with a local directory:
```
s3cmd sync s3://kissmetrics-export-bucket km-export
```

Compact all the JSON files into one:

```
cat km-export/*.json > km-data.json
```


### Step 5: Import your events

Feed each event to __track($event, $properties=array())__

This process will take a few hours depending on the volume of events.

```php
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
```

### Step 6: Wait

On this step you can grab a coffee, watch a movie, do some more coding or simply sleep.
It's going to take a few hours but I guarantee you that will save you days or even weeks of work.
The data your already have on KissMetrics is worth a lot.

### Step 7: Extra tip

If you have a huge amount of data - a few months or even years - you can accelerate this process by importing only the last 30 days, for example. There are some use cases where you might need data from the Past but this timeframe worked for me.

Just add this piece of code:

```
$eventsSince = strtotime('-30 days');

foreach (explode("\n", $eventsFile) as $row)
{
	$data		= json_decode($row, true);

	$userId      = $data['_p'];
	$name        = $data['_n'];
	$timestamp   = $data['_t'];

	if ($timestamp < $eventsSince) continue;

	…
}
```

### Conclusion

This guide may seem big but you can do this process in a few hours. If you have any doubt just tweet or send me an email: dnna at dnna dot gr.

### Links

- [Export KissMetrics Data](http://support.kissmetrics.com/apis/data/)
- [Importing data to Mixpanel](https://mixpanel.com/docs/api-documentation/importing-events-older-than-31-days)
- [Migrate from KissMetrics to Customer.io (base for this guide)](https://github.com/knokio/kissmetrics-to-customerio)

<?php

// Set zip
if (! isset($zip)) {
	$zip = "10007"; // New York
}

define("MAX_WEATHER_DATA_FETCH_TRIES", 4);

function sxiToArray($sxi) {
	$a = array();
	for( $sxi->rewind(); $sxi->valid(); $sxi->next() ) {
		if(!array_key_exists($sxi->key(), $a)){
			$a[$sxi->key()] = array();
		}
		if($sxi->hasChildren()){
			$a[$sxi->key()][] = sxiToArray($sxi->current());
		}
		else{
			$a[$sxi->key()][] = strval($sxi->current());
		}
	}
	return $a;
}

function change_pts($dates) {
	$past = False;
	$pts = Array();
	$index = -1;
	foreach ($dates as $current) {
		$index++;
		if ($past ===	False) {
			$past = $current;
			$pts[] = $index;
			continue;
		}
		if ($past != $current) {
			$pts[] = $index;
			$pts[] = $index;
			$past = $current;
		}
	}
	$pts[] = $index + 1;
	
	return $pts;
}

function array_avg($a) {
	return (array_sum($a)/count($a));
}

function try_fetching_URL($zip, $tries) {
	$tries = $tries + 1;
	try {
		// Create times for weather fetching
		$in5d = new DateTime(date("Y-m-d"));
		$in5d->modify('+5 day');
		$in5d->modify('+1 year');
		$in5d = $in5d->format('Y-m-d');
		$now = date('Y-m-d');
		
		// Contruct URL
		$url = "https://graphical.weather.gov/xml/sample_products/browser_interface/ndfdXMLclient.php?product=time-series"
					 . "&zipCodeList=" . $zip // set zip code
					 . "&begin=" . $now . "T00:00:00" // set begin date
					 . "&end=" . $in5d . "T00:00:00"	// set end date
					 . "&maxt=maxt&mint=mint&qpf=qpf&sky=sky"; // set needed parameters
		// echo $url;

		// create curl resource
		$ch = curl_init();

		// set url
		curl_setopt($ch, CURLOPT_URL, $url);

		//return the transfer as a string
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');

		// $output contains the output string
		$xml = curl_exec($ch);

		// close curl resource to free up system resources
		curl_close($ch); 
	
		// Fetch URL
		$rss = simplexml_load_string($xml, "SimpleXMLIterator");
		$data = $rss->data;
		
		// Get start-time data
		$times = Array();
		$i = 0;
		foreach ($data->{'time-layout'} as $time) {
			$times[] = Array();
			foreach ($time->{'start-valid-time'} as $svt) {
				$svt = (string) $svt;
				
				$svt = explode('T', $svt, -1);
				$times[$i][] = $svt[0];
			}
			$i++;
		}
		
		// Get value data
		if ($data->parameters != null) {
			$values = sxiToArray($data->parameters);
			$values = $values['parameters'][0];
		} else {
			throw new Exception("Weather Data Fetch Failed (url:$url)");
		}
		
		return array($times, $values);
	}
	catch (Exception $e) {
		// Try again, if max is not hit
		if ($tries < MAX_WEATHER_DATA_FETCH_TRIES) {
			try_fetching_URL($zip, $tries);
		}
		else {
			throw new Exception("Max ".$e->getMessage());
		}
	}
}

// Safely get the URL
$toUnpack = try_fetching_URL($zip, 0);
$times = $toUnpack[0];
$values = $toUnpack[1];

$temp = array_pop($values['temperature']);
$values['temperature2'] = Array($temp);

foreach ($values as $key => $that)
{
	$values[$key] = $values[$key][0]['value'];
}

$values = array_combine(Array(0=>0, 3=>3, 2=>2, 1=>1), $values);
ksort($values);

$pdat = Array();
$pTimes = Array();
$j = 0;
$funcs = Array("array_avg", "array_avg", "array_avg", "array_sum");
foreach ($times as $tlist)
{
	$pdat[] = Array();
	$pTimes[] = Array();
	$pts = change_pts($tlist);
	for ($i = 0; $i < count($pts); $i += 2)
	{
		$set = array_slice($values[$j], $pts[$i], $pts[$i + 1] - $pts[$i]);
		$nVal = $funcs[$j]($set);
		$pdat[$j][] = $nVal;
		$pTimes[$j][] = $times[$j][$pts[$i]];
	}
	$j++;
}

print_r($pdat);
return Array($pTimes, $pdat);

?>

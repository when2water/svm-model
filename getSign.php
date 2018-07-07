<?php

ini_set("safe_mode", 0);
ini_set("precision", 15);

if (! isset($zip)) {
	$zip = "10007"; // New York
}

// Get predicted data from getPred.php for the current zip code
ob_start(); // stop any output from that file
$predictionData = include_once "getPred.php";

/* Data Format: fData/fTimes
 * - "f" prefix for predicted weather data
 * - $fTimes is associated dates
 * - $fData[0] is high temperatures
 * - $fData[1] is low temperatures
 * - $fData[2] is cloud cover (0-100 scale)
 * - $fData[3] is precipitation
 * - $fData[*][0] is current/today's data, $fData[*][1] is tomorrow's data, etc.
 */
$fData = $predictionData[1];
$fTimes = $predictionData[0];
ob_end_clean();
// var_dump($predictionData);

ob_start(); // suppress all outputs
/* Data Format: pData
 * - "p" prefix for predicted weather data
 * - Each row represents a single day's data
 * - $pData[*][0] is associated dates
 * - $pData[*][1] is high temperature
 * - $pData[*][2] is low temperature
 * - $pData[*][3] is cloud cover (0-100 scale)
 * - $pData[*][4] is precipitation
 * - $pData[3] is yesterday, $pData[0] is the oldest available data, etc
 */
$pData = include_once "getHist.php";
ob_end_clean();

if ($missingData === False) {
	// sets $sign to irrigation decision

	// high temperatures
	$highCur = (float) $fData[0][0];
	$highPast3day = $pData[1][1] + $pData[2][1] + $pData[3][1];
	$highPast3day = (float) ($highPast3day / 3.0);
	$highPred3day = array_slice($fData[0], 1, 3); // indicies 1, 2, 3
	$highPred3day = (float) array_avg($highPred3day);

	// low temperatures
	$lowCur = (float) $fData[1][0];
	$lowPast3day = $pData[1][2] + $pData[2][2] + $pData[3][2];
	$lowPast3day = (float) ($lowPast3day / 3.0);
	$lowPred3day = array_slice($fData[1], 1, 3); // indicies 1, 2, 3
	$lowPred3day = (float) array_avg($lowPred3day);

	// cloud cover
	$cloudCur = (float) $fData[2][0];

	// precipitation
	$precipCur = (float) $fData[3][0];
	$precipPast3day = (float) ($pData[1][4] + $pData[2][4] + $pData[3][4]);
	$precipPred3day = array_slice($fData[3], 1, 3); // indicies 1, 2, 3
	$precipPred3day = (float) array_sum($precipPred3day);

	// TODO send the variables to the python script
	$program = "python3 SVC-make_pred.py";
	$params = sprintf("%f %f %f %f %f %f %f %f %f %f",
		$highCur, $highPast3day, $highPred3day,
		$lowCur ,  $lowPast3day,  $lowPred3day,
	 	$cloudCur,
		$precipCur, $precipPast3day, $precipPred3day);

	$cmd = $program . " " . $params . " 2>&1";
	// $sign = $cmd;
	$sign = shell_exec($cmd);
	// $sign = filter_var($sign, FILTER_VALIDATE_INT);
	// if ($sign === false) {
	// 	$sign = -4;
	// }
}
else {
	$sign = -5;
}

/* Sample cURL system call command for weather data
$token = "MrMdNxjhVdRUqmBReCCYpqzYVBIlYhaV";
$url = "http://www.ncdc.noaa.gov/cdo-web/api/v2/data?datasetid=GHCND&startdate=2014-06-05&enddate=2014-06-15&locationid=ZIP:10007";
// $url = "http://www.ncdc.noaa.gov/cdo-web/api/v2/data?datasetid=GHCND&locationid=FIPS:02&startdate=2010-05-01&enddate=2010-05-31";
$query = "curl -H \"token:$token\" \"$url\"";
echo $query;
echo system($query);
 */

// Log output with zip and timestamp
$log = fopen("signs.log", "a");
$dt = new DateTime();
fputs($log, $dt->format(DateTime::ISO8601) . "@zip=" . $zip . ":" . $sign . "\n");
fclose($log);

print $sign;
return $sign;

?>

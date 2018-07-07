<?php

// Read any stored historical data into correct format
$fName = dirname(__FILE__) . "/wData/$zip.txt";
if (!file_exists($fName)) {
	$zipReg = fopen(dirname(__FILE__) . "/zips.txt", "a+");
	fwrite($zipReg, "$zip\n");
	fclose($zipReg);
}
$f = fopen($fName, "r");
rewind($f);
$raw = fread($f, filesize($fName));
fclose($f);
$raw = explode("\n", $raw);
$pData = Array();
foreach ($raw as $line) { // Formating
	if ($line) {
		$line = explode(",", $line);
		$line[0] = new DateTime($line[0]);
		$line[1] = (float) $line[1];
		$line[2] = (float) $line[2];
		$line[3] = (float) $line[3];
		$line[4] = (float) $line[4];
		$pData[] = $line;
	}
}

// Check for today's historical data
$tDataGot = False;
$today = new DateTime(date('Y-m-d'));
if ($pData[count($pData) - 1][0] == $today) { // Only the latest data
	$pData = array_slice($pData, -5, 4);
	$tDataGot = True;
} else {
	$pData = array_slice($pData, -4);
}
// var_dump($tDataGot);
// var_dump($pData);

// Make sure the data isn't missing anything
$missingData = False;
for ($i = 3; $i >= 0; $i--) {
	if (! isset($pData[$i][0])) {
		$missingData = True;
		break;
	}
	$add = "+" . (string) (4 - $i) . " day";
	$dD = $pData[$i][0];
	$dD->modify($add);
	if ($dD->format("Y-m-d") !== $today->format("Y-m-d")) {
		$missingData = True;
		break;
	}
}

// Write new historical data to file
if (!$tDataGot) {
	$f = fopen($fName, "a+");
	$tData = Array();
	$tData[] = (string) date('Y-m-d');
	$tData[] = (string) $fData[0][0];
	$tData[] = (string) $fData[1][0];
	$tData[] = (string) $fData[2][0];
	$tData[] = (string) $fData[3][0];
	$tData = (string) implode(",", $tData);
	fwrite($f, $tData);
	fwrite($f, "\n");
	fclose($f);
}

return $pData;

?>

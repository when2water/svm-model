<?php

/* Response Codes (Pos->Success, Neg->Failure)
 *  1: Signal->Water
 * -1: Signal->Don't Water (or restrictions disallow)
 * -2: Zip Code Not Provided Error
 * -3: Zip Code Not Valid Error
 * -4: Exception Thrown Error
 * -5: Missing historical data
 *
 * Allowed $_GET['restriction']
 * -1: No restrictions
 *  0: Even Days Only
 *  1: Odd Days Only
*/

// Zip code verification function
function verifyZip($zip) {
	$ch = curl_init("https://signal.when2water.org/zipVerify.php?zip=" . $zip);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	$verify = curl_exec($ch);
	curl_close($ch);
	if ($verify === "0") {
		return true;
	}
	return false;
}

define("WATERING_THRESHOLD", 0);

ob_start();
$code = -4;
try {
	if (isset($_GET['zip']) && $_GET['zip'] !== null && $_GET['zip'] !== "") {
		if (verifyZip($_GET['zip'])) {
			$now = new DateTime(null, new DateTimeZone('America/New_York'));

			$zip = $_GET['zip'];
			$canWaterToday = true;
			if (isset($_GET['restriction']) &&
				($_GET['restriction'] === "-1" || $_GET['restriction'] === "0" || $_GET['restriction'] === "1") &&
				$_GET['restriction'] !== "-1") {
				// they have restrictions
				if ( ((int) $now->format("d")) % 2 != $_GET['restriction'] ) {
					$canWaterToday = false;
				}
			}

			if ($canWaterToday) {
				// Get signal
				ob_start();
				$sign = include_once "getSign.php";
				ob_end_clean();
				// echo $sign;
			}

			// Return final sign
			if ($canWaterToday && isset($sign) && $sign > WATERING_THRESHOLD) {
				$code = 1; // water signal + no restriction
			} else if ($sign === -5) {
				$code = -5; // internal error (in getSign.php)
			} else {
				$code = -1; // don't water signal / restriction
			}
		} else {
			$code = -3; // zip not valid
		}
	} else {
		$code = -2; // zip not set
	}
} catch (Exception $e) {
	// catch errors
	// $e->getMessage();
	error_log($e->getMessage());
	$code = -4;
}
// ob_end_clean();
ob_end_flush(); // for debugging, see any outputs

if (isset($_GET['override'])) {
	$code = 1;
}

if (isset($_GET['json'])) {
	// JSON formatting
	echo '{"signal":' . $code . '}';
}
else {
	// Normal formatting
	echo $code;
}

?>

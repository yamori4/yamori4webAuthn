<?php
require_once dirname(__FILE__) . "/../config/config.php";

class Log
{
	static function write($msg)
	{
		if (is_array($msg) || is_object($msg) || is_bool($msg)) {
			$msg = var_export($msg, true);
		}
		$fp = fopen(dirname(__FILE__) . "/../log/log_" . gmdate("Y-m-d") . ".txt", "a");
		fwrite($fp, gmdate("H:i:s") . " " . $msg . "\r\n");
		fclose($fp);
	}

	static function debugWrite($msg)
	{
		if (Config::isTestMode()) {
			self::write($msg);
		}else{
			//Do Nothing
		}
	}
}

<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 AOE GmbH <dev@aoe.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

require_once 'AbstractLogger.php';


/**
 * @author Chetan Thapliyal <chetan.thapliyal@aoe.com>
 */
class FileLogger extends AbstractLogger {

	/**
	 * @var string
	 */
	private $logFile;


	/**
	 * Initialize file logger instance.
	 *
	 * @param  string $logFile      Absolute path to log file.
	 */
	public function __construct($logFile) {
		parent::__construct();
		$this->setLogFile($logFile);
	}

	/**
	 * @param  string $logFile
	 * @throws Exception
	 */
	public function setLogFile($logFile) {
		if (!file_exists($logFile) && (FALSE === touch($logFile))) {
			throw new \Exception($logFile .  ': Could not create log file.', 1454417454);
		}

		if (is_file($logFile) && is_writable($logFile)) {
			$this->logFile = $logFile;
		} else {
			throw new \Exception($logFile .  ': File either does not exist or not writable.', 1454417454);
		}
	}

	/**
	 * @param  string $message
	 * @param  int    $severity
	 * @param  bool   $appendEOL
	 * @return void
	 */
	public function log($message, $severity = self::LOG_INFO, $appendEOL = TRUE) {
		if ($this->logSeverity($severity)) {
			file_put_contents(
				$this->logFile,
				date('d.m.Y H:i:s') . ' [' . $this->getReadableLogLevel($severity) . '] ' . $message,
				FILE_APPEND
			);
		}
	}

	/**
	 * Truncate log file.
	 */
	public function truncate() {
		$fp = @fopen($this->logFile, 'w');

		if ($fp) {
			fclose($fp);
		}
	}
}

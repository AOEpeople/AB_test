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

require_once 'Logger.php';


/**
 * @author Chetan Thapliyal <chetan.thapliyal@aoe.com>
 */
abstract class AbstractLogger implements \Logger {

	protected $logLevel;

	public function __construct() {
		$this->logLevel = self::LOG_INFO;
	}

	/**
	 * Get human readable log level.
	 *
	 * @param  int $logLevel
	 * @return string
	 */
	public function getReadableLogLevel($logLevel) {
		$readableLogLevel = '';

		if ($this->isValidLogLevel($logLevel)) {
			switch($logLevel) {
				case self::LOG_DEBUG:
					$readableLogLevel = 'DEBUG';
					break;
				case self::LOG_INFO:
					$readableLogLevel = 'INFO';
					break;
				case self::LOG_WARNING:
					$readableLogLevel = 'WARNING';
					break;
				case self::LOG_ERROR:
					$readableLogLevel = 'ERROR';
					break;
			}
		}

		return $readableLogLevel;
	}

	/**
	 * @param int $logLevel
	 */
	public function setLogLevel($logLevel) {
		if ($this->isValidLogLevel($logLevel)) {
			$this->logLevel = $logLevel;
		} else {
			$this->log($logLevel . ': Invalid log level.', self::LOG_INFO);
		}
	}

	/**
	 * Check if the messages with given severity should be logged.
	 *
	 * @param  integer $severity
	 * @return bool
	 */
	protected function logSeverity($severity) {
		return ($this->isValidLogLevel($severity) && ($severity >= $this->logLevel));
	}

	/**
	 * @param  int $logLevel
	 * @return bool
	 */
	protected function isValidLogLevel($logLevel) {
		return is_numeric($logLevel) && ($logLevel >= self::LOG_DEBUG) && ($logLevel <= self::LOG_ERROR);
	}
}

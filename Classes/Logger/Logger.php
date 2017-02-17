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

/**
 * @author Chetan Thapliyal <chetan.thapliyal@aoe.com>
 */
interface Logger {

	const LOG_DEBUG   = 1;
	const LOG_INFO    = 2;
	const LOG_WARNING = 3;
	const LOG_ERROR   = 4;

	/**
	 * @param  string $message
	 * @param  int    $severity
	 * @param  bool   $appendEOL
	 * @return void
	 */
	public function log($message, $severity = self::LOG_INFO, $appendEOL = TRUE);
}

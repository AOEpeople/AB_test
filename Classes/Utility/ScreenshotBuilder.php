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

require_once PROJECT_ROOT.'Classes/Logger/Logger.php';

class ScreenshotBuilder {

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @param  Logger $logger
	 * @return $this
	 */
	public function setLogger(Logger $logger) {
		$this->logger = $logger;
		return $this;
	}

	/**
	 * Use plain phantomjs for screenshots
	 *
	 * @param $url
	 * @param $output
	 */
	public function make($url, $output) {
		$cmd = 'phantomjs JavaScript/screenshot.js ' . escapeshellarg($url) . ' ' . $output;
		$this->logger->log('Executing: ' . $cmd);
		exec($cmd);
	}

	/**
	 * Use Casper (slightly better for responsive)
	 *
	 * @param $url
	 */
	public function makeWithCasper($url) {
		$cmd = 'casperjs JavaScript/casper-shot.js ' . escapeshellarg($url);
		$this->logger->log('Executing: ' . $cmd);
		exec($cmd);
	}

	/**
	 * PhantomCSS should be the most reliable choice
	 * Use it to make individual comparisons as well
	 *
	 * @see https://github.com/Huddle/PhantomCSS
	 * @param string $url
	 * @return void
	 */
	public function makeWithPhantomCss($originalUrl, $diffUrl) {
		$cmd = PROJECT_ROOT.'node_modules/phantomcss/node_modules/casperjs/bin/casperjs JavaScript/phantomcss-compare.js' .' '. escapeshellarg($diffUrl) .' '. escapeshellarg($originalUrl);
		$this->logger->log('Executing: ' . $cmd);
		exec($cmd);
	}
}

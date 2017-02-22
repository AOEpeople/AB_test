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

require_once PROJECT_ROOT . 'Classes/Controller/Controller.php';


/**
 * @author Chetan Thapliyal <chetan.thapliyal@aoe.com>
 */
abstract class AbstractController implements Controller {

	const CONFIG_FILENAME = 'config.json';

	/**
	 * @var array
	 */
	private $configuration;

	/**
	 * Request arguments
	 * @var array
	 */
	protected $arguments = array();

	/**
	 * @var Logger
	 */
	protected $logger;

	public function setArguments(array $arguments) {
		$this->arguments = $arguments;
	}

	/**
	 * @param  Logger $logger
	 * @return $this
	 */
	public function setLogger(Logger $logger) {
		$this->logger = $logger;
		return $this;
	}

	public function initialize() {
		// to be overridden in concrete controller instance
	}

	/**
	 * Default action
	 */
	public function defaultAction() {
		// to be overridden in concrete controller instance
	}

	/**
	 * @return array
	 */
	protected function getConfiguration() {
		if (!is_array($this->configuration)) {
			$this->configuration = array();
			$configurationFile = PROJECT_ROOT . self::CONFIG_FILENAME;

			if (file_exists($configurationFile)) {
				$configJSON = file_get_contents($configurationFile);
				$config = json_decode($configJSON, TRUE);
				if (json_last_error() > 0) {
					$this->logger->log("config.json file error:\r\n" . json_last_error_msg(),Logger::LOG_DEBUG);
				}
				$this->configuration = is_array($config) ? $config : array();
			} else {
				$this->logger->log('Missing configuration file config.json or config file not UTF-8 encoded!',Logger::LOG_WARNING);
			}
		}

		return $this->configuration;
	}
}

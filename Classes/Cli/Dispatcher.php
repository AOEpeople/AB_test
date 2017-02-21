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


define('PROJECT_ROOT', realpath(__DIR__ . '/../../').'/');

require_once PROJECT_ROOT . 'Classes/Domain/Repository/LinkRepository.php';
require_once PROJECT_ROOT . 'Classes/Logger/Logger.php';
require_once PROJECT_ROOT . 'Classes/Logger/ScreenLogger.php';
require_once PROJECT_ROOT . 'Classes/Logger/FileLogger.php';
require_once PROJECT_ROOT . 'Classes/Utility/Compare.php';
require_once PROJECT_ROOT . 'Classes/Utility/Database/AbstractDbAdapter.php';
require_once PROJECT_ROOT . 'Classes/Utility/Database/MysqlAdapter.php';
require_once PROJECT_ROOT . 'Classes/Utility/Http/HttpClient.php';
require_once PROJECT_ROOT . 'Classes/Utility/Http/HttpResponse.php';
require_once PROJECT_ROOT . 'Classes/Utility/LinkParser.php';
require_once PROJECT_ROOT . 'Classes/Utility/ScreenshotBuilder.php';
require_once PROJECT_ROOT . 'Vendor/finediff.php';


/**
 * @author Erik Frister <erik.frister@aoe.com>
 * @author Chetan Thapliyal <chetan.thapliyal@aoe.com>
 */
class Dispatcher {

	/**
	 * @var array
	 */
	private $arguments;

	/**
	 * @var Logger
	 */
	private $logger;


	/**
	 * Dispatch request to appropriate controller.
	 */
	public function dispatch() {
		$this->parseArguments();
		$this->initializeLogger();

		$controller = $this->getController();
		$action = $this->getControllerAction();

		if (method_exists($controller, $action)) {
			$status = $controller->$action();
		} else {
			$this->logger->log(get_class($controller) . '::' . $action . ': Invalid action');
			$this->displayUsageAndExit();
		}

		return $status;
	}

	protected function initializeLogger() {
		$this->logger = new ScreenLogger();
		$this->logger->setLogLevel(
			(isset($this->arguments['log-level']) ? $this->arguments['log-level'] : Logger::LOG_INFO)
		);
	}

	/**
	 * Parse command line arguments.
	 */
	protected function parseArguments() {
		$args = getopt('c:a:h:u:p:d:', array('log-level::', 'max-fail-count::', 'ignore-cache::', 'url::'));

		if (!isset($args['c'])) {
			$this->displayUsageAndExit();
		}

//		if ((!isset($args['c']))
//			|| (!isset($args['u']))
//			|| (!isset($args['p']))
//			|| (!isset($args['d']))) {
//
//			$this->displayUsageAndExit();
//		}
//
		$this->arguments = $args;
	}

	/**
	 * Parse request arguments and generate the requested controller instance.
	 *
	 * @return \Controller
	 */
	protected function getController() {
		$controller = NULL;
		$controllerName  = $this->arguments['c'];
		$controllerClass = $controllerName . 'Controller';
		$controllerFile  = PROJECT_ROOT . 'Classes/Controller/' . $controllerClass . '.php';

		if (file_exists($controllerFile)) {
			require_once $controllerFile;
			$controller = new $controllerClass();
		} else {
			$this->logger->log($controllerName . ': Invalid controller');
			$this->displayUsageAndExit();
		}

		$this->injectControllerDependencies($controllerName, $controller);

		return $controller;
	}

	protected function getControllerAction() {
		$action = isset($this->arguments['a']) ? $this->arguments['a'] : 'default';

		if (strlen($action)) {
			$action .= 'Action';
		}

		return $action;
	}

	/**
	 * @param  string $controllerName
	 * @param  Controller $controllerInstance
	 */
	protected function injectControllerDependencies($controllerName, Controller $controllerInstance) {
		$controllerInstance->setLogger($this->logger);
		$controllerInstance->setArguments($this->arguments);

		switch($controllerName) {
			case 'ABTest':
				/** @var \ABTestController $controllerInstance */
				$controllerInstance
					->setLinkRepository($this->getLinkRepository())
					->setTestResultLogger($this->getFileLogger(PROJECT_ROOT . '/Log/failed-urls.log'))
					->setCompareUtility($this->getCompareUtility());
				break;
		}
		
		$controllerInstance->initialize();
	}

	/**
	 * @param  string $filePath     Absolute path to log file name.
	 * @return FileLogger
	 */
	protected function getFileLogger($filePath) {
		$fileLogger = NULL;

		try {
			$fileLogger = new FileLogger($filePath);
		} catch (\Exception $e) {
			$this->logger->log($e->getMessage());
			exit(1);
		}

		$fileLogger->truncate();

		return $fileLogger;
	}

	/**
	 * @return Compare
	 */
	protected function getCompareUtility() {
		$compareUtility = new Compare();
		$compareUtility
			->setLogger($this->logger)
			->setScreenshotBuilder($this->getScreenshotBuilder());

		return $compareUtility;
	}

	/**
	 * @return ScreenshotBuilder
	 */
	protected function getScreenshotBuilder() {
		$screenshotBuilder = new ScreenshotBuilder();
		$screenshotBuilder->setLogger($this->logger);

		return $screenshotBuilder;
	}

	/**
	 * @return LinkRepository
	 */
	protected function getLinkRepository() {
		$linkRepository = new LinkRepository();
		$linkRepository->setLogger($this->logger);

			// not needed anymore
		#$dbAdapter = $this->getMysqlAdapter();
		#$dbAdapter->setLogger($this->logger);
		#$linkRepository->setDbAdapter($dbAdapter);

		$linkRepository->setLinkParser($this->getLinkParser());

		return $linkRepository;
	}

	/**
	 * @return \MysqlAdapter
	 */
	protected function getMysqlAdapter() {
		$hostname = isset($this->arguments['h']) ? $this->arguments['h'] : '127.0.0.1';
		$dbAdapter = new MysqlAdapter($this->arguments['u'], $this->arguments['p'], $this->arguments['d'], $hostname);
		return $dbAdapter;
	}

	/**
	 * @return \LinkParser
	 */
	protected function getLinkParser() {
		$linkParser = new LinkParser();
		$linkParser
			->setLogger($this->logger)
			->setHttpClient($this->getHttpClient());

		return $linkParser;
	}

	/**
	 * @return \HttpClient
	 */
	protected function getHttpClient() {
		$httpClient = new HttpClient();
		$httpClient->setHttpResponse(new \HttpResponse());

		return $httpClient;
	}

	protected function displayUsageAndExit() {
		echo 'Usage:' . PHP_EOL .
		     'php Dispatch.php -c ABTest [-a <compareApiCalls|compareFrontend>] ' .
		     '[--url=<primary url> --url=<primary url>] ' .
		     //'-u<db username > -p <db password> -d<db name> [-h<db hostname>] ' .
		     '[--log-level=<log level>] [--max-fail-count=<fail count>]' .
		     '[--ignore-cache]' . PHP_EOL . PHP_EOL;
		exit(1);
	}
}

$dispatcher = new Dispatcher();
$dispatcher->dispatch();

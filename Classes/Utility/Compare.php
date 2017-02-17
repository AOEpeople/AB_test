<?php

require_once PROJECT_ROOT . 'Classes/Logger/Logger.php';
require_once PROJECT_ROOT . 'Classes/Exception/SkipException.php';


class Compare {

	/**
	 * @var array
	 */
	protected $configuration;

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @var ScreenshotBuilder
	 */
	private $screenshotBuilder;

	/**
	 * @param array $configuration
	 */
	public function setConfiguration(array $configuration) {
		$this->configuration = $configuration;
	}

	/**
	 * @param  Logger $logger
	 * @return $this
	 */
	public function setLogger(Logger $logger) {
		$this->logger = $logger;
		return $this;
	}

	/**
	 * @param ScreenshotBuilder $screenshot
	 * @return $this
	 */
	public function setScreenshotBuilder(ScreenshotBuilder $screenshot){
		$this->screenshotBuilder = $screenshot;
		return $this;
	}

	/**
	 * Compare on text level
	 *
	 * @param  string $originalUrl
	 * @param  string $diffUrl
	 * @param  string $diff
	 * @return bool
	 * @throws Exception
	 */
	public function compareText($originalUrl, $diffUrl, &$diff) {

		$expectedContent = $this->getUrl($originalUrl, $httpStatus, $error);

		if ($httpStatus !== 200) {
			$this->logger->log('Request to original URL not successful (other status code than 200)', Logger::LOG_DEBUG);
			throw new \SkipException($httpStatus . ': ' . $error . ': ' . $originalUrl, 1455543002);
		}

		$expectedContent = $this->normalizeContent($expectedContent);

		$httpStatus = 0;
		$error = '';
		$actualContent = $this->getUrl($diffUrl, $httpStatus, $error);

		if ($httpStatus !== 200) {
			$this->logger->log('Request to test URL not successful (other status code than 200)', Logger::LOG_DEBUG);
			throw new \SkipException($httpStatus . ': ' . $error . ': ' . $diffUrl, 1455790651);
		}

		$actualContent = $this->normalizeContent($actualContent);

		// Normalize comparison content
		// i.e. only the domains should be different, the rest should be the same
//		$originalDomain = parse_url($originalUrl, PHP_URL_HOST);
//		$diffDomain = parse_url($diffUrl, PHP_URL_HOST);
//
//		$normalizedOriginal = str_replace($originalDomain, '', $expectedContent);
//		$normalizedDiff = str_replace($diffDomain, '', $actualContent);

		$looksRight = ($actualContent === $expectedContent);

		if (!$looksRight) {
			// Word Granularity
			// Put a breakpoint here in case the text comparison fails to see the diff
			// TODO: collect diffs and print them into a report at the end of the test
			$diff = FineDiff::getDiffOpcodes($expectedContent, $actualContent, FineDiff::$wordGranularity);
		}

		return $looksRight;
	}

	protected function normalizeContent($content) {
		// first global processors
		foreach ($this->configuration['global']['processors'] as $id => $configuration) {
			/** @var AbstractProcessor $processor */
			$processor = $this->getProcessor($id);

			if (!is_object($processor)) {
				throw new Exception($id . ': Invalid global processor.', 1470208720);
			}

			$processor->setConfiguration($configuration);
			$content = $processor->process($content);
		}

		return $content;
	}

	private function getProcessor($id) {
		$processor = NULL;
		$class = ucfirst($id) . 'Processor';
		$classFile = PROJECT_ROOT . 'Classes/Processor/' . $class . '.php';

		if (file_exists($classFile)) {
			require_once $classFile;
			$processor = new $class();
		}

		return $processor;
	}

	/**
	 * @param string $originalUrl
	 * @param string $diffUrl
	 * @return bool
	 */
	public function compareScreenshot($originalUrl, $diffUrl) {
		// remove old failure screenshots if any
		$failureScreenshotsPattern = $this->getFailureScreenshotsPattern($diffUrl);
		$failureScreenshots = glob($failureScreenshotsPattern);

		if (is_array($failureScreenshots) && count($failureScreenshots)) {
			foreach($failureScreenshots as $screenshot) {
				unlink($screenshot);
			}
		}

		$this->logger->log('Call PhantomCSS', Logger::LOG_DEBUG);
		$this->screenshotBuilder->makeWithPhantomCss($originalUrl, $diffUrl);
		$this->logger->log('Call PhantomCSS - done', Logger::LOG_DEBUG);

		// failure if new screenshots exists
		$failureScreenshots = glob($failureScreenshotsPattern);
		$comparisonStatus = (is_array($failureScreenshots) && count($failureScreenshots)) ? FALSE : TRUE;

		return $comparisonStatus;
	}

	/**
	 * @param  string $url
	 * @param  int    $httpStatus   HTTP response code from server
	 * @param  string $error        Error description
	 * @return string               URL content
	 */
	protected function getUrl($url, &$httpStatus = 0, &$error = '') {
		$ch = curl_init($url);

		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_FOLLOWLOCATION => FALSE,
			CURLOPT_SSL_VERIFYHOST => FALSE,
			CURLOPT_SSL_VERIFYPEER => FALSE,
			CURLOPT_FOLLOWLOCATION => TRUE,
			CURLOPT_MAXREDIRS      => 3
		));

		$content = curl_exec($ch);
		$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if (FALSE === $content) {
			$error = curl_error($ch);
		}

		curl_close($ch);

		return $content;
	}

	/**
	 * @param  string $url
	 * @return string           Pattern matching absolute filesystem path of failure screenshots.
	 */
	protected function getFailureScreenshotsPattern($url) {
		$failureScreenshotsLocation = PROJECT_ROOT . 'Tmp/Screenshots/failures/';
		$host = parse_url($url, PHP_URL_HOST);
		list(,$uri) = explode($host, $url);
		$uri = str_replace('/', '-', trim($uri, '/'));

		if ($uri === '' || $uri === '/') {
			$uri = 'index';
		}

		$screenshotPattern = $failureScreenshotsLocation . $host . '/' . $uri . '-*';

		return $screenshotPattern;
	}
}

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

require_once  PROJECT_ROOT . 'Classes/Processor/UrlProcessor.php';

$urlProcessor = NULL;

class LinkParser {

	/**
	 * @var array
	 */
	protected $configuration;

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @var HttpClient
	 */
	private $http;

	/**
	 * @param  Logger $logger
	 * @return $this
	 */
	public function setLogger(Logger $logger) {
		$this->logger = $logger;
		return $this;
	}

	/**
	 * @param HttpClient $http
	 * @return $this
	 */
	public function setHttpClient(HttpClient $http) {
		$this->http = $http;
		return $this;
	}

	/**
	 * @param array $configuration
	 */
	public function setConfiguration(array $configuration) {
		$this->configuration = $configuration;
	}

	/**
	 * Scan a whole domain
	 *
	 * @param $domain
	 * @return array
	 * @throws \Exception
	 */
	public function getAllLinksFromDomain($domain) {
		$this->logger->log('Getting links for domain `' . $domain . '`');

		$urlQueue    = array($domain);
		$crawledUrls = array();
		$failedUrls  = array();
		$skippedUrls = array();

		do {
			$url = array_pop($urlQueue);

				// Don't visit the same page twice
			if (in_array($url, $crawledUrls) || in_array($url, $failedUrls) || in_array($url, $skippedUrls)) {
				continue;
			}

			$this->logger->log('Crawling ' . $url, Logger::LOG_DEBUG);

			if (!$this->processUrl($url)) {
				$skippedUrls[] = $url;
				$this->logger->log('Limit Exceeded: Skipping ...', Logger::LOG_DEBUG);
				continue;
			}

			$response = $this->http->get($url);
			$statusCode = $response->getStatus();

			if ($statusCode !== HttpResponse::STATUS_OK) {
				if ($response->isError()) {
					$this->logger->log('-- ' . $statusCode . ': ' . $response->getError(). '. URL Skipped.', Logger::LOG_DEBUG);
				} else {
					$this->logger->log($statusCode . ': Skipping ...', Logger::LOG_DEBUG);
				}
				$failedUrls[] = $url;
			} else {
				$effectiveUrl = $response->getEffectiveUrl();

				if ($url !== $effectiveUrl) {
					$this->logger->log('-- REDIRECT: ' . $url . ' -> ' . $effectiveUrl, Logger::LOG_DEBUG);
					$this->logger->log('-- Using target URL: ' . $effectiveUrl, Logger::LOG_DEBUG);
					$skippedUrls[] = $url;

					if (!in_array($effectiveUrl, $crawledUrls)) {
						$crawledUrls[] = $effectiveUrl;
					}
				} else {
					$crawledUrls[] = $url;
				}

				$links = $this->extractLinksFromHtml($response->getContent(), $domain);
				$newLinks = array_diff($links, $crawledUrls, $urlQueue);
				$urlQueue = array_merge($urlQueue, $newLinks);
			}
		} while (!empty($urlQueue));

		$links = array_unique($crawledUrls);
		return $links;
	}

	private function processUrl($url) {
		global $urlProcessor;

		if (!$urlProcessor) {
			$urlProcessor = new UrlProcessor();
			$urlProcessor->setConfiguration($this->configuration);
		}

		return $urlProcessor->process($url);
	}

	/**
	 * DOM Parsing
	 *
	 * @param string $html
	 * @param string $domain
	 * @return array
	 */
	protected function extractLinksFromHtml($html, $domain) {
		$links = array();
		// Load DOM
		$dom = new DOMDocument;
		@$dom->loadHTML($html);

		/** @var DOMNode $node */
		foreach ($dom->getElementsByTagName('a') as $node) {
			if (!$node->hasAttributes()) {
				continue;
			}

			/** @var DOMAttr $attribute */
			foreach ($node->attributes as $attribute) {
				// Only hrefs
				if ($attribute->name !== 'href') {
					continue;
				}
				$possibleUrl = trim($attribute->value);
				// Filter out empty urls
				if (empty($possibleUrl) || "/" === $possibleUrl) {
					continue;
				}
				// Skip anchor links
				if ('#' === substr($possibleUrl, 0, 1)) {
					continue;
				}
				// Skip keywords
				if ('mailto:' === substr($possibleUrl, 0, strlen('mailto:'))) {
					continue;
				}
				if ('javascript:' === substr($possibleUrl, 0, strlen('javascript:'))) {
					continue;
				}
				if ('tel:' === substr($possibleUrl, 0, strlen('tel:'))) {
					continue;
				}
				// Skip fileadmin and uploads
				if (FALSE !== stripos($possibleUrl, 'fileadmin')) {
					continue;
				}
				if (FALSE !== stripos($possibleUrl, 'uploads')) {
					continue;
				}
				if (FALSE !== stripos($possibleUrl, 'typo3temp')) {
					continue;
				}

				$host = parse_url($possibleUrl, PHP_URL_HOST);
				$actualUrl = '';

				if (NULL === $host) {
					// Local url
					$actualUrl = rtrim($domain, '/') . '/' . ltrim($possibleUrl, '/');
				} else {
					// Skip if not the same host
					if (FALSE === stripos($domain, $host)) {
						// writeLog('Skipping "' . $possibleUrl . '" because its not on our domain "' . $domain . '"');
						continue;
					}
					// Make sure to use the same subdomain
					$actualUrl = rtrim($domain, '/') . '/' . ltrim(substr($possibleUrl, stripos($possibleUrl, $host) + strlen($host)), '/');
				}
				// Remove skiplinks
				if (FALSE !== stripos($actualUrl, '#')) {
					$actualUrl = substr($actualUrl, 0, stripos($actualUrl, '#'));
				}
				$actualUrl = rtrim($actualUrl, '/') . '/';
				// Finally, a candidate
				$links[] = $actualUrl;
			}
		}

		$links = array_unique($links);
		return $links;
	}
}

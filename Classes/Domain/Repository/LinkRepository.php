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
 * @author Erik Frister <erik.frister@aoe.com>
 * @author deniz.dagtekin <deniz.dagtekin@aoe.com>
 */
class LinkRepository {

	/**
	 * @var array
	 */
	protected $configuration;

	/**
	 * @var string
	 */
	private $cacheDir;

	/**
	 * Database connector
	 * @var \AbstractDbAdapter
	 */
	private $db;

	/**
	 * @var \LinkParser
	 */
	private $linkParser;

	/**
	 * @var \Logger
	 */
	private $logger;

	/**
	 * @param array $configuration
	 */
	public function setConfiguration(array $configuration) {
		$this->configuration = $configuration;
	}

	/**
	 * @param string $dir
	 */
	public function setCacheDir($dir) {
		if (is_dir($dir) && is_writable($dir)) {
			$this->cacheDir = $dir;
		} else {
			$this->logger->log($dir . ': Directory either does not exits or not writable.', Logger::LOG_DEBUG);
		}
	}

	/**
	 * @param \AbstractDbAdapter $db
	 */
	public function setDbAdapter(\AbstractDbAdapter $db) {
		$this->db = $db;
	}

	/**
	 * @param LinkParser $linkParser
	 */
	public function setLinkParser(\LinkParser $linkParser) {
		$this->linkParser = $linkParser;
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
	 * @param  string $url
	 * @param  bool   $ignoreCache
	 * @return array
	 */
	public function getFrontendComparisonLinks($url, $ignoreCache = FALSE) {
		$this->linkParser->setConfiguration($this->configuration);
		$domain = parse_url($url, PHP_URL_HOST);

		if ($ignoreCache) {
			$links = array();
			$this->logger->log('Ignoring FE URL cache ...');
		} else {
			$links = $this->getCachedDomainLinks($domain);
		}

		if (count($links) < 1) {
			$this->logger->log('Crawling `' . $url . '` to collect sub-page URLs ...', Logger::LOG_DEBUG);
			$links = $this->linkParser->getAllLinksFromDomain($url);

			if (count($links)) {
				$this->cacheDomainLinks($links, $domain);
			}
		}

		return $links;
	}

	/**
	 * @param  string $domain
	 * @return array
	 */
	protected function getCachedDomainLinks($domain) {
		$cachedDomainLinks = array();
		$cacheFile = $this->getDomainUrlCacheFile($domain);

		if (file_exists($cacheFile)) {
			if (is_readable($cacheFile)) {
				$this->logger->log('Fetching URLs for domain `' . $domain . '` from cache ...', Logger::LOG_DEBUG);
				$cacheContent = @file_get_contents($cacheFile);
				$cachedDomainLinks = @unserialize($cacheContent);

				if (is_array($cachedDomainLinks)) {
					$this->logger->log('Found ' . count($cachedDomainLinks) . ' URLs in cache.', Logger::LOG_DEBUG);
				} else {
					$cachedDomainLinks = array();
					$this->logger->log('Failed to retrieve URLs from cache.', Logger::LOG_DEBUG);
				}
			} else {
				$this->logger->log($cacheFile . ': File not readable.', Logger::LOG_WARNING);
			}
		}

		return $cachedDomainLinks;
	}

	/**
	 * @param array  $links
	 * @param string $domain
	 */
	protected function cacheDomainLinks(array $links, $domain) {
		$this->logger->log('Caching ' . count($links). ' URLs for domain `' . $domain . '` ...', Logger::LOG_DEBUG);
		$cacheFile = $this->getDomainUrlCacheFile($domain);

		if (FALSE === @file_put_contents($cacheFile, serialize($links))) {
			$this->logger->log('Failed to cache URLs for domain `' . $domain . '`', Logger::LOG_WARNING);
		}
	}

	/**
	 * @param  string $domain
	 * @return string
	 */
	private function getDomainUrlCacheFile($domain) {
		return $this->cacheDir . $domain . '.cache.urls.php';
	}
}

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
class HttpClient {

	/**
	 * @var HttpResponse
	 */
	private $response;

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @param HttpResponse $response
	 */
	public function setHttpResponse(HttpResponse $response) {
		$this->response = $response;
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
	 * @return HttpResponse
	 */
	public function get($url) {
		$response = $this->getHttpResponse();

		if (!extension_loaded('curl')) {
			$this->logger->log('Http Utility: Missing PHP Curl module', Logger::LOG_ERROR);
		} else {
			$ch = curl_init($url);

			curl_setopt_array($ch, array(
				CURLOPT_HEADER => TRUE,
				CURLOPT_RETURNTRANSFER => TRUE,
				CURLOPT_FOLLOWLOCATION => FALSE,
				CURLOPT_SSL_VERIFYHOST => FALSE,
				CURLOPT_SSL_VERIFYPEER => FALSE,
				CURLOPT_FOLLOWLOCATION => TRUE,
				CURLOPT_MAXREDIRS      => 3,
				CURLOPT_TIMEOUT        => 30
			));

			$content = curl_exec($ch);

			if (FALSE === $content) {
				$response->setError(curl_error($ch));
				$content = '';
			}

			$responseInfo = curl_getinfo($ch);
			$headers = substr($content, 0, $responseInfo['header_size']);
			$contentWithoutHeaders = substr($content, $responseInfo['header_size']);
			$parsedHeaders = $this->parseResponseHeaders($headers);

			$response
				->setStatus($responseInfo['http_code'])
				->setEffectiveUrl($responseInfo['url'])
				->setHeaders($parsedHeaders)
				->setContent($contentWithoutHeaders);

			curl_close($ch);
		}

		return $response;
	}

	/**
	 * @return HttpResponse
	 */
	protected function getHttpResponse() {
		return (clone $this->response);
	}

	/**
	 * @param  string $headers
	 * @return array
	 */
	protected function parseResponseHeaders($headers) {
		$parsedHeaders = array();

		if (is_string($headers) && strlen($headers)) {
			$headers = str_replace("\r", "\n", $headers);
			$headers = array_filter(explode("\n", $headers));

			foreach ($headers as $header) {
				list($name, $value, ) = explode(': ', $header . ': ');

				if ($value) {
					$parsedHeaders[trim($name)] = trim($value);
				}
			}
		}

		return $parsedHeaders;
	}
}

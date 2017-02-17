<?php

/**
 * Created by PhpStorm.
 * User: chetan.thapliyal
 * Date: 22.04.2016
 * Time: 10:47
 */
class HttpResponse {

	const STATUS_OK = 200;
	const STATUS_PERM_REDIRECT = 301;
	const STATUS_TEMP_REDIRECT = 302;
	const STATUS_INTERNAL_SERVER_ERROR = 500;

	/**
	 * Http Status
	 *
	 * @var int
	 */
	private $status = 0;

	/**
	 * @var string
	 */
	private $content = '';

	/**
	 * @var array
	 */
	private $headers = array();

	/**
	 * @var string
	 */
	private $effectiveUrl = '';

	/**
	 * @var string
	 */
	private $error = '';

	/**
	 * @param  string $header
	 * @return string
	 */
	public function getHeader($header) {
		$headerValue = (is_string($header) && isset($header, $this->headers)) ? $this->headers[$header] : '';
		return $headerValue;
	}

	/**
	 * @return array
	 */
	public function getHeaders() {
		return $this->headers;
	}

	/**
	 * @return string
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * @return int
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @return string
	 */
	public function getEffectiveUrl() {
		return $this->effectiveUrl;
	}

	/**
	 * @return string
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * @param  array $headers
	 * @return $this
	 */
	public function setHeaders(array $headers) {
		$this->headers = $headers;
		return $this;
	}

	/**
	 * @param  $content
	 * @return $this
	 */
	public function setContent($content) {
		if (is_string($content)) {
			$this->content = $content;
		}

		return $this;
	}

	/**
	 * @param  int $status
	 * @return $this
	 */
	public function setStatus($status) {
		$status = filter_var($status, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0, 'default' => 0)));

		if ($status) {
			$this->status = $status;
		}

		return $this;
	}

	/**
	 * @param  string $url
	 * @return $this
	 */
	public function setEffectiveUrl($url) {
		if (is_string($url) && strlen($url)) {
			$this->effectiveUrl = $url;
		}

		return $this;
	}

	/**
	 * @param  string $error
	 * @return $this
	 */
	public function setError($error) {
		if (is_string($error) && strlen($error)) {
			$this->error = $error;
		}

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isError() {
		return (bool) strlen($this->error);
	}
}

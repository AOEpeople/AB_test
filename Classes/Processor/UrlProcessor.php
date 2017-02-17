<?php

require_once 'AbstractProcessor.php';

/**
 * Created by PhpStorm.
 * User: chetan.thapliyal
 * Date: 05.08.2016
 * Time: 11:46
 */
class UrlProcessor extends AbstractProcessor {

	/**
	 * @var array
	 */
	private $whitelist;

	/**
	 * @var array
	 */
	private $blacklist;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->whitelist = array();
		$this->blacklist = array();
	}

	public function process($content) {
		$domain = parse_url($content, PHP_URL_HOST);

		if (isset($this->configuration[$domain]['processors']['url'])) {
			$urlConfiguration = $this->configuration[$domain]['processors']['url'];

			if (is_array($urlConfiguration)) {
				foreach ($urlConfiguration as $action => $configuration) {
					if (method_exists($this, $action)) {
						$content = $this->$action($content, $configuration);
					}
				}
			}
		}

		return $content;
	}

	private function whitelist($url, array $configurations) {
		$status = TRUE;
		foreach ($configurations as $configuration) {
			$curl = isset($configuration['url']) ? $configuration['url'] : '';
			$limit = isset($configuration['limit']) ? $configuration['limit'] : '';
			$limit = intval($limit);

			if ($curl{0} === substr($curl, -1, 1)) {
				if (preg_match($curl, $url)) {
					if(!isset($this->whitelist[$curl])) $this->whitelist[$curl] = 0;

					if ($limit > $this->whitelist[$curl]) {
						$this->whitelist[$curl] += 1;
						$status = TRUE;
					} else {
						$status = FALSE;
						break;
					}
				}
			}
		}

		return $status;
	}
}

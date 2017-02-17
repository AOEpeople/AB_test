<?php

/**
 * Created by PhpStorm.
 * User: chetan.thapliyal
 * Date: 03.08.2016
 * Time: 09:22
 */
abstract class AbstractProcessor {

	/**
	 * @var array
	 */
	protected $configuration;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->configuration = array();
	}

	/**
	 * @param array $configuration
	 */
	public function setConfiguration(array $configuration) {
		$this->configuration = $configuration;
	}

	abstract function process($content);
}

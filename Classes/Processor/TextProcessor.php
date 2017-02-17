<?php

require_once 'AbstractProcessor.php';

/**
 * Created by PhpStorm.
 * User: chetan.thapliyal
 * Date: 02.08.2016
 * Time: 17:31
 */
class TextProcessor extends AbstractProcessor {

	public function process($content) {
		foreach ($this->configuration as $action => $configuration) {
			if (method_exists($this, $action)) {
				$content = $this->$action($content, $configuration);
			}
		}

		return $content;
	}

	/**
	 * @param  string $content
	 * @param  array  $configuration
	 * @return string
	 */
	public function substitute($content, array $configuration) {
		$regex = $simple = array();

		foreach($configuration as $_search => $_replace) {
			if ($_search{0} === substr($_search, -1, 1)) {
				$regex[$_search] = $_replace;
			} else {
				$simple[$_search] = $_replace;
			}
		}
		
		uksort($simple, function($val1, $val2) {
			return strlen($val2) - strlen($val1);
		});

		$content = str_replace(array_keys($simple), array_values($simple), $content);

		foreach ($regex as $pattern => $replace) {
			$count = 0;
			$content = preg_replace_callback($pattern, function() use (&$count, $replace) {
				return str_replace('%c', ++$count, $replace);
			}, $content);
		}

		return $content;
	}
}

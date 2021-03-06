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

require_once PROJECT_ROOT . 'Classes/Controller/AbstractController.php';
require_once PROJECT_ROOT . 'Classes/Domain/Repository/LinkRepository.php';


/**
 * @author Erik Frister <erik.frister@aoe.com>
 * @author Chetan Thapliyal <chetan.thapliyal@aoe.com>
 */
class ABTestController extends AbstractController
{

    const CMP_TYPE_FRONTEND = 'FRONTEND';
    const CMP_TYPE_SOURCE = 'SOURCE';
    const DEFAULT_MAX_FAIL_COUNT = 100;
    /**
     * Key/value pair of original and comparison API domains.
     * No trailing slash.
     *
     * @var array
     */
    protected $apiDomains;
    /**
     * Key/value pair of original and comparison FE domains.
     * No trailing slash.
     *
     * @var array
     */
    protected $frontendDomains;

    /**
     * @var Logger
     */
    private $testResultLogger;

    /**
     * Flag to stop comparison if given fail count reaches.
     * @var int
     */
    private $maxFailCount;

    /**
     * @var Compare
     */
    private $compareUtility;

    /**
     * @var LinkRepository
     */
    private $linkRepository;

    /**
     * @param Compare $compareUtility
     * @return $this
     */
    public function setCompareUtility(Compare $compareUtility)
    {
        $this->compareUtility = $compareUtility;
        return $this;
    }

    /**
     * @param  Logger $logger
     * @return $this
     */
    public function setTestResultLogger(Logger $logger)
    {
        $this->testResultLogger = $logger;
        return $this;
    }

    /**
     * @param int|NULL $maxFailCount
     */
    public function setMaxFailCount($maxFailCount = NULL)
    {
        if (isset($this->arguments['max-fail-count'])) {
            $maxFailCount = intval($this->arguments['max-fail-count']);
            $maxFailCount = filter_var(
                $maxFailCount,
                FILTER_VALIDATE_INT,
                array('options' => array('min_range' => 1, 'default' => self::DEFAULT_MAX_FAIL_COUNT))
            );
        }

        $this->maxFailCount = $maxFailCount;
    }

    /**
     * @param LinkRepository $repository
     * @return $this
     */
    public function setLinkRepository(LinkRepository $repository)
    {
        $repository->setCacheDir(PROJECT_ROOT . 'Tmp/Cache/');
        $this->linkRepository = $repository;
        $this->linkRepository->setConfiguration($this->getConfiguration());
        return $this;
    }

    public function initialize()
    {
        $this->compareUtility->setConfiguration($this->getConfiguration());
        $this->setMaxFailCount($this->arguments['max-fail-count']);
    }

    /**
     * @return void
     */
    public function defaultAction()
    {
        $this->compareFrontendAction();
    }


    /**
     * Compare response from frontend requests.
     *
     * @return void
     */
    public function compareFrontendAction()
    {
        $failCount = 0;
        $ignoreCache = isset($this->arguments['ignore-cache']);
        $compareDomains = $this->getComparisonDomains(self::CMP_TYPE_FRONTEND);

        foreach ($compareDomains as $remoteDomain => $localDomain) {
            $remoteUrl = 'http://' . $remoteDomain;
            $links = $this->linkRepository->getFrontendComparisonLinks($remoteUrl, $ignoreCache);

            foreach ($links as $i => $link) {
                if (($i % 50) == 0) {
                    $this->logger->log(sprintf('Processing %5s ... %s', ($i + 1), ($i + 50)));
                }

                $originalUrl = $link;
                $compareUrl = str_replace($remoteDomain, $localDomain, $link);

                list(, $path) = explode($remoteDomain, $link);
                $path = empty($path) ? '/' : $path;

                $this->logger->log('Comparing URI: ' . $path, Logger::LOG_DEBUG);

                    // do a text comparison first...
                $text_comparison_ok = $this->compare($originalUrl, $compareUrl);
                $failCount += ($text_comparison_ok == FALSE) ? 1 : 0;

                $this->logger->log('Create Screenshot...', Logger::LOG_DEBUG);
                if (!$this->compareUtility->compareScreenshot($originalUrl, $compareUrl)) {
                    $failCount++;

                    $this->logger->log('Failure!', Logger::LOG_DEBUG);
                    $logMsg = sprintf("Screenshot comparison info ...\nOriginal URL: %s\nCompare URL: %s\n\n", $originalUrl, $compareUrl);
                    $this->testResultLogger->log($logMsg);
                    $this->logger->log($logMsg, Logger::LOG_DEBUG);
                }

                if ($failCount >= $this->maxFailCount && !is_null($this->maxFailCount)) {
                    $this->logger->log('Maximum failed count limit of ' . $this->maxFailCount . ' reached.');
                    $this->logger->log('Discarding further processing.');
                    break;
                }

            }

            $this->logger->log('Finished FE comparison');

            if ($failCount > 0) {
                $this->logger->log('Fail count: ' . $failCount);
                exit(1);
            }
        }
    }

    /**
     * Get domains to compare.
     *
     * @param  string $comparisonType
     * @return array
     */
    protected function getComparisonDomains($comparisonType)
    {
        $comparisonDomains = array();

        foreach ($this->getConfiguration() as $identifier => $subConfiguration) {
            if (strtoupper($identifier) !== 'GLOBAL'
                && isset($subConfiguration['comparisonType'])
                && (in_array($comparisonType, array_map('strtoupper', $subConfiguration['comparisonType'])))
            ) {

                $comparisonDomains[$identifier] = $subConfiguration['compareDomain'];
            }
        }

        if (isset($this->arguments['url']) && count($comparisonDomains)) {
            $domains = array();

            if (!is_array($this->arguments['url'])) {
                $domains = array(parse_url($this->arguments['url'], PHP_URL_HOST));
            } else {
                foreach ($this->arguments['url'] as $url) {
                    $domains[] = parse_url($url, PHP_URL_HOST);
                }
            }

            // just keep intersection of arrays $comparisonDomains and $domains
            $comparisonDomains = array_intersect_key($comparisonDomains, array_flip($domains));
        }

        return $comparisonDomains;
    }

    /**
     * @param  string $originalUrl
     * @param  string $compareUrl
     *
     * @return bool
     */
    protected function compare($originalUrl, $compareUrl)
    {
        $comparisonStatus = FALSE;
        $diff = 'Not available';

        try {
            $failure = $this->compareUtility->compareText($originalUrl, $compareUrl, $diff) ^ TRUE;
            $failureReason = '';
        } catch (SkipException $e) {
            $failure = TRUE;
            $failureReason = $e->getMessage();
        }

        if ($failure == TRUE) {
            $failureReason = $failureReason ? $failureReason : 'Text comparison failure!';
            $logMsg = sprintf(
                "Text comparison info ...\nOriginal URL: %s\nCompare URL: %s\nDiff: %s\n",
                $originalUrl,
                $compareUrl,
                $diff
            );

            $this->testResultLogger->log($logMsg);
            $this->logger->log($failureReason, Logger::LOG_DEBUG);
            $this->logger->log($logMsg, Logger::LOG_DEBUG);
        } else {
            $comparisonStatus = TRUE;
        }

        return $comparisonStatus;
    }
}

<?php

class TenN_JobHealth_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Constant got providing the cron exporession when building a URL
     */
    const PARAM_CRON = 'cron';

    /**
     * Constant denoting the job status when building a URL
     */
    const PARAM_STATUS = 'status';

    /**
     * Constant denoting the elapsed time when building a URL
     */
    const PARAM_ELAPSED = 'elapsed';

    /**
     * Constant denoting the time zone when building a URL
     */
    const PARAM_TIMEZONE = 'tz';

    /**
     * Constant denoting the next run time when building a URL
     */
    const PARAM_NEXT_RUN = 'next_run';

    /**
     * Constant for getting the proper name for the base helper
     */
    const HELPER = 'tenn_jobhealth';

    /**
     * The Health Vault token location in the system configuration
     */
    const CONFIG_TOKEN = 'system/job_health/token';

    /**
     * The enable setting location in the system configuration
     */
    const CONFIG_ENABLED = 'system/job_health/enabled';

    /**
     * The enable setting location for logging in the system configuration
     */
    const CONFIG_LOGGING_ENABLED = 'system/job_health/logging_enabled';

    /**
     * The setting location for the name of the log file in the system configuration
     */
    const CONFIG_LOG_FILE = 'system/job_health/log_file';

    /**
     * The setting location for the always cron expression in the system configuration
     */
    const CONFIG_ALWAYS = 'system/job_health/always';

    /**
     * The healthy status
     */
    const STATUS_HEALTHY = 'healthy';

    /**
     * The degraded status
     */
    const STATUS_DEGRADED = 'degraded';

    /**
     * The failed status
     */
    const STATUS_FAILED = 'failed';

    protected $acceptableParams = [
        self::PARAM_CRON,
        self::PARAM_STATUS,
        self::PARAM_NEXT_RUN,
        self::PARAM_ELAPSED,
        self::PARAM_TIMEZONE
    ];

    /**
     * Is the functionality enabled?
     *
     * @return bool
     */
    public function isEnabled() {
        return Mage::getStoreConfigFlag(self::CONFIG_ENABLED);
    }


    /**
     * Retrieves the system timezone for the site.
     *
     * @return string
     */

    public function getTimezone()
    {
        $tz =@date_default_timezone_get();
        if (!$tz) {
            $tz = Mage_Core_Model_Locale::DEFAULT_TIMEZONE;
        }
        return $tz;
    }

    /**
     * Retrieves the token from the system configuration
     *
     * @return mixed|null
     */

    public function getToken()
    {
        return Mage::getStoreConfig(self::CONFIG_TOKEN);
    }

    /**
     * Retrieves the always cron expression
     *
     * @return mixed|null
     */

    public function getAlwaysExpression()
    {
        return Mage::getStoreConfig(self::CONFIG_ALWAYS);
    }

    /**
     * Builds a formatted URL to send to the vault
     *
     * @param string $jobCode
     * @param array $params
     * @return string The URL
     * @throws TenN_JobHealth_Helper_InvalidParameterException
     */

    public function buildUrl($jobCode, array $params)
    {
        foreach ($params as $key => $value) {
            if (!in_array($key, $this->acceptableParams)) {
                throw new TenN_JobHealth_Helper_InvalidParameterException($key . ' is not an allowed parameter');
            }
        }

        $url = 'https://vh.10n-software.com/i/'
            . $this->getToken() . '/'
            . $jobCode . '?'
            . http_build_query($params);
        return $url;
    }

    /**
     * Send a message to the log file
     *
     * @param $message
     * @param int $level
     */

    public function log($message, $level = Zend_Log::INFO)
    {
        $enabled = Mage::getStoreConfigFlag(self::CONFIG_LOGGING_ENABLED);
        $fileName = Mage::getStoreConfig(self::CONFIG_LOG_FILE);
        if ($enabled && $fileName) {
            Mage::log($message, $level, $fileName);
        }
    }

}

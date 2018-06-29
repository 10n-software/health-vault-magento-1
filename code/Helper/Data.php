<?php

class TenN_JobHealth_Helper_Data extends Mage_Core_Helper_Abstract
{
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
     * Is the functionality enabled?
     *
     * @return bool
     */
    public function isEnabled() {
        return Mage::getStoreConfigFlag(self::CONFIG_ENABLED);
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

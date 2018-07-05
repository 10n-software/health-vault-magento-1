<?php

class TenN_JobHealth_Model_Cron extends Mage_Cron_Model_Observer
{

    /**
     * Overrides the job processing functionality in Mage_Cron_Model_Observer so that it can capture timings and statuses
     * of the current test run.
     *
     * @see Mage_Cron_Model_Observer
     * @param Mage_Cron_Model_Schedule $schedule
     * @param $jobConfig
     * @param bool $isAlways
     * @return Mage_Cron_Model_Observer
     * @throws Exception
     */

    protected function _processJob($schedule, $jobConfig, $isAlways = false)
    {
        if (!$this->getHelper()->isEnabled()) {
            return parent::_processJob($schedule, $jobConfig, $isAlways);
        }

        $jobCode = 'unknown';
        if ($schedule instanceof Mage_Cron_Model_Schedule) {
            $jobCode = $schedule->getJobCode();
        }
        try {
            $startTime = microtime(true);
            $result = parent::_processJob($schedule, $jobConfig, $isAlways);
            $elapsed = (microtime(true) - $startTime);
            $this->sendExecutionEvent($schedule, $jobConfig, $jobCode, intval($elapsed * 1000), $isAlways);
        } catch (\Exception $e) {
            $elapsed = (microtime(true) - $startTime);
            $this->sendExecutionEvent($schedule, $jobConfig, $jobCode, intval($elapsed * 1000), $isAlways, 'failed', $schedule->getMessages());
            throw $e;
        }
        return $result;
    }

    /**
     * Sends the execution event to the vault API server.
     *
     * @param $schedule
     * @param $jobConfig
     * @param $jobCode
     * @param int $elapsed time in millinseconds
     * @param bool $isAlways
     * @param null $status
     * @param null $message
     */

    public function sendExecutionEvent($schedule, $jobConfig, $jobCode, $elapsed, $isAlways = false, $status = null, $message = null)
    {
        if ($this->getHelper()->isEnabled()) {
            $url = $this->getReportUrl($schedule, $jobConfig, $elapsed, $status, $isAlways);
            $this->send($url, $jobCode, $message);
        }
    }

    /**
     * Sends the job update request to the Vault server
     *
     * @param $url
     */

    public function send($url, $jobCode, $content = null)
    {
        if ($url) {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            if ($content) {
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
            }
            $result = curl_exec($curl);
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($code == 200) {
                $this->getHelper()->log($result);
            } else {
                $this->getHelper()->log("Job updated failed for {$jobCode}:\nCode: {$code}\n{$result}", Zend_Log::ALERT);
            }
            curl_close($curl);
        }
    }

    /**
     * Builds the API request URL based off of the results of the job execution
     *
     * @param $schedule
     * @param $jobConfig
     * @param $elapsed
     * @param string $status
     * @param $isAlways
     * @return null|string
     */

    public function getReportUrl($schedule, $jobConfig, $elapsed, $status, $isAlways)
    {
        if ($schedule instanceof Mage_Cron_Model_Schedule) {
            if ($schedule->getStatus() == Mage_Cron_Model_Schedule::STATUS_PENDING) {
                return null; // Nothing to see here
            }
            $token = $this->getHelper()->getToken();
            if ($token) {
                if ($schedule->getStatus() != Mage_Cron_Model_Schedule::STATUS_SUCCESS) {
                    $status = 'failed';
                }
                $params = [
                    'elapsed' => $elapsed,
                    'tz' => $this->getTimezone()
                ];
                if ($status) {
                    $params['status'] = $status;
                }
                if ($isAlways) {
                    $expression = $this->getHelper()->getAlwaysExpression();
                    if ($expression) {
                        $params['cron'] = (string)$expression;
                    }
                } else {
                    $nextRun  = $this->getNextRun($schedule);
                    if ($nextRun) {
                        $params['next_run'] = (int)$nextRun->format('U');
                    } else if (!empty($jobConfig->schedule->cron_expr)){
                        $params['cron'] = (string)$jobConfig->schedule->cron_expr;
                    }
                }

                $url = 'https://vh.10n-software.com/i/'
                    . $token . '/'
                    . $schedule->getJobCode() . '?'
                    . http_build_query($params);
                return $url;
            }
        }
        return null;
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
     * Gets the next run for the scheduled item, if it exists
     *
     * @param Mage_Cron_Model_Schedule $schedule
     * @return DateTime
     */

    public function getNextRun(Mage_Cron_Model_Schedule $schedule)
    {
        $collection = $schedule->getCollection();
        if ($collection instanceof Mage_Cron_Model_Resource_Schedule_Collection) {
            $collection->addFieldToFilter('job_code', $schedule->getJobCode());
            $collection->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_PENDING);
            $collection->setOrder('scheduled_at', 'ASC');
            $collection->setPageSize(1);
            $collection->addFieldToFilter('executed_at', ['null' => true]);
            $nextSchedule = $collection->getFirstItem();
            if ($nextSchedule instanceof Mage_Cron_Model_Schedule && $nextSchedule->getId()) {
                return new DateTime($nextSchedule->getScheduledAt());
            }
        }
        return null;
    }

    /**
     * Retrieves the helper.
     *
     * @return TenN_JobHealth_Helper_Data
     */

    public function getHelper()
    {
        return Mage::helper(TenN_JobHealth_Helper_Data::HELPER);
    }

}

# Magento 1 Job Health Vault Adapter

## Introduction
The Magento 1 integration will automatically create all new jobs as they are executed as they are found in the cron_schedule table. It also will automatically send elapsed time for each job so you can track how each job is performing.

Visit [10n Job Health Vault](https://vh.10n-software.com)

## Installation
The source can be found on the Github library page. You can either copy the source code from there, use modman , or  composer.

### Modman Installation
```
cd <magento directory>
modman init
modman clone https://github.com/10n-software/health-vault-magento-1.git
```
        
### Composer Installation
```
cd <magento directory>
composer require 10n/health-vault-magento1
```
        
## Configuration
Configuration is managed via the System Configuration in the administration UI.

![admin configuration](https://vh.10n-software.com/static/img/configuration.ae26ee5.png)

### Enabled
Flag for enabling or disabling the vault. If this is set to "No" the module will still be enabled, but it will not do anything.
### Token
This is the project-specific token. Get this from your project home page.
### Logging Enabled
Specifies if you want to log communication with the API. There is generally no need to disable this.
### "Always" Job Schedule
Magento has two different cron modes, "default" and "always". "Default" behaves the same way that your system cron does but "always" is run each time cron is run. It is usually used for very quick tasks that need to be executed as soon as possible. Generally, cron should be run once per minute and if so the default "* * * * *" should be fine. But if the Magento cron is run under a different schedule you will need to set it here to make sure that the next expected run timestamp for "always" tasks is set correctly.
### Log Filename
This allows you to use a different filename for the API logger. You generally should not need to change this.
The Magento integration rewrites the `Mage_Cron_Schedule_Observer` class. If you have a customization that also overrides the cron observer you will need to make your module dependent on  TenN_JobHealth so it is loaded afterwards, and overwrites the TenN_JobHealth rewrite.

```
<?xml version="1.0"?>
<config>
    <modules>
        <My_Module>
            <active>true</active>
            <codePool>local</codePool>
            <depends>
                <TenN_JobHealth/>
            </depends>
        </My_Module>
    </modules>
</config>
```
Then, in your rewrite you will need to pass in the job information, elapsed time, and status.

```
class MyModule_Model_Cron extends Mage_Cron_Model_Observer
{

    protected function _processJob($schedule, $jobConfig, $isAlways = false)
    {
        $startTime = microtime(true);
        $result = parent::_processJob($schedule, $jobConfig, $isAlways);
        $elapsed = microtime(true) - $startTime;
        $jobCode = $schedule->getJobCode();
        Mage::getModel('tenn_jobhealth')->sendExecutionEvent($schedule, $jobConfig, $jobCode, $elapsed, $isAlways);

        return $result;
    }
}
```        
But note, this is only pertinent if you have an existing module that rewrites `Mage_Cron_Schedule_Observer`.

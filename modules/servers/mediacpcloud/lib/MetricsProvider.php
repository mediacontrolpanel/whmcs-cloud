<?php
namespace WHMCS\Module\Server\Mediacpcloud;

use WHMCS\UsageBilling\Contracts\Metrics\MetricInterface;
use WHMCS\UsageBilling\Contracts\Metrics\ProviderInterface;
use WHMCS\UsageBilling\Metrics\Metric;
use WHMCS\UsageBilling\Metrics\Units\Accounts;
use WHMCS\UsageBilling\Metrics\Units\GigaBytes;
use WHMCS\UsageBilling\Metrics\Units\WholeNumber;
use WHMCS\UsageBilling\Metrics\Usage;

class MetricsProvider implements ProviderInterface
{
    private $moduleParams = [];

    public function __construct($moduleParams)
    {
        // A sample `$params` array may be defined as:
        //
        // ```
        // array(
        //     "server" => true
        //     "serverid" => 1
        //     "serverip" => "11.111.4.444"
        //     "serverhostname" => "my.testserver.tld"
        //     "serverusername" => "root"
        //     "serverpassword" => ""
        //     "serveraccesshash" => "ZZZZ1111222333444555AAAA"
        //     "serversecure" => true
        //     "serverhttpprefix" => "https"
        //     "serverport" => "77777"
        // )
        // ```
        $this->moduleParams = $moduleParams;
    }

    public function metrics()
    {
        return [
            new Metric(
                'cdnBandwidth',
                'CDN Bandwidth',
                MetricInterface::TYPE_PERIOD_MONTH,
                new GigaBytes
            ),
            new Metric(
                'mslEgressBandwidth',
                'MSL Egress Bandwidth',
                MetricInterface::TYPE_PERIOD_MONTH,
                new GigaBytes()
            ),
            new Metric(
                'diskSpace',
                'Disk Space',
                MetricInterface::TYPE_PERIOD_MONTH,
                new GigaBytes()
            ),
            new Metric(
                'channels',
                'Channels',
                MetricInterface::TYPE_SNAPSHOT,
                new WholeNumber('Channels', 'channel', 'channels')
            ),
        ];
    }

    public function usage()
    {
        $serverData = $this->apiCall('stats');
        $usage = [];
        foreach ($serverData as $data) {
            $usage[$data['username']] = $this->wrapUserData($data);
        }

        return $usage;
    }

    public function tenantUsage($tenant)
    {
        $userData = $this->apiCall('user_stats');

        return $this->wrapUserData($userData);
    }

    private function wrapUserData($data)
    {
        $wrapped = [];
        foreach ($this->metrics() as $metric) {
            $key = $metric->systemName();
            if ($data[$key]) {
                $value = $data[$key];
                $metric = $metric->withUsage(
                    new Usage($value)
                );
            }

            $wrapped[] = $metric;
        }

        return $wrapped;
    }

    private function apiCall($action)
    {
        // make remote call with $moduleParams
    }
}
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
                'cdn_bandwidth',
                'CDN Bandwidth',
                MetricInterface::TYPE_PERIOD_MONTH,
                new GigaBytes
            ),
            new Metric(
                'egress_bandwidth',
                'MSL Egress Bandwidth',
                MetricInterface::TYPE_PERIOD_MONTH,
                new GigaBytes()
            ),
            new Metric(
                'storage',
                'Disk Space',
                MetricInterface::TYPE_PERIOD_MONTH,
                new GigaBytes()
            ),
            new Metric(
                'channels_count',
                'Channels',
                MetricInterface::TYPE_SNAPSHOT,
                new WholeNumber('Channels', 'channel', 'channels')
            ),
        ];
    }

    public function usage()
    {
	$usage = [];
	$server = DB::table('tblservers')->find($this->moduleParams['serverid']);
	$hosting = DB::table('tblhosting')
	->leftJoin('tblproducts','tblproducts.id','=','tblhosting.packageid') #packageid
	->where('server',$this->moduleParams['serverid'])
	->where('domainstatus','Active')
	->where('servertype','mediacpcloud');

	if ( $hosting->count() > 0 ){

		foreach($hosting->get() as $acc){
			$usage[$acc->username] = $this->tenantUsage($acc->domain);
		}

	}

        return $usage;
    }

    public function tenantUsage($customerId)
    {
        $userData = $this->apiCall('/api/customers/' . $customerId. '/stats');

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
        $requestUrl = requestUrl($this->moduleParams['serverhttpprefix'], $this->moduleParams['serverhostname'], $this->moduleParams['serverport'], $action);
        $response = request('get', $requestUrl, $this->moduleParams['serveraccesshash']);

        return \json_decode($response, true);
    }

    private function request($action, $url, $token, $params = [])
    {
        $payload = \json_encode($params);

        // Prepare new cURL resource
        $ch = \curl_init();
        \curl_setopt($ch, CURLOPT_URL, $url);
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        switch ($action) {
            case 'get':
                \curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Accept: application/json',
                    'Authorization: Bearer ' . $token
                ]);
                break;
            case 'post':
                \curl_setopt($ch, CURLOPT_POST, true);
                \curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

                // Set HTTP Header for POST request
                \curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Accept: application/json',
                        'Content-Length: ' . strlen($payload),
                        'Authorization: Bearer ' . $token
                    ]
                );
                break;
            case 'delete':
                \curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                // Set HTTP Header for POST request
                \curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Accept: application/json',
                    'Authorization: Bearer .' . $token
                ]);
                break;

        }

        // Submit the POST request
        $result = \curl_exec($ch);

        // Close cURL session handle
        \curl_close($ch);

        return $result;
    }
}

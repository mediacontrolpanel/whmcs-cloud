<?php

use WHMCS\Module\Server\mediacpcloud\MetricsProvider;

/**
 * WHMCS SDK Sample Provisioning Module
 *
 * Provisioning Modules, also referred to as Product or Server Modules, allow
 * you to create modules that allow for the provisioning and management of
 * products and services in WHMCS.
 *
 * This sample file demonstrates how a provisioning module for WHMCS should be
 * structured and exercises all supported functionality.
 *
 * Provisioning Modules are stored in the /modules/servers/ directory. The
 * module name you choose must be unique, and should be all lowercase,
 * containing only letters & numbers, always starting with a letter.
 *
 * Within the module itself, all functions must be prefixed with the module
 * filename, followed by an underscore, and then the function name. For this
 * example file, the filename is "mediacpcloud" and therefore all
 * functions begin "mediacpcloud_".
 *
 * If your module or third party API does not support a given function, you
 * should not define that function within your module. Only the _ConfigOptions
 * function is required.
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/provisioning-modules/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license https://www.whmcs.com/license/ WHMCS Eula
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

// Require any libraries needed for the module to function.
// require_once __DIR__ . '/path/to/library/loader.php';
//
// Also, perform any initialization required by the service's library.

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related abilities and
 * settings.
 *
 * @see https://developers.whmcs.com/provisioning-modules/meta-data-params/
 *
 * @return array
 */
function mediacpcloud_MetaData()
{
    return array(
        'DisplayName' => 'MediaCP Cloud',
        'APIVersion' => '1.1', // Use API Version 1.1
        'RequiresServer' => true, // Set true if module requires a server to work
        'DefaultNonSSLPort' => '80', // Default Non-SSL Connection Port
        'DefaultSSLPort' => '443', // Default SSL Connection Port
        'ServiceSingleSignOnLabel' => 'Login to Panel as User',
        'AdminSingleSignOnLabel' => 'Login to Panel as Admin',
		'ListAccountsUniqueIdentifierDisplayName' => 'Customer ID',
		'ListAccountsUniqueIdentifierField' => 'domain',
		'ListAccountsProductField' => 'configoption1',
    );
}

/**
 * Define product configuration options.
 *
 * The values you return here define the configuration options that are
 * presented to a user when configuring a product for use with the module. These
 * values are then made available in all module function calls with the key name
 * configoptionX - with X being the index number of the field from 1 to 24.
 *
 * You can specify up to 24 parameters, with field types:
 * * text
 * * password
 * * yesno
 * * dropdown
 * * radio
 * * textarea
 *
 * Examples of each and their possible configuration parameters are provided in
 * this sample function.
 *
 * @see https://developers.whmcs.com/provisioning-modules/config-options/
 *
 * @return array
 */
function mediacpcloud_ConfigOptions()
{
    return array(
        'plan' => [
            'FriendlyName' => 'Plan',
            'Type' => 'text',
            'Size' => '25',
            'Loader' => 'mediacpcloud_LoaderFunction',
            'SimpleMode' => true,
        ],
		'create_live' => [
			'FriendlyName' => 'Create Live',
            'Type'         => 'yesno',
            'Size'         => '25',
            'Description'  => 'Tick to automatically create a live channel',
		],
		'create_tv' => [
			'FriendlyName' => 'Create TV',
            'Type'         => 'yesno',
            'Size'         => '25',
            'Description'  => 'Tick to automatically create a tv channel',
		],
    );
}

function mediacpcloud_MetricProvider($params)
{
    return new MetricsProvider($params);
}

/**
 * Loader function that will populate the field in ConfigOptions
 * @return array The list of package names
 * @throws Exception
 */
function mediacpcloud_LoaderFunction($params)
{
    $requestUrl = requestUrl($params['serverhttpprefix'], $params['serverhostname'], $params['serverport'], '/api/plans');

    $response = request('get', $requestUrl, $params['serveraccesshash']);

    // Attempt to decode the response
    $packages = json_decode($response, true);

    // Check to make sure valid json was returned
    if (is_null($packages)) {
        throw new Exception('Invalid response format');
    }

    logModuleCall(
        'mediacpcloud',
        __FUNCTION__,
        json_encode($params),
        $response,
        $response
    );

    // Format the list of values for display
    // ['value' => 'Display Label']
    $list = [];
    foreach ($packages as $package) {
        $list[$package['name']] = ucfirst($package['name']);
    }

    return $list;
}


function mediacpcloud_ListAccounts(array $params){
		
    $requestUrl = requestUrl($params['serverhttpprefix'], $params['serverhostname'], $params['serverport'], '/api/customers');
    $response = request('get', $requestUrl, $params['serveraccesshash']);

    // Attempt to decode the response
	$accounts = [];
	foreach(json_decode($response,true) as $account){
		$accounts[] = [

		// The remote accounts email address
		'email' => $account['email'], 
		
		// The remote accounts username
		'username' => $account['email'], 
		// The remote accounts primary domain name
		'domain' => $account['id'], 
		// This can be one of the above fields or something different.
		// In this example, the unique identifier is the domain name
		'uniqueIdentifier' => $account['id'], 
		// The accounts package on the remote server
		'product' => $account['plan'],
		// The remote accounts primary IP Address
		'primaryip' => '', 
		// The remote accounts creation date (Format: Y-m-d H:i:s)
		'created' => $account['created_at'], 
		// The remote accounts status (Status::ACTIVE or Status::SUSPENDED)
		'status' => $account['suspended_at'] === NULL ? Status::ACTIVE : Status::SUSPENDED, 
		];
	}
	
    logModuleCall(
        'mediacpcloud',
        __FUNCTION__,
		json_encode([
			'url' => $requestUrl,
			'token' => $params['serveraccesshash'],
			'payload' => []
		]),
        $response,
        $response
    );
	
	return [
		'success' => true,
		'accounts' => $accounts,
	];
}


/**
 * Provision a new instance of a product/service.
 *
 * Attempt to provision a new instance of a given product/service. This is
 * called any time provisioning is requested inside of WHMCS. Depending upon the
 * configuration, this can be any of:
 * * When a new order is placed
 * * When an invoice for a new order is paid
 * * Upon manual request by an admin user
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function mediacpcloud_CreateAccount(array $params)
{
    try {
        $payload = [
            'name'  => $params['clientsdetails']['fullname'],
            'email' => $params['clientsdetails']['email'],
            'password' => $params['password'],
            'plan' => $params['configoption1'],
            'password_confirmation' => $params['password'],
        ];
        $requestUrl = requestUrl($params['serverhttpprefix'], $params['serverhostname'], $params['serverport'], '/api/customers');
        $response = request('post', $requestUrl, $params['serveraccesshash'], $payload);

        logModuleCall(
            'mediacpcloud',
            __FUNCTION__,
            json_encode([
                'url' => $requestUrl,
                'token' => $params['serveraccesshash'],
                'payload' => $payload
            ]),
            $response,
            $response
        );

        $customer = \json_decode($response, true);

        if (isset($customer['message'])) {
            return $customer['message'];
        }

        $results = localAPI('UpdateClientProduct', [
            'domain' => $customer['id'],
            'serviceid' => $params['serviceid']
        ]);

        if ($results['result'] !== 'success') {
            logModuleCall(
                'mediacpcloud',
                __FUNCTION__,
                json_encode([
                    'domain' => $customer['id'],
                    'serviceid' => $params['serviceid']
                ]),
                $results['result'],
                $results['result']
            );

            return $results['result'];
        }

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'mediacpcloud',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }
	
	# Update WHMCS Username
	# TODO: Move this into eloquent style
	full_query("UPDATE tblhosting SET username='".  $customer['email']  ."' WHERE id='".$params["accountid"]."'");
	
	# Lookup plan details if creating live or tv channel
	if ( $params['configoption2'] == 'on' || $params['configoption3'] == 'on' ){
		# Lookup plan details for bitrate
		$requestUrl = requestUrl($params['serverhttpprefix'], $params['serverhostname'], $params['serverport'], "/api/plans");
		$response = request('get', $requestUrl, $params['serveraccesshash']);
		$plans = \json_decode($response);
		$bitrate = 4092;
		foreach($plans as $plan){
			if ($plan->name == $params['configoption1']) break;
		}
	}
	
	# Create live channel
	if ( $params['configoption2'] == 'on' ){
		
		$channelName = $params['clientsdetails']['fullname'] . "'s Channel";
		if ( !empty($params['clientdetails']['companyname']) && strlen($params['clientdetails']['companyname']) > 2 ){
			$channelName = $params['clientdetails']['companyname'];
		}

		try {
			$payload = [
				'name' => $channelName,
				'type' => 'live',
				'bitrate' => $plan->bitrate, // Default to plan bitrate
				'allow_publishing' => $plan->allow_publishing ?? false,
				'allow_rewind' => $plan->allow_rewind ?? false,
				'allow_recording' => $plan->allow_recording ?? false,
				'reduced_latency' => $plan->reduced_latency ?? false,
				'allow_advertising' => $plan->allow_advertising ?? false,
			];
			$requestUrl = requestUrl($params['serverhttpprefix'], $params['serverhostname'], $params['serverport'], "/api/tenants/customers/{$customer['id']}/channels");
			$response = request('post', $requestUrl, $params['serveraccesshash'], $payload);
			var_dump($response);exit;
		} catch (Exception $e) {
			// Record the error in WHMCS's module log.
			logModuleCall(
				'mediacpcloud',
				__FUNCTION__,
				$params,
				$e->getMessage(),
				$e->getTraceAsString()
			);

			return $e->getMessage();
		}
	}
	
	# Create tv channel
	if ( $params['configoption3'] == 'on' ){
		
		$channelName = $params['clientsdetails']['fullname'] . "'s TV Channel";
		if ( !empty($params['clientdetails']['companyname']) && strlen($params['clientdetails']['companyname']) > 2 ){
			$channelName = $params['clientdetails']['companyname'];
		}

		try {
			$payload = [
				'name' => $channelName,
				'type' => 'tv',
				'bitrate' => $plan->bitrate, // Default to plan bitrate
				'allow_publishing' => $plan->allow_publishing ?? false,
				'allow_rewind' => $plan->allow_rewind ?? false,
				'allow_recording' => $plan->allow_recording ?? false,
				'reduced_latency' => $plan->reduced_latency ?? false,
				'allow_advertising' => $plan->allow_advertising ?? false,
			];
			$requestUrl = requestUrl($params['serverhttpprefix'], $params['serverhostname'], $params['serverport'], "/api/tenants/customers/{$customer['id']}/channels");
			$response = request('post', $requestUrl, $params['serveraccesshash'], $payload);
			var_dump($response);exit;
		} catch (Exception $e) {
			// Record the error in WHMCS's module log.
			logModuleCall(
				'mediacpcloud',
				__FUNCTION__,
				$params,
				$e->getMessage(),
				$e->getTraceAsString()
			);

			return $e->getMessage();
		}
	}
    
    return 'success';
}

/**
 * Suspend an instance of a product/service.
 *
 * Called when a suspension is requested. This is invoked automatically by WHMCS
 * when a product becomes overdue on payment or can be called manually by admin
 * user.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function mediacpcloud_SuspendAccount(array $params)
{
    try {
        $payload = [
            'email' => $params['clientsdetails']['email'],
        ];
        $requestUrl = requestUrl($params['serverhttpprefix'], $params['serverhostname'], $params['serverport'], '/api/customers/suspend');
        $response = request('post', $requestUrl, $params['serveraccesshash'], $payload);

        logModuleCall(
            'mediacpcloud',
            __FUNCTION__,
            json_encode([
                'params' => $params,
                'url' => $requestUrl,
                'token' => $params['serveraccesshash'],
                'payload' => $payload
            ]),
            $response,
            $response
        );

        $result = \json_decode($response, true);

        if (isset($result['message'])) {
            return $result['message'];
        }

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'mediacpcloud',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Un-suspend instance of a product/service.
 *
 * Called when an un-suspension is requested. This is invoked
 * automatically upon payment of an overdue invoice for a product, or
 * can be called manually by admin user.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function mediacpcloud_UnsuspendAccount(array $params)
{
    try {
        $payload = [
            'email' => $params['clientsdetails']['email'],
        ];
        $requestUrl = requestUrl($params['serverhttpprefix'], $params['serverhostname'], $params['serverport'], '/api/customers/unsuspend');
        $response = request('post', $requestUrl, $params['serveraccesshash'], $payload);

        logModuleCall(
            'mediacpcloud',
            __FUNCTION__,
            json_encode([
                'params' => $params,
                'url' => $requestUrl,
                'token' => $params['serveraccesshash'],
                'payload' => $payload
            ]),
            $response,
            $response
        );

        $result = \json_decode($response, true);

        if (isset($result['message'])) {
            return $result['message'];
        }
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'mediacpcloud',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Terminate instance of a product/service.
 *
 * Called when a termination is requested. This can be invoked automatically for
 * overdue products if enabled, or requested manually by an admin user.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function mediacpcloud_TerminateAccount(array $params)
{
    try {
        $payload = [
            'email' => $params['clientsdetails']['email'],
        ];

        $requestUrl = requestUrl($params['serverhttpprefix'], $params['serverhostname'], $params['serverport'], '/api/customers/' . $params['domain']);
        $response = request('delete', $requestUrl, $params['serveraccesshash']);

        logModuleCall(
            'mediacpcloud',
            __FUNCTION__,
            \json_encode([
                'url' => $requestUrl,
                'method' => 'delete',
                'token' => $params['serveraccesshash'],
                'payload' => $payload
            ]),
            $response,
            $response
        );

        $result = \json_decode($response, true);

        if (isset($result['message'])) {
            return $result['message'];
        }
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'mediacpcloud',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Change the password for an instance of a product/service.
 *
 * Called when a password change is requested. This can occur either due to a
 * client requesting it via the client area or an admin requesting it from the
 * admin side.
 *
 * This option is only available to client end users when the product is in an
 * active status.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function mediacpcloud_ChangePassword(array $params)
{
    try {
        // Call the service's change password function, using the values
        // provided by WHMCS in `$params`.
        //
        // A sample `$params` array may be defined as:
        //
        // ```
        // array(
        //     'username' => 'The service username',
        //     'password' => 'The new service password',
        // )
        // ```
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'mediacpcloud',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Upgrade or downgrade an instance of a product/service.
 *
 * Called to apply any change in product assignment or parameters. It
 * is called to provision upgrade or downgrade orders, as well as being
 * able to be invoked manually by an admin user.
 *
 * This same function is called for upgrades and downgrades of both
 * products and configurable options.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function mediacpcloud_ChangePackage(array $params)
{
    try {
        $payload = [
            'plan'    => $params['configoption1'],
        ];

        $requestUrl = requestUrl($params['serverhttpprefix'], $params['serverhostname'], $params['serverport'], '/api/customers/' . $params['domain']);
        $response = request('put', $requestUrl, $params['serveraccesshash'], $payload);

        logModuleCall(
            'mediacptenancies',
            __FUNCTION__,
            json_encode([
                'url' => $requestUrl,
                'method' => 'put',
                'token' => $params['serveraccesshash'],
                'payload' => $payload
            ]),
            $response,
            $response
        );

        $result = \json_decode($response, true);

        if (isset($result['message'])) {
            return $result['message'];
        }

    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'mediacpcloud',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Test connection with the given server parameters.
 *
 * Allows an admin user to verify that an API connection can be
 * successfully made with the given configuration parameters for a
 * server.
 *
 * When defined in a module, a Test Connection button will appear
 * alongside the Server Type dropdown when adding or editing an
 * existing server.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return array
 */
function mediacpcloud_TestConnection(array $params)
{
    try {
        // Call the service's connection test function.

        $success = true;
        $errorMsg = '';
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'mediacpcloud',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        $success = false;
        $errorMsg = $e->getMessage();
    }

    return array(
        'success' => $success,
        'error' => $errorMsg,
    );
}

/**
 * Additional actions an admin user can invoke.
 *
 * Define additional actions that an admin user can perform for an
 * instance of a product/service.
 *
 * @see mediacpcloud_buttonOneFunction()
 *
 * @return array
 */
function mediacpcloud_AdminCustomButtonArray()
{
    return array(
//        "Button 1 Display Value" => "buttonOneFunction",
//        "Button 2 Display Value" => "buttonTwoFunction",
    );
}

/**
 * Additional actions a client user can invoke.
 *
 * Define additional actions a client user can perform for an instance of a
 * product/service.
 *
 * Any actions you define here will be automatically displayed in the available
 * list of actions within the client area.
 *
 * @return array
 *
function mediacpcloud_ClientAreaCustomButtonArray()
{
    return array(
        "Action 1 Display Value" => "actionOneFunction",
        "Action 2 Display Value" => "actionTwoFunction",
    );
}
*/

/**
 * Custom function for performing an additional action.
 *
 * You can define an unlimited number of custom functions in this way.
 *
 * Similar to all other module call functions, they should either return
 * 'success' or an error message to be displayed.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 * @see mediacpcloud_AdminCustomButtonArray()
 *
 * @return string "success" or an error message
 */
function mediacpcloud_buttonOneFunction(array $params)
{
    try {
        // Call the service's function, using the values provided by WHMCS in
        // `$params`.
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'mediacpcloud',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Custom function for performing an additional action.
 *
 * You can define an unlimited number of custom functions in this way.
 *
 * Similar to all other module call functions, they should either return
 * 'success' or an error message to be displayed.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 * @see mediacpcloud_ClientAreaCustomButtonArray()
 *
 * @return string "success" or an error message
 */
function mediacpcloud_actionOneFunction(array $params)
{
    try {
        // Call the service's function, using the values provided by WHMCS in
        // `$params`.
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'mediacpcloud',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Admin services tab additional fields.
 *
 * Define additional rows and fields to be displayed in the admin area service
 * information and management page within the clients profile.
 *
 * Supports an unlimited number of additional field labels and content of any
 * type to output.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 * @see mediacpcloud_AdminServicesTabFieldsSave()
 *
 * @return array
 */
function mediacpcloud_AdminServicesTabFields(array $params)
{
    try {
        // Call the service's function, using the values provided by WHMCS in
        // `$params`.
        $response = array();

        return [];
        // Return an array based on the function's response.
//        return array(
//            'Number of Apples' => (int) $response['numApples'],
//            'Number of Oranges' => (int) $response['numOranges'],
//            'Last Access Date' => date("Y-m-d H:i:s", $response['lastLoginTimestamp']),
//            'Something Editable' => '<input type="hidden" name="mediacpcloud_original_uniquefieldname" '
//                . 'value="' . htmlspecialchars($response['textvalue']) . '" />'
//                . '<input type="text" name="mediacpcloud_uniquefieldname"'
//                . 'value="' . htmlspecialchars($response['textvalue']) . '" />',
//        );
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'mediacpcloud',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        // In an error condition, simply return no additional fields to display.
    }

    return array();
}

/**
 * Execute actions upon save of an instance of a product/service.
 *
 * Use to perform any required actions upon the submission of the admin area
 * product management form.
 *
 * It can also be used in conjunction with the AdminServicesTabFields function
 * to handle values submitted in any custom fields which is demonstrated here.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 * @see mediacpcloud_AdminServicesTabFields()
 */
function mediacpcloud_AdminServicesTabFieldsSave(array $params)
{
    // Fetch form submission variables.
    $originalFieldValue = isset($_REQUEST['mediacpcloud_original_uniquefieldname'])
        ? $_REQUEST['mediacpcloud_original_uniquefieldname']
        : '';

    $newFieldValue = isset($_REQUEST['mediacpcloud_uniquefieldname'])
        ? $_REQUEST['mediacpcloud_uniquefieldname']
        : '';

    // Look for a change in value to avoid making unnecessary service calls.
    if ($originalFieldValue != $newFieldValue) {
        try {
            // Call the service's function, using the values provided by WHMCS
            // in `$params`.
        } catch (Exception $e) {
            // Record the error in WHMCS's module log.
            logModuleCall(
                'mediacpcloud',
                __FUNCTION__,
                $params,
                $e->getMessage(),
                $e->getTraceAsString()
            );

            // Otherwise, error conditions are not supported in this operation.
        }
    }
}

/**
 * Perform single sign-on for a given instance of a product/service.
 *
 * Called when single sign-on is requested for an instance of a product/service.
 *
 * When successful, returns a URL to which the user should be redirected.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return array
 */
function mediacpcloud_ServiceSingleSignOn(array $params)
{
    try {
        // Call the service's single sign-on token retrieval function, using the
        // values provided by WHMCS in `$params`.
        $response = array();

        return array(
            'success' => true,
            'redirectTo' => $response['redirectUrl'],
        );
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'mediacpcloud',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return array(
            'success' => false,
            'errorMsg' => $e->getMessage(),
        );
    }
}

/**
 * Perform single sign-on for a server.
 *
 * Called when single sign-on is requested for a server assigned to the module.
 *
 * This differs from ServiceSingleSignOn in that it relates to a server
 * instance within the admin area, as opposed to a single client instance of a
 * product/service.
 *
 * When successful, returns a URL to which the user should be redirected to.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return array
 */
function mediacpcloud_AdminSingleSignOn(array $params)
{
    try {
        // Call the service's single sign-on admin token retrieval function,
        // using the values provided by WHMCS in `$params`.
        $response = array();

        return array(
            'success' => true,
            'redirectTo' => $response['redirectUrl'],
        );
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'mediacpcloud',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return array(
            'success' => false,
            'errorMsg' => $e->getMessage(),
        );
    }
}

/**
 * Client area output logic handling.
 *
 * This function is used to define module specific client area output. It should
 * return an array consisting of a template file and optional additional
 * template variables to make available to that template.
 *
 * The template file you return can be one of two types:
 *
 * * tabOverviewModuleOutputTemplate - The output of the template provided here
 *   will be displayed as part of the default product/service client area
 *   product overview page.
 *
 * * tabOverviewReplacementTemplate - Alternatively using this option allows you
 *   to entirely take control of the product/service overview page within the
 *   client area.
 *
 * Whichever option you choose, extra template variables are defined in the same
 * way. This demonstrates the use of the full replacement.
 *
 * Please Note: Using tabOverviewReplacementTemplate means you should display
 * the standard information such as pricing and billing details in your custom
 * template or they will not be visible to the end user.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return array
 */
function mediacpcloud_ClientArea(array $params)
{
    // Determine the requested action and set service call parameters based on
    // the action.
    $requestedAction = isset($_REQUEST['customAction']) ? $_REQUEST['customAction'] : '';

    if ($requestedAction == 'manage') {
        $serviceAction = 'get_usage';
        $templateFile = 'templates/manage.tpl';
    } else {
        $serviceAction = 'get_stats';
        $templateFile = 'templates/overview.tpl';
    }

    try {
        // Call the service's function based on the request action, using the
        // values provided by WHMCS in `$params`.
        $response = array();

        return array(
            'tabOverviewReplacementTemplate' => $templateFile,
            'templateVariables' => array(
                'serverhostname' => $params['serverhostname'],
            ),
        );
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'mediacpcloud',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        // In an error condition, display an error page.
        return array(
            'tabOverviewReplacementTemplate' => 'error.tpl',
            'templateVariables' => array(
                'usefulErrorHelper' => $e->getMessage(),
            ),
        );
    }
}

if (!function_exists('requestUrl')) {
    function requestUrl($serverhttpprefix, $serverhostname, $serverport, $path)
    {
        return sprintf('%s://%s:%s%s', $serverhttpprefix, $serverhostname, $serverport, $path);
    }
}

if (!function_exists('request')) {
    function request($action, $url, $token, $params = [])
    {
        $payload = \json_encode($params);

        // Prepare new cURL resource
        $ch = \curl_init();
        \curl_setopt($ch, CURLOPT_URL, $url);
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        \curl_setopt($ch, CURLINFO_HEADER_OUT, true);

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
                    'Authorization: Bearer ' . $token
                ]);
                break;
            case 'put':
                \curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                \curl_setopt($ch, CURLOPT_POST, 1);
                \curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                // Set HTTP Header for POST request
                \curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Content-Length: ' . strlen($payload),
                    'Authorization: Bearer ' . $token
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

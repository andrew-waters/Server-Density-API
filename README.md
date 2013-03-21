# Server Density API Class for PHP

This is an implementation Class for the Server Density API.

Server Density is a monitoring suite for your servers. This class allows you to interact seamlessly with the [API](http://developer.serverdensity.com) so that you can build your own interface, perform postbacks etc. This class has the advantage of client side verification of the call.


## How to use

Fill in your login credentials and API details to the class:

	const SD_ACCOUNT_SUBDOMAIN	= "example";
	const SD_ACCOUNT_USERNAME	= "username";
	const SD_ACCOUNT_PASSWORD	= "password";

The API is object orientated and as such should be simple to integrate.
Simply instatiate the class and start making your calls.

For example, the following will print a list of all your device groups:

	$api = new ServerDensityAPI;
	
	$api->setCall("devices", "list")->call();
	
	if($api->response->status == 1) {
	
		// the call has been successful
		print_r($api->response->data);
	
	} else {

		// the call was unsuccessful (not due to an exception)

	}


## Requirements
* PHP 5
* cURL


## Contributors
[Andrew Waters](https://twitter.com/andrew_waters) and [Dominik Gehl](https://twitter.com/dominikgehl)


## License
Released under the [GNU Public License](http://opensource.org/licenses/gpl-license.php).

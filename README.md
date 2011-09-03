# Server Density API Class for PHP

This is an implementation Class for the Server Density API.

Server Density is a monitoring suite for your servers. This class allows you to interact seamlessly with the [API](http://developer.serverdensity.com) so that you can build your own interface, perform postbacks etc. This class has the advantage of client side verification of the call.


## How to use

The API is object orientated and as such should be simple to use and familiar. Simply instatiate the class and start makeing your calls, such as:

	$api = new ServerDensityAPI;
	$api->setCall("devices", "list")->call();
	if($api->response->status == 1) {
		// the call has been successful
		print_r($api->response->data);
	}


## Requirements
* PHP 5
* cURL


## License
Released under the [GNU Public License](http://opensource.org/licenses/gpl-license.php).

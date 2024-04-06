<?php

require_once plugin_dir_path(__FILE__). '../consts.php';


class MAP_Mastodon_API {
	private string $apiToken;
	public readonly string $instance;

	/**
	 * Initializes a new instance of the class.
	 *
	 * This constructor sets the values of the `$apiToken` and `$instance` properties by retrieving the corresponding
	 * options from the database using the `get_option` function.
	 *
	 * @return void
	 */
	function __construct() {
		$this->apiToken = get_option( OptionsMastodonToken );
		$this->instance = get_option( OptionsMastodonInstance );
	}

	/**
	 * Sends a POST request with JSON data.
	 *
	 * This method sends a POST request with the provided JSON data to the specified path. The request is authenticated
	 * using the `$apiToken` property and the request body is set to the `$body` parameter. The request is made with the
	 * headers "Authorization" and "Content-Type" set to the corresponding values. The actual HTTP request is made by the
	 * `post` method.
	 * The function also encodes the `$body` supplied to the function using `json_encode`.
	 *
	 * @param string $path The path to send the POST request to.
	 * @param mixed $body The data that should be sent to the API.
	 *
	 * @return array
	 */
	public function postJSON( string $path, mixed $body ): array {
		$headers = [
			"Authorization: Bearer $this->apiToken",
			"Content-Type: application/json"
		];

		return $this->post( $path, $headers, json_encode( $body ) );
	}

	/**
	 * Posts form data to the specified path.
	 *
	 * This method sends a POST request to the specified path with the provided form data.
	 * The form data is passed in the `$body` parameter and must be formatted as a string.
	 * The `$boundary` parameter is used to specify the boundary value for the multipart/form-data content type.
	 * The method sets the necessary headers, including the authorization header with the API token, and the content
	 * type and length headers.
	 *
	 * @param string $path The path to which the form data should be posted.
	 * @param mixed $body The form data to be posted. Must be a string.
	 * @param string $boundary The boundary value for the multipart/form-data content type.
	 *
	 * @return array
	 */
	public function postFormData( string $path, mixed $body, string $boundary ): array {
		$headers = [
			"Authorization: Bearer $this->apiToken",
			"Content-Type: multipart/form-data; boundary=$boundary",
			"Content-Length: " . strlen( $body )
		];

		return $this->post( $path, $headers, $body );

	}

	/**
	 * Sends a HTTP POST request to a specified endpoint with the provided headers and body.
	 *
	 * This method uses the cURL library to send a HTTP POST request to the endpoint defined by the concatenation of the
	 * `$instance` property and the `$path` parameter. The request's headers are set using the `$headers` parameter,
	 * and the request's body is set using the `$body` parameter.
	 *
	 * @param string $path The path component of the endpoint URL.
	 * @param array $headers An associative array representing the headers to be included in the request.
	 * @param mixed $body The body of the request.
	 *
	 * @return array An associative array containing the HTTP response code and the response content.
	 */
	private function post( string $path, array $headers, mixed $body ): array {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, "$this->instance$path" );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $body );

		$response_content = curl_exec( $ch );
		$response_info    = curl_getinfo( $ch );

		return array( 'http_code' => $response_info['http_code'], 'response' => $response_content );
	}

}
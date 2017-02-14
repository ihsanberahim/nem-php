<?php
/**
 * Part of the evias/php-nem-laravel package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under MIT License.
 *
 * This source file is subject to the MIT License that is
 * bundled with this package in the LICENSE file.
 *
 * @package    evias/php-nem-laravel
 * @version    0.0.2
 * @author     Grégory Saive <greg@evias.be>
 * @license    MIT License
 * @copyright  (c) 2017, Grégory Saive <greg@evias.be>
 * @link       http://github.com/evias/php-nem-laravel
 */
namespace evias\NEMBlockchain\Handlers;

use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/**
 * This is the GuzzleHttpHandler class
 *
 * @author Grégory Saive <greg@evias.be>
 */
class GuzzleHttpHandler
    extends AbstractHttpHandler
{
    /**
     * This method triggers a GET request to the given
     * URI using the GuzzleHttp client.
     *
     * Default behaviour disables Promises Features.
     *
     * Promises Features
     * - success callback can be configured with $options["onSuccess"],
     *   a ResponseInterface object will be passed to this callable when
     *   the Request Completes.
     * - error callback can be configured with $options["onError"],
     *   a RequestException object will be passed to this callable when
     *   the Request encounters an error
     *
     * @see  \evias\NEMBlockchain\Contracts\HttpHandler
     * @param  string $uri
     * @param  string $bodyJSON
     * @param  array  $options      can contain "headers" array, "onSuccess" callable,
     *                              "onError" callable and any other GuzzleHTTP request
     *                              options.
     * @param  boolean  $usePromises
     * @return [type]
     */
    public function get($uri, $bodyJSON, array $options = [], $usePromises = false)
    {
        $headers = [];
        if (!empty($options["headers"]))
            $headers = $options["headers"];

        // overwrite mandatory headers
        $headers["Content-Length"] = strlen($bodyJSON);
        $headers = $this->normalizeHeaders($headers);

        // prepare guzzle request options
        $options = array_merge($options, [
            "body"    => $bodyJSON,
            "headers" => $headers,
        ]);

        $client  = new Client(["base_uri" => $this->getBaseUrl()]);
        $request = new Request("GET", $uri, $options);
        if (! $usePromises)
            // return the response object when done.
            return $client->send($request);

        // Now use guzzle Promises features, as mentioned at the end,
        // Guzzle Promises do not allow Asynchronous Requests Handling,
        // I have implemented this feature only because it will
        // allow a better Response Time for Paralell Request Handling.
        // This will be implemented in later versions and so, the
        // following snippet will basically work just like a normal
        // Synchronous request, except that the Success and Error
        // callbacks can be configured more conviniently.

        $successCallback = isset($options["onSuccess"]) && is_callable($options["onSuccess"]) ? $options["onSuccess"] : null;
        $errorCallback   = isset($options["onError"]) && is_callable($options["onError"]) ? $options["onError"] : null;

        $promise = $client->sendAsync($request);
        $promise->then(
            function(ResponseInterface $response)
                use ($successCallback)
            {
                if ($successCallback)
                    return $successCallback($response);

                return $response;
            },
            function(RequestException $exception)
                use ($errorCallback)
            {
                if ($errorCallback)
                    return $errorCallback($exception);

                return $exception;
            }
        );

        // Guzzle Promises advantages will only be leveraged
        // in Parelell request execution mode as all requests
        // will be sent in paralell and the handling time goes
        // down to the minimum response time of ALL promises.
        return $promise->wait();
    }

    /**
     * This method triggers a POST request to the given
     * URI using the GuzzleHttp client.
     *
     * @see  \evias\NEMBlockchain\Contracts\HttpHandler
     * @param  string $uri
     * @param  string $bodyJSON
     * @param  array  $options
     * @param  boolean  $synchronous
     * @return [type]
     */
    public function post($uri, $bodyJSON, array $options = [], $synchronous = false)
    {
    }
}

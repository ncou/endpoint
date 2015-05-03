<?php

namespace Phapi;

use Phapi\Contract\Di\Container;
use Phapi\Exception\MethodNotAllowed;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class Endpoint
 *
 * Parent class for all endpoints. Implements some basic functionality
 * like OPTIONS responses and adds dependency injection container, request
 * and response objects.
 *
 * @category Phapi
 * @package  Phapi\Endpoint
 * @author   Peter Ahinko <peter@ahinko.se>
 * @license  MIT (http://opensource.org/licenses/MIT)
 * @link     https://github.com/phapi/endpoint
 */
class Endpoint
{

    /**
     * Response object
     *
     * @var ResponseInterface
     */
    protected $response;

    /**
     * Request object
     *
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * Dependency Injection Container
     *
     * @var Container
     */
    protected $container;

    /**
     * Create the endpoint
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param Container $container
     */
    public function __construct(ServerRequestInterface $request, ResponseInterface $response, Container $container)
    {
        $this->request   = $request;
        $this->response  = $response;
        $this->container = $container;
    }

    /**
     * Get response
     *
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Handle HEAD requests if a GET method is implemented
     *
     * @return array
     * @throws MethodNotAllowed
     */
    public function head()
    {
        // Check if endpoint has a GET method
        if (method_exists($this, 'get')) {
            // Call GET. If the GET method adds any headers those will be present
            // in the $this->response object. The GET method will return the body
            // but since HEAD requests should only return headers we will NOT
            // return the body.
            $this->get();

            // Return empty body
            return [];
        }

        // Method GET isn't implemented so method (head) isn't allowed
        throw new MethodNotAllowed();
    }

    /**
     * Options
     *
     * Responding to OPTIONS requests and checks for implemented
     * methods that matches HTTP METHODS.
     *
     * @return array
     * @throws MethodNotAllowed
     */
    public function options()
    {
        if (!($this->response instanceof ResponseInterface)) {
            throw new MethodNotAllowed();
        }

        // Get all implemented methods for this resources
        $methods = get_class_methods(get_class($this));

        // Get supported verbs
        $validMethods = $this->container['validHttpVerbs'];

        // Loop though class functions/methods
        foreach ($methods as $key => &$method) {
            $method = strtoupper($method);
            // If class function/method isn't a verb, then unset it
            if (!in_array($method, $validMethods)) {
                unset($methods[$key]);
            }
        }

        // Set accept header
        $this->response = $this->response->withAddedHeader('Allow', implode(', ', $methods));

        // Prepare output
        $output = [];
        $output['Content-Type'] = $this->container['contentTypes'];
        $output['Accept'] = $this->container['acceptTypes'];

        foreach ($methods as $verb) {
            $doc = $this->parseMethodDoc($verb);

            // Check if there is any output to show
            if (!empty($doc)) {
                $output['methods'][$verb] = $doc;
            }
        }

        // return output
        return $output;
    }

    /**
     * Parse the methods doc and look for @api tags used for documenting
     * the API.
     *
     * @param $method
     * @return array
     */
    protected function parseMethodDoc($method)
    {
        // Reflect the method
        $reflectionMethod = new \ReflectionMethod($this, $method);
        // Get method documentation
        $doc = $reflectionMethod->getDocComment();

        // Prepare output
        $output = [];

        $longKey = null;

        // Loop through all lines
        foreach (preg_split("/((\r?\n)|(\r\n?))/", $doc) as $line) {

            // Reset value
            $value = '';
            // Reset key
            $key = '';

            // Remove some unwanted chars from the line
            $line = trim(str_replace('*', '', trim($line)));

            // check if line starts with @api
            if (substr($line, 0, 4) == '@api') {
                // find the annotation and use it as a key/identifier, example: @apiDescription
                preg_match('/^@api[a-zA-Z]*/i', $line, $matches);
                $longKey = $matches[0];

                // remove @api from the key/identifier
                $key = lcfirst(str_replace('@api', '', $longKey));

                // remove whitespace from the line
                $value = trim($line);

                // check if line doesn't have a annotation
            } elseif (!in_array(substr($line, 0, 1), ['@', '/']) && !empty($line)) {
                // check if we have the key/identifier from last loop
                if (!empty($longKey)) {
                    // remove whitespace
                    $value .= trim($line);
                    // create key/identifier by removing @api and making first letter lowercase
                    $key = lcfirst(str_replace('@api', '', $longKey));
                }
            } else {
                // don't include this line in the doc
                $longKey = null;
                continue;
            }

            $output = $this->prepareOutput($output, $key, $value, $longKey);
        }

        return $output;
    }

    /**
     * Prepare output
     *
     * @param $output
     * @param $key
     * @param $value
     * @param $longKey
     * @return array
     */
    protected function prepareOutput(array $output, $key, $value, $longKey)
    {
        // check if we already have a key/identifier in the output
        if (array_key_exists($key, $output)) {
            // check if value is an array (has multiple values)
            if (is_array($output[$key])) {
                $output = $this->outputMultipleValues($output, $key, $value, $longKey);
            } else {
                // value is not an array
                $output = $this->outputSingleValue($output, $key, $value, $longKey);
            }
        } else {
            // this is a new key/identifier
            // check if we have a key/identifier
            if (isset($longKey)) {
                // add key and value to output
                $output[$key] = str_replace($longKey.' ', '', trim($value));
            }
        }

        return $output;
    }

    /**
     * Handle output with multiple values
     *
     * @param array $output
     * @param $key
     * @param $value
     * @param $longKey
     * @return array
     */
    protected function outputMultipleValues(array $output, $key, $value, $longKey)
    {
        // remove the key from the value and remove whitespace
        $newValue = str_replace($longKey.' ', '', trim($value));

        // check if there was a key to remove
        if (trim($value) !== $newValue) {
            // the key was removed and that means we wasn't to add the line as a new row in the array
            $output[$key][] = $newValue;
        } else {
            // the key wasn't removed so we want to merge this line with the previous one
            // count rows in array to get the last key
            $last = count($output[$key]) -1;
            // merge this line with the previous one
            $output[$key][$last] = $output[$key][$last]. ' '. $newValue;
        }

        return $output;
    }

    /**
     * Handle output with single value
     *
     * @param array $output
     * @param $key
     * @param $value
     * @param $longKey
     * @return array
     */
    protected function outputSingleValue(array $output, $key, $value, $longKey)
    {
        // save the current value
        $oldValue = $output[$key];

        // remove the key from the value and remove whitespace
        $newValue = trim(str_replace($longKey.' ', '', trim($value)));

        // check if there was a key to remove
        if (trim($value) !== $newValue) {
            // the key was removed so we want to create an array with the previous and new value
            $output[$key] = [$oldValue, $newValue];
        } else {
            // the wasn't a key to remove so we want to merge this line with the previous one
            $output[$key] .= ' '. $newValue;
        }

        return $output;
    }
}

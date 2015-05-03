# Endpoint
Endpoint is a class that all endpoints created in Phapi should extend.

## Basic usage
An endpoint contains methods for handling requests. If an endpoint should support GET requests a <code>get()</code> method should exist. Each method must return the content that should be the response body sent to the client. The content must be formatted as an array. Serializers will then serialize the array to the correct format.

```php
<?php

namespace Phapi\Endpoint;

class Blog extends Endpoint
{

  public function get()
  {
    return [
      'Title' => 'A Phapi tutorial, part 1',
      'Publish date' => '2015-01-01',
      'Author' => 'John Doe',
      ...
    ];
  }

}

```

All endpoints (that extends the Endpoint class) has the request, response and dependency injection container available: <code>$this->request</code>, <code>$this->response</code>, <code>$this->container</code>.

## Status code
The default status code is 200. To change the status code by accessing the Response object:

```php
<?php
public function get()
{
  // Change status code on the response to a 201 CREATED:
  $this->response = $this->response->withStatusCode(201);
}

```

## Errors and Exceptions
Handling errors should be easy, that's why Phapi has many [predefined exceptions](https://github.com/phapi/exception). With these exceptions it's just a matter of throwing them to get a nice looking response sent to the client:

```php
<?php

namespace Phapi\Endpoint;

use Phapi\Exception\NotFound;

class Blog extends Endpoint
{

  public function get()
  {
    throw new NotFound(
      'The article you are looking for does not exist.',
      23,
      null,
      'https://example.local/docs/23/'
    );
  }

}
```

This will result in a response like this (JSON in this example):

```json
{
  "errors": {
    "message": "The article you are looking for does not exist.",
    "code": 23,
    "description": "The URI requested is invalid or the resource requested, such as a user, does not exists. Also returned when the requested format is not supported by the requested method.",
    "link": "https://example.local/docs/23/"
  }
}
```

## Redirects
Redirects should be handled by updating the response object:

```php
<?php
public function get()
{
  // This endpoint has moved permanently
  $this->response = $this->response->withStatusCode(301);
  $this->response = $this->response->withAddedHeader('Location': 'http://example.local/endpoint/new');
}

```

## Head requests
All HEAD requests are automatically handled by Endpoints by checking if a <code>get()</code> method is implemented. If it is implemented, it will call that method and return the response. The middleware that sends the response to the client will hopefully just return the headers and not the body.

## Options requests
Endpoint contains functionality for handling OPTIONS requests by looking at the implemented methods and PHPDoc to set headers and generate a body. The method returns supported content types, allowed methods as well as API documentation (if documentation exists).

Documenting the API is done by using PHPDoc and using tags that starts with @api.

```php
<?php
/**
 * @apiUri /blog/12
 * @apiDescription Retrieve the blogs information like
 *                 id, name and description
 * @apiParams id int
 * @apiResponse id int Blog ID
 * @apiResponse name string The name of the blog
 * @apiResponse description string A description of the blog
 * @apiResponse links string
 *              A list of links
 */
 public function get()
 ...
```

It's possible to do quite advanced documentation this way.

*It might be slightly unusual to return a body on an OPTIONS request but this makes the API self-describing. It makes it possible to browse, use and look at the documentation of the API by actually using the API itself.*

## Phapi
Endpoint is a Phapi package used by the [Phapi Framework](https://github.com/phapi/phapi).

## License
Endpoint is licensed under the MIT License - see the [license.md](https://github.com/phapi/endpoint/blob/master/license.md) file for details

## Contribute
Contribution, bug fixes etc are [always welcome](https://github.com/phapi/endpoint/issues/new).

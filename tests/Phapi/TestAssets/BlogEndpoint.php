<?php

namespace Phapi\Tests\Endpoint\Asset;

use Phapi\Endpoint;

class Blog extends Endpoint
{

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
    {
        return [
            'id' => 12,
            'name' => 'Dev blog'
        ];
    }

    /**
     * Change response to null to test exception
     */
    public function changeResponse()
    {
        $this->response = null;
    }
}

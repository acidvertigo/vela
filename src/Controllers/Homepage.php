<?php

namespace Vela\Controllers;

use \Sabre\HTTP\Request;
use \Sabre\HTTP\Response;
use \Purl\Url;

/**
 * Homepage Controller
 */
class Homepage
{
    /** @var object $response **/
    private $response;
    /** @var object $response **/
    private $request;
    /** @var object $response **/
    private $url;

    public function __construct(Response $response, Request $request, Url $url)
    {
        $this->response = $response;
        $this->request  = $request;
        $this->url      = $url;
    }

    public function show()
    {
        $this->response->setBody('<h1>Hello World</h1>');
        \Sabre\HTTP\Sapi::sendResponse($this->response);
    }
}

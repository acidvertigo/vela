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
        $data = $this->request->getQueryParameters();
        $url  = $this->url->getUrl();
        $this->response->setBody('Hello World '.$data['name'].' current url '.$url);
        \Sabre\HTTP\Sapi::sendResponse($this->response);
    }
}

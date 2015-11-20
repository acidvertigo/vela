<?php

namespace Vela;

require __DIR__.'/../vendor/autoload.php';

error_reporting(E_ALL|E_STRICT);

// define environment
$environment = 'development';

/**
* Register the error handler
*/
$whoops = new \Whoops\Run;
if ($environment !== 'production')
{
    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
} else
{
    $whoops->pushHandler(function() {
        echo 'Friendly error page and send an email to the developer';
    });
}
$whoops->register();

/**
 * Start Request REsponse Objects
 */
$request  = \Sabre\HTTP\Sapi::getRequest();
$response = new \Sabre\HTTP\Response();

/**
 * Start dic container
 */
$dic = new \Auryn\Injector;
$dic->share($request);
$dic->share($response);

/**
 * Start session object if user is not a robot
 */
$user = $dic->make('\Vela\Core\User');
$dic->share($user);

$userAgent = $user->getUserAgent();
$userIp    = $user->getUserIp();

//check if user is a robot
$robots = require 'Config/Robots.php';
$isRobot = $user->isRobot($userAgent, $robots);

if (!$isRobot)
{
    //Initialize session object
    $session_factory = new \Aura\Session\SessionFactory;
    $session         = $session_factory->newInstance($_COOKIE);

    $segment = $session->getSegment('User');
    if ($segment->get('IPaddress') != $userIp || $segment->get('userAgent') != $userAgent)
    {
        $session->clear();
        $segment = $session->getSegment('User');
        $segment->set('IPaddress', $userIp);
        $segment->set('userAgent', $userAgent);
        $session->regenerateId();
    }
}

/**
 * Start url parser
 */
$url = \Purl\Url::fromCurrent();

// share dependencies
$dic->share($url);
if (isset($session))
{
    $dic->share($session);
}

/**
 * Initialize router
 */
$routeDefinitionCallback = function(\FastRoute\RouteCollector $r) {
    $routes              = include('Config/Routes.php');
    foreach ($routes as $route)
    {
        $r->addRoute($route[0], $route[1], $route[2]);
    }
};

$dispatcher = \FastRoute\simpleDispatcher($routeDefinitionCallback);
$routeInfo = $dispatcher->dispatch($request->getMethod(), '/'.$request->getPath());
switch ($routeInfo[0])
{
    case \FastRoute\Dispatcher::NOT_FOUND:
        $response->setBody('404 - Page not found');
        $response->setStatus(404);
        \Sabre\HTTP\Sapi::sendResponse($response);
        exit;
        break;
    case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        $response->setHeader('Allow', $allowedMethods);
        $response->setBody('405 - Method not allowed');
        $response->setStatus(405);
        \Sabre\HTTP\Sapi::sendResponse($response);
        exit;
        break;
    case \FastRoute\Dispatcher::FOUND:
        $className = $routeInfo[1][0];
        $method    = $routeInfo[1][1];
        $vars      = $routeInfo[2];
        $class     = $dic->make($className);
        $class->$method($vars);
        break;
}

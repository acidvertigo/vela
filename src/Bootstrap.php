<?php

namespace Vela;

require __DIR__.'/../vendor/autoload.php';

error_reporting(E_ALL|E_STRICT);

// define environment
$environment = 'Development'; // Accepted values: Development or Production.

/**
* Register the error handler
*/
$whoops = new \Whoops\Run;
if ($environment !== 'Production')
{ 
    //start logger
    $logger = new \Katzgrau\KLogger\Logger(__DIR__ . '/logs', \Psr\Log\LogLevel::DEBUG, ['extension' => 'log']);
    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
} else
{
    $logger = new \Katzgrau\KLogger\Logger(__DIR__ . '/logs', \Psr\Log\LogLevel::WARNING, ['extension' => 'log']);
    $whoops->pushHandler(new \Whoops\Handler\PlainTextHandler($logger));
    $whoops->pushHandler(function() {
        echo 'Friendly error page and send an email to the developer';
    });
}

// Load configuration file
$config = require 'Config/' . $environment . '/Config.php';

/**
 * Initialize datetime
 */
$time = new \ICanBoogie\DateTime('now', $config['locale']['timezone']);

/**
 * Database connection
 */
$db = new \PDO('mysql:host=' . $config['database']['host'] . ';dbname=' . $config['database']['db_name'],
       $config['database']['user'], 
       $config['database']['password']);
$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

/**
 * Start Request Response Objects
 */
$request  = \Sabre\HTTP\Sapi::getRequest();
$response = new \Sabre\HTTP\Response();

/**
 * Start url parser
 */
$url = \Purl\Url::fromCurrent();

/**
 * Start dic container
 */
$dic = new \Auryn\Injector;

// Share object instances
$services = [$db, $mail, $request, $response, $time, $url];
foreach ($services as $service)
{
    $dic->share($service);
}

/**
 * Start user object
 */
$user = $dic->make('\Vela\Core\User');

$userAgent = $user->getUserAgent();
$userIp    = $user->getUserIp();

//check if user is a robot
$robots  = require 'Config/' . $environment . '/Robots.php';
$isRobot = $user->isRobot($userAgent, $robots);

if (!$isRobot)
{
    /**
     * Initialize session object
     */
    $session_factory = new \Aura\Session\SessionFactory;
    $session         = $session_factory->newInstance($_COOKIE);

    // set session name
    if($session->getName() !== $config['session']['id'])
    {
        $session->setName($config['session']['id']);
    }
    
    // determine if we are on https or not
    $ssl = ($url['port'] == '443') ? true : false;

    // set cookie parameters
    $session->setCookieParams(['lifetime' => 3600,
                               'path' => '/',
                               'domain' => $url['host'],
                               'secure' => $ssl,
                               'httponly' => true]);

    // create session segment
    $segment = $session->getSegment('User');

    // prevent session hijacking
    if ($segment->get('IPaddress') != $userIp || $segment->get('userAgent') != $userAgent)
    {
        $session->clear();
        $session->destroy();
        $segment = $session->getSegment('User');
        $segment->set('IPaddress', $userIp);
        $segment->set('userAgent', $userAgent);
        $segment->set('isSsl', $ssl);
        $session->regenerateId();
    }

    // regenerate session id and set cookie secure flag when switching between http and https
    if($segment->get('isSsl') !== $ssl)
    {
        $segment->set('isSsl', $ssl);
        $session->setCookieParams(['secure' => $ssl]);
        $session->regenerateId();
    }

    // record session activity
    if(!$segment->get('start_time'))
    {
        $segment->set('start_time', time());
    }

    $segment->set('last_activity', time());

    // delete session expired also server side
    if($segment->get('start_time') < (strtotime('-1 hours')) || $segment->get('last_activity') < (strtotime('-20 mins')))
    {
        $session->clear();
        $session->destroy();
    }

    // share same instance of session object
    $dic->share($session);
}

/**
 * Initialize router
 */
$routeDefinitionCallback = function (\FastRoute\RouteCollector $r) use ($environment) {
    $routes = require 'Config/' . $environment . '/Routes.php';
    foreach ($routes as $route)
    {
        $r->addRoute($route[0], $route[1], $route[2]);
    }
};

$dispatcher = \FastRoute\simpleDispatcher($routeDefinitionCallback);
$routeInfo  = $dispatcher->dispatch($request->getMethod(), '/'.$request->getPath());
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

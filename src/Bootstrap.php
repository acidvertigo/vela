<?php

namespace Vela;

require __DIR__.'/../vendor/autoload.php';

error_reporting(E_ALL|E_STRICT);

// define environment
$environment = 'Development'; // Accepted values: Development or Production.

$config = require 'Config/' . $environment . '/Config.php';

/**
* Register the error handler
*/
$whoops = new \Whoops\Run;
if ($environment !== 'Production')
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
$dic->share($db);
$dic->share($request);
$dic->share($response);
$dic->share($time);
$dic->share($url);

/**
 * Start user object
 */
$user = $dic->make('\Vela\Core\User');
$dic->share($user);

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

    // set cookie parameters
    $session->setCookieParams(['lifetime' => 3600, 'path' => '/', 'domain' => $url['host'], 'secure' => $url['port'] == '443' ? true : false, 'httponly' => true]);

    $segment = $session->getSegment('User');

    // prevent session hijacking
    if ($segment->get('IPaddress') != $userIp || $segment->get('userAgent') != $userAgent)
    {
        $session->clear();
        $session->destroy();
        $segment = $session->getSegment('User');
        $segment->set('IPaddress', $userIp);
        $segment->set('userAgent', $userAgent);
        $segment->set('isSsl', $url['port'] !== '443' ? true : false);
        $session->regenerateId();
    }

    // regenerate session id and set cookie secure flag when switching between http and https
    if($segment->get('isSsl') == false && $url['port'] == '443')
    {
        $segment->set('isSsl', true);
        $session->setCookieParams(['secure' => true]);
        $session->regenerateId();
    }
        
    if($segment->get('isSsl') == true && $url['port'] !== '443')
    {
        $segment->set('isSsl', false);
        $session->setCookieParams(['secure' => false]);
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

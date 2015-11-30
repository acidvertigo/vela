<?php

namespace Vela;

require __DIR__ . '/../vendor/autoload.php';

// Load configuration file
$configuration = require 'Config/' . ENVIRONMENT . '/Config.php';

/**
 * Initialize Configuration container
 */
$config = new Core\Config($configuration);

/**
 * Start mailer
 */
$mail = (function() use ($config)  {
            return new Core\Mail($config);
            });

/**
* Register the error handler
*/
$whoops = new \Whoops\Run;

//start logger
$logger = function ($logLevel) { return new \Katzgrau\KLogger\Logger(__DIR__ . '/logs', $logLevel, ['extension' => 'log']);
          };

if (ENVIRONMENT !== 'Production')
{   
    $logLevel = \Psr\Log\LogLevel::DEBUG;
    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);    
} else {
    $logLevel = \Psr\Log\LogLevel::WARNING;
    $whoops->pushHandler(function() use ($mail) {
        echo 'Friendly error page and send an email to the developer';
    $mail()->setMessage('Error notification', '<H1>Error</H1><br><p>There was an error on your website. Please check your log file for more info', ['test@test.com' => 'test']);
    });
}
$whoops->pushHandler(new \Whoops\Handler\PlainTextHandler($logger($logLevel)));
$whoops->register();

/**
 * Initialize datetime
 */
$time = (function() use ($config) {
             return new \ICanBoogie\DateTime('now', $config->get('locale.timezone')); 
         });

/**
 * Database connection
 */
$db = (function() use ($config) { 
        return new \PDO('mysql:host=' . $config->get('database.host') . ';dbname=' . $config->get('database.db_name'),
        $config->get('database.user'), 
        $config->get('database.password'),
        [\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION]);
        });

/**
 * Start Request Response Objects
 */
$request  = (function() {
                return \Sabre\HTTP\Sapi::getRequest();
                });
$response = (function() {
                return new \Sabre\HTTP\Response();
                });
/**
 * Start url parser
 */
$url = \Purl\Url::fromCurrent();
// determine if we are on https or not
$ssl = ($url['port'] == '443') ? true : false;

/**
 * Start dic container
 */
$dic = new \Auryn\Injector;

// Share object instances
$services = [$config, $db(), $mail(), $request(), $response(), $time(), $url];
foreach ($services as $service)
{
    $dic->share($service);
}

//check if user is a robot
$robots = require 'Config/' . ENVIRONMENT . '/Robots.php';

/**
 * Start user object
 */
$user = $dic->make('\Vela\Core\User');

$userAgent = $user->getUserAgent();
$userIp    = $user->getUserIp();

if (!$user->isRobot($userAgent, $robots))
{
    /**
     * Initialize session object
     */
    $session_factory = new \Aura\Session\SessionFactory;
    $session         = $session_factory->newInstance($_COOKIE);

    // set session name
    if ($session->getName() !== $config->get('session.id'))
    {
        $session->setName($config->get('session.id'));
    }
    
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
    if ($segment->get('isSsl') !== $ssl)
    {
        $segment->set('isSsl', $ssl);
        $session->setCookieParams(['secure' => $ssl]);
        $session->regenerateId();
    }

    // record session activity
    if (!$segment->get('start_time'))
    {
        $segment->set('start_time', time());
    }

    $segment->set('last_activity', time());

    // delete session expired also server side
    if ($segment->get('start_time') < (strtotime('-1 hours')) || $segment->get('last_activity') < (strtotime('-20 mins')))
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
$routeDefinitionCallback = function(\FastRoute\RouteCollector $r) {
    $routes = require 'Config/' . ENVIRONMENT . '/Routes.php';
    foreach ($routes as $route)
    {
        $r->addRoute($route[0], $route[1], $route[2]);
    }
};

$req = $request();
$dispatcher = \FastRoute\simpleDispatcher($routeDefinitionCallback);
$routeInfo  = $dispatcher->dispatch($req->getMethod(), '/' . $req->getPath());
switch ($routeInfo[0])
{
    case \FastRoute\Dispatcher::NOT_FOUND:
        $resp = $response();
        $resp->setBody('404 - Page not found');
        $resp->setStatus(404);
        \Sabre\HTTP\Sapi::sendResponse($resp);
        exit;
        break;
    case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        $resp = $response();
        $resp->setHeader('Allow', $allowedMethods);
        $resp->setBody('405 - Method not allowed');
        $resp->setStatus(405);
        \Sabre\HTTP\Sapi::sendResponse($resp);
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

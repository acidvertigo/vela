<?php
namespace Vela\Core;

use \Sabre\HTTP\Request;

Class User
{
    private $request;
  
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
  
    public function getUserAgent()
    {
        return $this->request->getRawServerValue('HTTP_USER_AGENT');
    }

    public function getUserIp()
    {
    if (getenv('HTTP_CLIENT_IP'))
    {
            $userIp = getenv('HTTP_CLIENT_IP');
    } else if (getenv('HTTP_X_FORWARDED_FOR'))
    {
            $userIp = getenv('HTTP_X_FORWARDED_FOR');
    } else if (getenv('HTTP_X_FORWARDED'))
    {
            $userIp = getenv('HTTP_X_FORWARDED');
    } else if (getenv('HTTP_FORWARDED_FOR'))
    {
            $userIp = getenv('HTTP_FORWARDED_FOR');
    } else if (getenv('HTTP_FORWARDED'))
    {
            $userIp = getenv('HTTP_FORWARDED');
    } else if (getenv('REMOTE_ADDR'))
    {
            $userIp = getenv('REMOTE_ADDR');
    } else
    {
            $userIp = 'UNKNOWN';
    }

    return $userIp;
    }
  
    public function isRobot($userAgent, array $robots = [])
    {
    foreach ($robots as $robot)
    {
        if (strpos(strtolower($userAgent), $robot) !== false)
        {
        return true;
        }
    }

    return false;
    }

}

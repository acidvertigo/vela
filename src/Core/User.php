<?php

namespace Vela\Core;

use \Sabre\HTTP\Request;

/**
 * Class User handles user data
 */
Class User
{
    /** @var object $request **/
    private $request;
    
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    
    /**
     * Retrieve the user agent string
     * @return string|null The user agent string
     */
    public function getUserAgent()
    {
        return $this->request->getRawServerValue('HTTP_USER_AGENT');
    }

    /**
     * Gets the user ip address
     * @return string The user ip address
     */
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
  
    /**
     * Checks if user is a web crawler
     * @param string $userAgent
     * @param array $robots
     * @return bool True if user is a robot
     */
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

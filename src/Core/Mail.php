<?php

namespace Vela\Core;

/**
 * Mail class
 *
 * Mail object builder class
 */
class Mail
{
    private $config;
    private $transport;

	/**
	 * Class constructor
	 * @param \Vela\Core\Config $config
	 */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }
    
	/**
	 * @return \Swift_SmtpTransport
	 */
    private function getTransport()
    {
        if ($this->config->get('mailer.system') == 'phpmail')
        {
            $this->transport = \Swift_MailTransport::newInstance();
        } else
        {
             $this->transport = \Swift_SmtpTransport::newInstance('smtp.example.com', 25)
                         ->setUsername('test@example.com')
                         ->setPassword('');
        }
        return $this->transport;
    }
    
	/**
	 * @return \Swift_Mailer
	 */
    public function createMailer()
    {
        return \Swift_Mailer::newInstance($this->getTransport());
    }
    
}
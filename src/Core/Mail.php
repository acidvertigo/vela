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
        return $this->newMailInstance(($this->config->get('mail.system') == 'phpmail') ? false : true);
    }
    
    /**
     * @return \Swift_SmtpTransport
     */
    public function newMailInstance($smtp = false)
    {
        if ($smtp)
        {
                return \Swift_SmtpTransport::newInstance('smtp.example.com', 25)
                                        ->setUsername('test@example.com')
                                        ->setPassword('');
        }
 
        return \Swift_MailTransport::newInstance();
    }
    
    /**
     * @return \Swift_Mailer
     */
    public function createMailer()
    {
        return \Swift_Mailer::newInstance($this->getTransport());
    }

    /**
     * 
     * @param string $subject
     * @param string $msg
     * @param array $to
     * @return \Swift_Mailer
     */
    public function setMessage($subject, $msg, array $to)
    {
        $message = \Swift_Message::newInstance();
        $message->setSubject($subject)
                ->setFrom($this->config->get('mailer.from'))
                ->setTo($to)
                ->addPart($msg, 'text/html');
        return $this->createMailer()->send($message);
    }

}

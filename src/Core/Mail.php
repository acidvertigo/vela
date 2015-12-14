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
     * @throws \Exception
     */
    private function getTransport()
    {
        switch ($this->config->get('mail.system')) {
        case 'phpmail': 
            return $this->transport = \Swift_MailTransport::newInstance();
        case 'smtp':
            return $this->transport = \Swift_SmtpTransport::newInstance('smtp.example.com', 25)
                                      ->setUsername('test@example.com')
                                      ->setPassword('');
        default:
            twrow new \Exception ('No valid Mail transport defined');
        }
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

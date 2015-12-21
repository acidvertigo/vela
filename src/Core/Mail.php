<?php
namespace Vela\Core;

/**
 * Mail class
 *
 * Mail object builder class
 */
class Mail
{
    /** @var \config $config \Vela\Config object */
    private $config;
    /** @var bool $smtp True if smtp transport is set*/
    private $smtp;

    /**
     * Class constructor
     * @param \Vela\Core\Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->smtp = $this->config->get('mail.system') == 'phpmail') ? false : true;
    }

    /**
     * @return \Swift_Mailer
     */
    public function createMailer()
    {
        return \Swift_Mailer::newInstance($this->getTransport());
    }
    
    /**
     * Create a new mail object instance
     * @return object \Swift mail or smtp transport
     */
    public function getTransport()
    {
        if ($this->smtp)
        {
            return \Swift_SmtpTransport::newInstance($this->config->get('mail.server'), (int)$this->config->get('mail.port'))
                                        ->setUsername($this->config->get('mail.username'))
                                        ->setPassword($this->config->get('mail.password'));
        }
 
        return \Swift_MailTransport::newInstance();
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

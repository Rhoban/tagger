<?php

namespace App\Service;
use Twig\Environment;

class Notifications
{
    protected $twig;
    protected $mailer;

    public function __construct(Environment $twig,  \Swift_Mailer $mailer)
    {
        $this->twig = $twig;
        $this->mailer = $mailer;
    }

    public function notify(array $users, string $templateName)
    {
        $template = $this->twig->loadTemplate('email/'.$templateName.'.email.twig');

        foreach ($users as $user) {
            if ($user->getAcceptNotifications()) {
                $vars = ['user' => $user];
                $subject = $template->renderBlock('subject', $vars);
                $body = $template->renderBlock('body', $vars);

                $message = new \Swift_Message($subject);
                $message
                    ->setFrom(['metabot.notifications@gmail.com' => 'Rhoban Tagger'])
                    ->setTo($user->getEmail())
                    ->setBody($body)
                    ;

                $this->mailer->send($message);
            }
        }
    }
}

<?php // src/Services/MailerService.php

namespace App\Services;

use Twig\Environment;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportException;

class MailerService
{
    /**
     * @var MailerInterface
     */
    private $mailer;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * MailerService constructor.
     *
     * @param MailerInterface       $mailer
     * @param Environment   $twig
     */
    public function __construct(MailerInterface $mailer, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    /**
     * sendEmail permet de préparer email en récupérant
     * le sujet, l'adresse mail, le template du message
     * et un tableau contenant les varaibles que l'on 
     * introduira dans le corp du message
     * @param string $subject
     * @param string $mail
     * @param string $template
     * @param array $params
     * @return void
     */
    public function sendEmail(string $subject, string $mail, string $template, array $params = null): void
    {
        // On attrape l 'erreur si il y un soucis lor de l'envoi de l'email.
        try {
            // On prépare l'email qui sera envoyé en mettant les 
            // variables dans les différent champs du mail.
            $email = (new Email())
                ->from($mail)
                ->to($mail)
                ->subject($subject)
                ->html($this->twig->render($template, $params), 'UTF-8');
            $this->mailer->send($email);
        } catch (TransportException $e) {
        }
    }
}

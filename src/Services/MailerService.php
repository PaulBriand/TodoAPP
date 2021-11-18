<?php

namespace App\Services;

use Twig\Environment;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;

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


    public function sendEmail(string $subject, string $mail, string $template, array $params): void
    {
        $email = new Email();

        $email->from($mail)
            ->to($mail)
            ->subject($subject)
            ->html($this->twig->render($template, $params));

        $this->mailer->send($email);
    }
}

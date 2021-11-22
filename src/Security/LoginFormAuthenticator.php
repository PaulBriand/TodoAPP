<?php

namespace App\Security;

use DateTime;
use App\Entity\User;
use Twig\Environment;
use App\Services\MailerService;
use Twig\Loader\LoaderInterface;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private UrlGeneratorInterface $urlGenerator;

    private MailerService $mailer;

    /**
     * @var TaskRepository
     */
    private $repository;

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    public function __construct(UrlGeneratorInterface $urlGenerator, TaskRepository $repository, EntityManagerInterface $manager, MailerService $mailer)
    {
        $this->urlGenerator = $urlGenerator;
        $this->repository = $repository;
        $this->manager = $manager;
        $this->mailer = $mailer;
    }

    public function authenticate(Request $request): PassportInterface
    {
        $email = $request->request->get('email', '');

        $request->getSession()->set(Security::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        //On intitialise la fonction
        $mailuser = $request->request->get('email', ''); //On récupère le MailUser 

        //On récupère le nom d'utilisateur à partir de son adresse e-mail
        $username = explode('@', $mailuser)[0];

        // On instantie la date d'aujourd'hui
        $now = new DateTime();

        // On récupère les tâches
        $tasks = $this->repository->findAll();

        // On initialise le msg 
        $msg = '';

        // On boucle sur la liste des tâches
        foreach ($tasks as $task) {
            // On calcule la durée qui sépare la Date d'aujourd'hui avec la date 
            //d'échéance de la tâche.
            $diffDate = $now->diff($task->getDueAt());

            // On ajoute les paramètres que l'on souhaite afficher dans le message
            $parameters = [
                'username' => $username,
                'task' => $task,
                'msg' => $msg
            ];

            /* Si la durée est inférieur ou égale 2 jours et que la date d'aujourd'hui
          * et que la date d'aujourd'hui et antérieur à la date d'échéance
          * on écrit un message avertissant l'utilisateur que la date arrive bientôt
          */

            if ($diffDate->days <= 2 && ($now < $task->getDueAt())) {


                $msg = ' arrive à échéance le '; // Le bout de message d'avertissement

                // On envoie l'e-mail
                $this->mailer->sendEmail(
                    "Attention ! Votre tache arrive à échéance !",
                    $mailuser,
                    'emails\alert.html.twig',
                    $parameters
                );

                // Si la durée est inférieur ou égale 2 jours et que la date d'aujourd'hui
                // et que la date d'aujourd'hui et antérieur à la date d'échéance
                // on écrit un message avertissant l'utilisateur que la date est passée
            } else if ($now > $task->getDueAt()) {
                //Le bout de message qui informe le dépassement de l'échéance
                $msg = " a dépassé la date d'échéance le ";

                // On envoie le e-mail
                $this->mailer->sendEmail(
                    "Attention ! Votre tache est arrivée à échéance !",
                    $mailuser,
                    'emails\alert.html.twig',
                    $parameters
                );
            }
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // For example:
        return new RedirectResponse($this->urlGenerator->generate('task_listing'));
        // throw new \Exception('TODO: provide a valid redirect inside '.__FILE__);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}

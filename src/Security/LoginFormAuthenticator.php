<?php

namespace App\Security;

use DateTime;
use App\Entity\User;
use Twig\Environment;
use App\Services\MailerService;
use Twig\Loader\LoaderInterface;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
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

    /**
     * @var TaskRepository
     */
    private $repository;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     *
     * @var MailerService
     */
    private $mailer;


    public function __construct(MailerService $mailer, UrlGeneratorInterface $urlGenerator, TaskRepository $repository, UserRepository $userRepository, EntityManagerInterface $manager)
    {
        $this->urlGenerator = $urlGenerator;
        $this->repository = $repository;
        $this->userRepository = $userRepository;
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
        $mailuser = $request->request->get('email', ''); //On r??cup??re le MailUser 

        //On r??cup??re le nom d'utilisateur ?? partir de son adresse e-mail
        $username = explode('@', $mailuser)[0];

        // On instantie la date d'aujourd'hui
        $now = new DateTime();

        $user = $this->userRepository->findOneBy(['email' => $mailuser]);
        // On r??cup??re les t??ches
        $tasks = $this->repository->findBy(['user' => $user->getId(), 'isArchived' => '0']);

        // On initialise le msg 
        $msg = '';

        // On boucle sur la liste des t??ches
        foreach ($tasks as $task) {
            // On calcule la dur??e qui s??pare la Date d'aujourd'hui avec la date 
            //d'??ch??ance de la t??che.
            $diffDate = $now->diff($task->getDueAt());

            // On ajoute les param??tres que l'on souhaite afficher dans le message
            $parameters = [
                'username' => $username,
                'task' => $task,
                'msg' => $msg
            ];

            /* Si la dur??e est inf??rieur ou ??gale 2 jours et que la date d'aujourd'hui
          * et que la date d'aujourd'hui et ant??rieur ?? la date d'??ch??ance
          * on ??crit un message avertissant l'utilisateur que la date arrive bient??t
          */

            if ($diffDate->days <= 2 && ($now < $task->getDueAt())) {


                $msg = ' arrive ?? ??ch??ance le '; // Le bout de message d'avertissement

                // On envoie l'e-mail
                $this->mailer->sendEmail(
                    "Attention ! Votre tache arrive ?? ??ch??ance !",
                    $mailuser,
                    'emails\alert.html.twig',
                    $parameters
                );

                // Si la dur??e est inf??rieur ou ??gale 2 jours et que la date d'aujourd'hui
                // et que la date d'aujourd'hui et ant??rieur ?? la date d'??ch??ance
                // on ??crit un message avertissant l'utilisateur que la date est pass??e
            } else if ($now > $task->getDueAt()) {
                //Le bout de message qui informe le d??passement de l'??ch??ance
                $msg = " a d??pass?? la date d'??ch??ance le ";

                // On envoie le e-mail
                $this->mailer->sendEmail(
                    "Attention ! Votre tache est arriv??e ?? ??ch??ance !",
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

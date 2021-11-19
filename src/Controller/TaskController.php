<?php

namespace App\Controller;

use DateTime;
use App\Entity\Task;
use App\Entity\User;
use App\Form\TaskType;
use App\Services\MailerService;
use Symfony\Component\Mime\Email;
use App\Repository\TaskRepository;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TaskController extends AbstractController
{
    /**
     * @var TaskRepository
     */
    private $repository;

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    public function __construct(TaskRepository $repository, EntityManagerInterface $manager)
    {
        $this->repository = $repository;
        $this->manager = $manager;
    }

    /**
     * @Route("/task/listing", name="task_listing")
     */
    public function index(MailerService $mailer): Response
    {

        // On intitialise la fonction
        $user = $this->getUser(); //On récupère le User 

        // On récupère le nom d'utilisateur à partir de son adresse e-mail
        $username = explode('@', $user->getEmail())[0];

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
                $mailer->sendEmail(
                    "Attention ! Votre tache arrive à échéance !",
                    $user->getEmail(),
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
                $mailer->sendEmail(
                    "Attention ! Votre tache est arrivée à échéance !",
                    $user->getEmail(),
                    'emails\alert.html.twig',
                    $parameters
                );
            }
        }


        return $this->render('task/index.html.twig', [
            'tasks' => $tasks,
        ]);
    }

    /**
     * @Route("/task/create", name="task_create")
     * @Route("/task/update/{id}", name="task_update", requirements={"id"="\d+"})
     */
    public function Task(Task $task = null, Request $request)
    {

        if (!$task) {
            $task = new Task;
            $task->setCreatedAt(new \DateTime());
        }

        $form = $this->createForm(TaskType::class, $task, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->manager->persist($task);
            $this->manager->flush();

            return $this->redirectToRoute('task_listing');
        }

        return $this->render('task/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/task/delete/{id}", name="task_delete", requirements={"id"="\d+"})
     */
    public function deleteTask(Task $task)
    {
        $this->manager->remove($task);
        $this->manager->flush();

        return $this->redirectToRoute('task_listing');
    }
}

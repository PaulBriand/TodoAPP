<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Form\TaskType;
use App\Services\MailerService;
use Symfony\Component\Mime\Email;
use App\Repository\TaskRepository;
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
    public function index(MailerInterface $mailer): Response
    {

        $user = $this->getUser();
        // dd($user);

        $tasks = $this->repository->findAll();

        try {
            $email = (new TemplatedEmail())
                ->from("briand.paul@outlook.fr")
                ->to("briand.paul@outlook.fr")
                ->subject("Toto")

                // path of the Twig template to render
                ->htmlTemplate('emails/alert.html.twig')

                // pass variables (name => value) to the template
                ->context([
                    'expiration_date' => new \DateTime('+7 days'),
                    'username' => 'foo',
                ]);

            $mailer->send($email);
        } catch (TransportException $e) {
            print $e->getMessage() . "\n";
            throw $e;
        }

        // var_dump($tasks);
        // die;
        //dd($tasks);

        return $this->render('task/index.html.twig', [
            'tasks' => $tasks,
        ]);
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

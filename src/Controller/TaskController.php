<?php

namespace App\Controller;

use DateTime;
use DateInterval;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use function GuzzleHttp\Promise\task;

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
    public function index(): Response
    {
        // On récupère les tâches
        $user = $this->getUser();
        $id = $user->getId();
        $slug = $user->getIsPrefered();
        $role = $user->getRoles();
        $admin = 'ROLE_ADMIN';

        if (in_array($admin, $role)) {
            $tasks = $this->repository->findBy(['isArchived' => '0']);
        } else {
            $tasks = $this->repository->findBy(['user' => $id, 'isArchived' => '0']);
        }

        return $this->render('task/index.html.twig', [
            'tasks' => $tasks,
            'slug' => $slug
        ]);
    }

    /**
     * @Route("/task/archives", name="task_archives")
     */
    public function indexArchives(): Response
    {
        $user = $this->getUser();
        $role = $user->getRoles();
        $admin = "ROLE_ADMIN";
        $id = $user->getId();

        if (in_array($admin, $role)) {
            $tasks = $this->repository->findBy(['isArchived' => '1']);
        } else {
            $tasks = $this->repository->findBy(['user' => $id, 'isArchived' => '1']);
        }

        return $this->render('task/archives.html.twig', [
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
            $user = $this->getUser();
            $task->setUser($user);
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
        if ($task->getIsArchived()) {
            $this->manager->remove($task);
            $this->manager->flush();

            $this->addFlash(
                'success',
                'L\'action a bien effacée'
            );

            return $this->redirectToRoute('task_archives');
        } else {
            $this->manager->remove($task);
            $this->manager->flush();

            $this->addFlash(
                'success',
                'L\'action a bien effacée'
            );

            return $this->redirectToRoute('task_listing');
        }
    }
    /**
     * 
     *@Route("/task/listing/download", name="task_download")
     */
    public function dowloadPdf()
    {
        $user = $this->getUser();
        $id = $user->getId();
        $role = $user->getRoles();
        $admin = 'ROLE_ADMIN';

        if (in_array($admin, $role)) {
            $tasks = $this->repository->findBy(['isArchived' => '0']);
        } else {
            $tasks = $this->repository->findBy(['user' => $id, 'isArchived' => '0']);
        }
        // Gestion des options
        $pdfoption = new Options;
        $pdfoption->set('default', "Arial");
        // $pdfoption->setIsRemoteEnabled(true);

        // On va instantier le domPdf pour créer le téléchargement
        $dompdf = new Dompdf($pdfoption);

        $html = $this->renderView('pdf/pdfdownload.html.twig', [
            'tasks' => $tasks,

        ]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $fichier = 'J\'dore les pdfs';
        $dompdf->stream($fichier, [
            'Attachement' => true
        ]);
    }

    public function checkDueAt(Task $task)
    {
        $flag = false;
        $dueAt = $task->getDueAt();
        $today = new \DateTime();

        if ($today > $dueAt) {
            $flag = true;
        }

        return $flag;
    }

    /**
     * Undocumented function
     *
     * @Route ("/task/archive/{id}", name="task_archive", requirements={"id"="\d+"})
     * @return Response
     */
    public function archiveTask(Task $task): Response
    {
        if ($this->checkDueAt($task)) {
            $task->setIsArchived(1);
            $this->manager->persist($task);
            $this->manager->flush();
            $this->addFlash(
                'text-success',
                'La tache à bien été archivée !'
            );
        } else {
            $this->addFlash(
                'text-warning',
                'Impossible d\'archiver la tache car la date n\'est pas arrivé !'
            );
        }
        return $this->redirectToRoute("task_listing");
    }

    /**
     *@Route("/task/archives_{slug}")
     * @param String $slug
     * @return void
     */
    public function displayTable(String $slug)
    {
        //  Récupération des infos de l'utilisateur.
        $user = $this->getUser();

        if ($slug != 'manual') {
            $tasks = $this->repository->findAll();
            $user->setIsPrefered(0);
            for ($i = 0; $i < count($tasks); $i++) {
                if ($this->checkDueAt($tasks[$i])) {
                    $this->archiveTask($tasks[$i]);
                }
            }
        } else {
            $user->setIsPrefered(1);
        }
        $this->manager->persist($user);
        $this->manager->flush();

        return $this->index();
    }
}

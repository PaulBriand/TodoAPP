<?php

namespace App\Controller;

use DateTime;
use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
    public function index(): Response
    {
        // On récupère les tâches
        $tasks = $this->repository->findAll();

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

        $this->addFlash(
            'Delete',
            'L\'action a bien effacée'
        );
        $this->addFlash(
            'success',
            'L\'action a bien été effectuée'
        );

        return $this->redirectToRoute('task_listing');
    }


    /**
     * @Route("/task/listing/download", name="Task_Download")
     */
    public function downloadPDF()
    {
        $tasks = $this->repository->findAll();

        $PDFOptions = new Options;
        $PDFOptions->set('defaultFont', 'Arial');
        //$PDFOptions->setIsRemoteEnabled(true);

        $DomPDF = new Dompdf($PDFOptions);

        $html = $this->renderView('pdf/pdfdownload.html.twig', [
            'tasks' => $tasks
        ]);

        $DomPDF->loadHtml($html);
        $DomPDF->setPaper('A4', 'landscape');
        $DomPDF->render();

        $file = 'Jadore les PDF';

        $DomPDF->stream($file, ['Attachement => true']);

        return new Response();
    }
}

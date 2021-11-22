<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    /**
     *
     * @var UserRepository
     */
    private $repository;

    public function __construct(
        UserRepository $repository,
        EntityManagerInterface $manager,
        UserPasswordHasherInterface $encoder
    ) {

        $this->repository = $repository;
        $this->manager = $manager;
        $this->encoder = $encoder;
    }
    /**
     * @Route("/user/listing", name="user_listing")
     */
    public function index(): Response
    {

        // Dans le repo, on récupère les entrées 
        $users = $this->repository->findAll();


        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }

    /**
     * @Route("/user/create", name="user_create")
     * @Route("/user/update/{id}", name="user_update", requirements={"id" = "\d+"})
     * 
     */


    public function user($user = null, Request $resquest)
    {
        $flag = false;

        if (!$user) {
            $user = new User;
            $form = $this->createForm(UserType::class, $user, []);
            $flag = true;
        } else {
            $form = $this->createForm(UsertUpdateTyoe::class, $user, []);
        }

        $form->handleRequest($resquest);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($flag == true) {
                $hash = $this->encoder->hasPassword($user, $form['password']->getData());
                $user->setPassword($hash);
            }


            $this->manager->persis($user);
            $this->manager->flush();


            return $this->redirectToRoute('user_listing');
        }


        return $this->render('user/create.html.twig', [
            'form' => $form->createView()
        ]);
    }
}

<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Form\TagType;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/tag")
 */
class TagController extends AbstractController
{
    /**
     * @Route("/listing", name="tag_listing", methods={"GET"})
     */
    public function index(TagRepository $tagRepository): Response
    {
        return $this->render('tag/index.html.twig', [
            'tags' => $tagRepository->findAll(),
        ]);
    }

    /**
     * @Route("/create", name="tag_create", methods={"GET", "POST"})
     */
    public function new(Request $request, EntityManagerInterface $entityManager, TagRepository $tagRepository): Response
    {
        $tag = new Tag();
        // dd($tag);
        $form = $this->createForm(TagType::class, $tag);
        $form->handleRequest($request);
        $arrayTags = $tagRepository->findAll();

        if ($form->isSubmitted() && $form->isValid()) {

            try {
                $entityManager->persist($tag);
                $entityManager->flush();
            } catch (Exception $e) {
                $this->addFlash(
                    'danger',
                    'flash.create_impossible'
                );
                return $this->redirectToRoute('tag_create', [], Response::HTTP_SEE_OTHER);
            }

            $this->addFlash(
                'success',
                'flash.create_complete'
            );
            return $this->redirectToRoute('tag_listing', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tag/new.html.twig', [
            'tag' => $tag,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="tag_show", methods={"GET"})
     */
    public function show(Tag $tag): Response
    {
        return $this->render('tag/show.html.twig', [
            'tag' => $tag,
        ]);
    }

    /**
     * @Route("/update/{id}", name="tag_update", methods={"GET", "POST"})
     */
    public function edit(Request $request, Tag $tag, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TagType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
            } catch (Exception $e) {
                $this->addFlash(
                    'danger',
                    'flash.update_impossible'
                );
                return $this->redirectToRoute('tag_update', ["id" => $tag->getId()], Response::HTTP_SEE_OTHER);
            }
            $this->addFlash(
                'success',
                'flash.update_completed'
            );

            return $this->redirectToRoute('tag_listing', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tag/edit.html.twig', [
            'tag' => $tag,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/delete/{id}", name="tag_delete", methods={"POST"})
     */
    public function delete(Request $request, Tag $tag, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $tag->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($tag);
                $entityManager->flush();
            } catch (Exception $e) {
                // dd($e);
                $this->addFlash(
                    'danger',
                    'flash.delete_impossible'
                );
                return $this->redirectToRoute('tag_listing', [], Response::HTTP_SEE_OTHER);
            }
        }
        $this->addFlash(
            'success',
            'flash.delete_complete'
        );

        return $this->redirectToRoute('tag_listing', [], Response::HTTP_SEE_OTHER);
    }
}

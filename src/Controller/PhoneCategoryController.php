<?php

namespace App\Controller;

use App\Entity\PhoneCategory;
use App\Form\PhoneCategoryType;
use App\Repository\PhoneCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/phone/categories')]
class PhoneCategoryController extends AbstractController
{
    #[Route('', name: 'phone_category_index', methods: ['GET'])]
    public function index(PhoneCategoryRepository $repo): Response
    {
        return $this->render('phone_category/index.html.twig', [
            'categories' => $repo->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'phone_category_new', methods: ['GET','POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $category = new PhoneCategory();
        $form = $this->createForm(PhoneCategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();
            $this->addFlash('success', 'Catégorie créée.');
            return $this->redirectToRoute('phone_category_index');
        }

        return $this->render('phone_category/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'phone_category_edit', methods: ['GET','POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, PhoneCategory $category, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(PhoneCategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Catégorie mise à jour.');
            return $this->redirectToRoute('phone_category_index');
        }

        return $this->render('phone_category/edit.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }

    #[Route('/{id}/delete', name: 'phone_category_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, PhoneCategory $category, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete_category_'.$category->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('CSRF invalide');
        }

        if ($category->getNumbers()->count() > 0) {
            $this->addFlash('warning', 'Impossible de supprimer : la catégorie contient des numéros.');
            return $this->redirectToRoute('phone_category_index');
        }

        $em->remove($category);
        $em->flush();
        $this->addFlash('success', 'Catégorie supprimée.');
        return $this->redirectToRoute('phone_category_index');
    }
}

<?php

namespace App\Controller;

use App\Entity\PhoneNumber;
use App\Form\PhoneNumberType;
use App\Repository\PhoneCategoryRepository;
use App\Repository\PhoneNumberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/phone/numbers')]
class PhoneNumberController extends AbstractController
{
    #[Route('', name: 'phone_number_index', methods: ['GET'])]
    public function index(PhoneCategoryRepository $catRepo): Response
    {
        // Récupère les catégories + numéros triés par nom (OrderBy sur l'entité)
        $categories = $catRepo->findBy([], ['name' => 'ASC']);

        return $this->render('phone_number/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/new', name: 'phone_number_new', methods: ['GET','POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $number = new PhoneNumber();
        $form = $this->createForm(PhoneNumberType::class, $number);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($number);
            $em->flush();
            $this->addFlash('success', 'Numéro ajouté.');
            return $this->redirectToRoute('phone_number_index');
        }

        return $this->render('phone_number/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'phone_number_edit', methods: ['GET','POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, PhoneNumber $number, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(PhoneNumberType::class, $number);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Numéro mis à jour.');
            return $this->redirectToRoute('phone_number_index');
        }

        return $this->render('phone_number/edit.html.twig', [
            'form' => $form->createView(),
            'number' => $number,
        ]);
    }

    #[Route('/{id}/delete', name: 'phone_number_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, PhoneNumber $number, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete_number_'.$number->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('CSRF invalide');
        }

        $em->remove($number);
        $em->flush();
        $this->addFlash('success', 'Numéro supprimé.');
        return $this->redirectToRoute('phone_number_index');
    }
}

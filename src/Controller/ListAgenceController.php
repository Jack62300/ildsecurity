<?php

namespace App\Controller;

use App\Entity\ListAgence;
use App\Form\ListAgenceType;
use App\Repository\ListAgenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/agences')]
class ListAgenceController extends AbstractController
{
    #[Route('', name: 'agences_index', methods: ['GET'])]
    public function index(ListAgenceRepository $repo): Response
    {
        $agences = $repo->findBy([], ['name' => 'ASC']);
        return $this->render('agences/index.html.twig', compact('agences'));
    }

    #[Route('/new', name: 'agences_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $agence = new ListAgence();
        $form = $this->createForm(ListAgenceType::class, $agence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($agence);
            $em->flush();
            $this->addFlash('agence_success', 'Agence créée.');
            return $this->redirectToRoute('agences_index');
        }

        return $this->render('agences/form.html.twig', [
            'form' => $form->createView(),
            'agence' => $agence,
            'mode' => 'new',
        ]);
    }

    #[Route('/{id}/edit', name: 'agences_edit', methods: ['GET','POST'])]
    public function edit(ListAgence $agence, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ListAgenceType::class, $agence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('agence_success', 'Agence mise à jour.');
            return $this->redirectToRoute('agences_index');
        }

        return $this->render('agences/form.html.twig', [
            'form' => $form->createView(),
            'agence' => $agence,
            'mode' => 'edit',
        ]);
    }

    #[Route('/{id}/delete', name: 'agences_delete', methods: ['POST'])]
    public function delete(ListAgence $agence, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_agence_'.$agence->getId(), $request->request->get('_token'))) {
            $em->remove($agence);
            $em->flush();
            $this->addFlash('agence_success', 'Agence supprimée.');
        }
        return $this->redirectToRoute('agences_index');
    }
}

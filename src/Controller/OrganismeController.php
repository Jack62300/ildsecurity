<?php
// src/Controller/OrganismeController.php
namespace App\Controller;

use App\Entity\Organisme;
use App\Form\OrganismeType;
use App\Repository\OrganismeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/organismes')]
class OrganismeController extends AbstractController
{
    #[Route('', name: 'organisme_index', methods: ['GET'])]
    public function index(OrganismeRepository $repo): Response
    {
        $items = $repo->findBy([], ['name' => 'ASC']);

        return $this->render('organismes/index.html.twig', [
            'items' => $items,
        ]);
    }

    #[Route('/new', name: 'organisme_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $organisme = new Organisme();
        $form = $this->createForm(OrganismeType::class, $organisme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($organisme);
            $em->flush();
            $this->addFlash('organisme_success', 'Organisme créé.');
            return $this->redirectToRoute('organisme_index');
        }

        return $this->render('organismes/form.html.twig', [
            'form' => $form->createView(),
            'organisme' => $organisme,
            'mode' => 'new',
        ]);
    }

    #[Route('/{id}/edit', name: 'organisme_edit', methods: ['GET','POST'])]
    public function edit(Organisme $organisme, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(OrganismeType::class, $organisme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('organisme_success', 'Organisme mis à jour.');
            return $this->redirectToRoute('organisme_index');
        }

        return $this->render('organismes/form.html.twig', [
            'form' => $form->createView(),
            'organisme' => $organisme,
            'mode' => 'edit',
        ]);
    }

    #[Route('/{id}/delete', name: 'organisme_delete', methods: ['POST'])]
    public function delete(Organisme $organisme, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_organisme_'.$organisme->getId(), (string) $request->request->get('_token'))) {
            $em->remove($organisme);
            $em->flush();
            $this->addFlash('organisme_success', 'Organisme supprimé.');
        } else {
            $this->addFlash('organisme_error', 'Jeton CSRF invalide.');
        }
        return $this->redirectToRoute('organisme_index');
    }
}

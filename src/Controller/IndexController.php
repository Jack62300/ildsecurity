<?php

namespace App\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class IndexController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    #[IsGranted('ROLE_USER')]
    public function index(AuthenticationUtils $authUtils,
    ClientRepository $clientRepo,
    PaginatorInterface $paginator,
    Request $request): Response
    {
      // Tri A→Z sur le nom
    $qb = $clientRepo->findBy([],['nom' => 'ASC']);

    // Paginer (12 cartes par page, adapte le per-page)
    $pagination = $paginator->paginate(
        $qb,                                // Query | QueryBuilder | array
        $request->query->getInt('page', 1), // page courante
        12                                  // éléments par page
    );
    // dump($qb);

    return $this->render('index/index.html.twig', [
        'controller_name' => 'IndexController',
        'error'      => $authUtils->getLastAuthenticationError(),
        'pagination' => $pagination,
        'clients' => $qb
    ]);
    }
}

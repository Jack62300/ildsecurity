<?php
namespace App\Controller;

use App\Repository\AuditLogRepository;
use Knp\Component\Pager\PaginatorInterface; // ✅ KnpPaginator
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuditController extends AbstractController
{
    #[Route('/admin/audit', name: 'audit_index')]
    public function index(Request $request, AuditLogRepository $repo, PaginatorInterface $paginator): Response
    {
        $qb = $repo->createQueryBuilder('a');

        // ------- Filtres -------
        if ($u = trim((string)$request->query->get('user'))) {
            $qb->andWhere('a.userIdentifier LIKE :u')->setParameter('u', "%$u%");
        }
        if ($ac = trim((string)$request->query->get('action'))) {
            $qb->andWhere('a.action = :ac')->setParameter('ac', $ac);
        }
        if ($m = trim((string)$request->query->get('method'))) {
            $qb->andWhere('a.method = :m')->setParameter('m', $m);
        }
        if ($r = trim((string)$request->query->get('route'))) {
            $qb->andWhere('a.route LIKE :r')->setParameter('r', "%$r%");
        }
        if ($from = $request->query->get('from')) {
            $qb->andWhere('a.createdAt >= :from')->setParameter('from', new \DateTimeImmutable($from.' 00:00:00'));
        }
        if ($to = $request->query->get('to')) {
            $qb->andWhere('a.createdAt <= :to')->setParameter('to', new \DateTimeImmutable($to.' 23:59:59'));
        }
        if ($q = trim((string)$request->query->get('q'))) {
            $qb->andWhere('a.path LIKE :q OR a.objectType LIKE :q OR a.objectId LIKE :q OR a.payload LIKE :q')
               ->setParameter('q', "%$q%");
        }

        // ------- Tri personnalisé : type puis date -------
        // Ordre de priorité des actions (modifiable)
        $priority = [
            'create',        // 0
            'update',        // 1
            'delete',        // 2
            'approve',       // 3
            'login_failure', // 4
            'login_success', // 5
            'login_attempt', // 6
            'device_pending',// 7
            'visit',         // 8
        ];

        // CASE … END pour ordonner les actions selon $priority
        $caseParts = [];
        foreach ($priority as $i => $name) {
            $param = 'act_'.$i;
            $caseParts[] = "WHEN a.action = :$param THEN $i";
            $qb->setParameter($param, $name);
        }
        $caseSql = 'CASE '.implode(' ', $caseParts).' ELSE 999 END';

        // On ajoute un select "HIDDEN" pour pouvoir l'utiliser dans ORDER BY
        $qb->addSelect($caseSql.' AS HIDDEN actionRank')
           ->orderBy('actionRank', 'ASC')
           ->addOrderBy('a.createdAt', 'DESC');

        // ------- Pagination (20 par page) -------
        $page = max(1, (int)$request->query->get('page', 1));
        $logs = $paginator->paginate($qb, $page, 20);

        return $this->render('audit/index.html.twig', [
            'logs' => $logs, // objet PaginationInterface (KNP)
        ]);
    }
}

<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


final class CommandeController extends AbstractController
{
    #[Route('/admin/commandes', name: 'admin_commandes')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $conn = $em->getConnection();
        
        $from   = $request->query->get('from') ?: null;
        $to     = $request->query->get('to')   ?: null;
        $statut = $request->query->get('statut') ?: null; 

        $params = [];
        $where  = ['1=1'];

        if ($from) {
            $where[] = "DATE(datecommande) >= :from";
            $params['from'] = $from;
        }
        if ($to) {
            $where[]  = "DATE(datecommande) <= :to";
            $params['to'] = $to;
        }
        if ($statut) {
            $where[] = "statut = :statut";
            $params['statut']  = $statut;
        }

        $whereStr = implode(' AND ', $where);

        $commandes = $conn->executeQuery(
            "SELECT c.id, c.datecommande, c.statut,
                    u.email AS user_email
             FROM commande c
             LEFT JOIN user u ON u.id = c.user_id
             WHERE $whereStr
             ORDER BY c.datecommande DESC",
            $params
        )->fetchAllAssociative();

        $counts = $conn->executeQuery(
            "SELECT statut, COUNT(id) AS total FROM commande GROUP BY statut"
        )->fetchAllAssociative();

        $countMap = ['EN_ATTENTE' => 0, 'EN_COURS' => 0, 'TERMINEE' => 0];
        foreach ($counts as $row) {
            $countMap[$row['statut']] = (int) $row['total'];
        }

        return $this->render('commande/index.html.twig', [
            'commandes' => $commandes,
            'countMap'  => $countMap,
            'from'      => $from,
            'to'        => $to,
            'statut'    => $statut,
        ]);
    }
}
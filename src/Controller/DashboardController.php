<?php

namespace App\Controller;

use App\Entity\Commande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $conn = $em->getConnection();

        $from = $request->query->get('from');
        $to   = $request->query->get('to');

        if (empty($from) || empty($to)) {
            $from = null;
            $to = null;
        }

        $clients = $conn->executeQuery(
            "SELECT COUNT(*)
             FROM user
             WHERE JSON_CONTAINS(roles, '\"ROLE_USER\"')"
        )->fetchOne();

        if ($from && $to) {
            $orders = $conn->executeQuery(
                "SELECT COUNT(id)
                 FROM commande
                 WHERE DATE(datecommande) BETWEEN :from AND :to",
                ['from' => $from, 'to' => $to]
            )->fetchOne();
        } else {
            $orders = $em->getRepository(Commande::class)->count([]);
        }

        if ($from && $to) {
            $revenue = $conn->executeQuery(
                "SELECT COALESCE(SUM(lc.prix * lc.quantite), 0)
                 FROM ligne_commande lc
                 JOIN commande c ON c.id = lc.commande_id
                 WHERE DATE(c.datecommande) BETWEEN :from AND :to",
                ['from' => $from, 'to' => $to]
            )->fetchOne();
        } else {
            $revenue = $conn->executeQuery(
                "SELECT COALESCE(SUM(prix * quantite), 0)
                 FROM ligne_commande"
            )->fetchOne();
        }

        if ($from && $to) {
            $topProduct = $conn->executeQuery(
                "SELECT p.nom AS name,
                        SUM(lc.quantite) AS total
                 FROM ligne_commande lc
                 JOIN produit p ON p.id = lc.produit_id
                 JOIN commande c ON c.id = lc.commande_id
                 WHERE DATE(c.datecommande) BETWEEN :from AND :to
                 GROUP BY p.id, p.nom
                 ORDER BY total DESC
                 LIMIT 1",
                ['from' => $from, 'to' => $to]
            )->fetchAssociative() ?: null;
        } else {
            $topProduct = $conn->executeQuery(
                "SELECT p.nom AS name,
                        SUM(lc.quantite) AS total
                 FROM ligne_commande lc
                 JOIN produit p ON p.id = lc.produit_id
                 GROUP BY p.id, p.nom
                 ORDER BY total DESC
                 LIMIT 1"
            )->fetchAssociative() ?: null;
        }

        if ($from && $to) {
            $ordersByDate = $conn->executeQuery(
                "SELECT DATE(datecommande) AS date,
                        COUNT(id) AS total
                 FROM commande
                 WHERE DATE(datecommande) BETWEEN :from AND :to
                 GROUP BY DATE(datecommande)
                 ORDER BY date ASC",
                ['from' => $from, 'to' => $to]
            )->fetchAllAssociative();
        } else {
            $ordersByDate = $conn->executeQuery(
                "SELECT DATE(datecommande) AS date,
                        COUNT(id) AS total
                 FROM commande
                 GROUP BY DATE(datecommande)
                 ORDER BY date ASC"
            )->fetchAllAssociative();
        }

        if (empty($ordersByDate)) {
            $labels = [];
            $values = [];
        } else {
            $labels = [];
            $values = [];
            foreach ($ordersByDate as $row) {
                $labels[] = $row['date'];
                $values[] = (int) $row['total'];
            }
        }

        if ($from && $to) {
            $salesRaw = $conn->executeQuery(
                "SELECT cat.nom AS name,
                        SUM(lc.quantite) AS total
                 FROM ligne_commande lc
                 JOIN produit p ON p.id = lc.produit_id
                 JOIN categorie cat ON cat.id = p.categorie_id
                 JOIN commande c ON c.id = lc.commande_id
                 WHERE DATE(c.datecommande) BETWEEN :from AND :to
                 GROUP BY cat.id, cat.nom
                 ORDER BY total DESC
                 LIMIT 6",
                ['from' => $from, 'to' => $to]
            )->fetchAllAssociative();
        } else {
            $salesRaw = $conn->executeQuery(
                "SELECT cat.nom AS name,
                        SUM(lc.quantite) AS total
                 FROM ligne_commande lc
                 JOIN produit p ON p.id = lc.produit_id
                 JOIN categorie cat ON cat.id = p.categorie_id
                 GROUP BY cat.id, cat.nom
                 ORDER BY total DESC
                 LIMIT 6"
            )->fetchAllAssociative();
        }

        $salesTotal = array_sum(array_column($salesRaw, 'total'));

        $salesByCategory = array_map(fn($r) => [
            'name'  => $r['name'],
            'total' => (int) $r['total'],
            'pct'   => $salesTotal > 0
                ? round(($r['total'] / $salesTotal) * 100)
                : 0,
        ], $salesRaw);

        return $this->render('dashboard/index.html.twig', [
            'clients'         => $clients,
            'orders'          => $orders,
            'revenue'         => $revenue,
            'topProduct'      => $topProduct,
            'labels'          => $labels,
            'values'          => $values,
            'salesByCategory' => $salesByCategory,
            'from'            => $from,
            'to'              => $to,
        ]);
    }
}
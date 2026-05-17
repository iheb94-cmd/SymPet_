<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use App\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Commande;
use App\Entity\LigneCommande;
use Doctrine\ORM\EntityManagerInterface;

class PaginationController extends AbstractController
{
    #[Route('/produits', name: 'app_pagination')]
    public function index(
        ProduitRepository $repo,
        Request $request,
        PaginationService $pagination
    ): Response {

        $page = $request->query->getInt('page', 1);

        $qb = $repo->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC');

        $result = $pagination->paginate($qb, $page);

        return $this->render('produit/index.html.twig', [
            'produits' => $result['data'],
            'pages' => $result['pages'],
            'currentPage' => $result['currentPage']
        ]);
    }



}

<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Service\PaginationService;
final class ProduitController extends AbstractController
{


    #[Route('/produit/{id}', name: 'product_show')]
    public function show(Produit $produit): Response
    {
        return $this->render('produit/show.html.twig', [
            'produit' => $produit
        ]);
    }



    #[Route('/produits', name: 'app_produits')]
    public function index(
        ProduitRepository $repo,
        Request $request,
        PaginationService $pagination
    ): Response {
        $page = $request->query->getInt('page', 1);

        $nom      = $request->query->get('nom', '');
        $prixMin  = $request->query->get('prixMin', '');
        $prixMax  = $request->query->get('prixMax', '');
        $categorie = $request->query->get('categorie', '');

        $qb = $repo->search(
            $nom ?: null,
            $prixMin !== '' ? (float) $prixMin : null,
            $prixMax !== '' ? (float) $prixMax : null,
            $categorie ?: null
        );

        $result = $pagination->paginate($qb, $page);

        return $this->render('produit/index.html.twig', [
            'produits'    => $result['data'],
            'pages'       => $result['pages'],
            'currentPage' => $result['currentPage'],
            // 👇 indispensable pour pré-remplir le formulaire
            'filters' => [
                'nom'      => $nom,
                'prixMin'  => $prixMin,
                'prixMax'  => $prixMax,
                'categorie' => $categorie,
            ],
        ]);
    }
}

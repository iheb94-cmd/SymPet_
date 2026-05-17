<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PanierController extends AbstractController
{
    #[Route('/panier', name: 'app_panier')]
    public function index(RequestStack $requestStack, ProduitRepository $repo): Response
    {
        $session = $requestStack->getSession();
        $panier = $session->get('panier', []);

        $data = [];
        $total = 0;

        foreach ($panier as $id => $quantite) {
            $produit = $repo->find($id);

            if ($produit) {
                $data[] = [
                    'produit' => $produit,
                    'quantite' => $quantite
                ];

                $total += $produit->getPrix() * $quantite;
            }
        }

        return $this->render('panier/index.html.twig', [
            'items' => $data,
            'total' => $total
        ]);
    }

    #[Route('/panier/add/{id}', name: 'panier_add')]
    public function add($id, RequestStack $requestStack): Response
    {
        $session = $requestStack->getSession();
        $panier = $session->get('panier', []);

        if (!empty($panier[$id])) {
            $panier[$id]++;
        } else {
            $panier[$id] = 1;
        }

        $session->set('panier', $panier);

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/panier/remove/{id}', name: 'panier_remove')]
    public function remove($id, RequestStack $requestStack): Response
    {
        $session = $requestStack->getSession();
        $panier = $session->get('panier', []);

        unset($panier[$id]);

        $session->set('panier', $panier);

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/panier/update/{id}/{qte}', name: 'panier_update')]
    public function update($id, $qte, RequestStack $requestStack): Response
    {
        $session = $requestStack->getSession();
        $panier = $session->get('panier', []);

        if ($qte > 0) {
            $panier[$id] = $qte;
        }

        $session->set('panier', $panier);

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/panier/valider', name: 'panier_valider')]
    public function valider(
        RequestStack $requestStack,
        ProduitRepository $repo,
        EntityManagerInterface $em
    ): Response {

        $session = $requestStack->getSession();
        $panier = $session->get('panier', []);

        if (empty($panier)) {
            return $this->redirectToRoute('app_panier');
        }

        $commande = new Commande();
        $commande->setDatecommande(new \DateTime());
        $commande->setStatut('en attente');


        if ($this->getUser()) {
            $commande->setUser($this->getUser());
        }

        $em->persist($commande);

        foreach ($panier as $id => $quantite) {

            $produit = $repo->find($id);

            if ($produit) {
                $ligne = new LigneCommande();

                $ligne->setProduit($produit);
                $ligne->setQuantite($quantite);
                $ligne->setPrix($produit->getPrix());
                $ligne->setCommande($commande);

                $em->persist($ligne);
            }
        }

        $em->flush();


        $session->remove('panier');

        return $this->redirectToRoute('commande_success');
    }


    #[Route('/commande/success', name: 'commande_success')]
    public function success(): Response
    {
        return $this->render('commande/success.html.twig');
    }
}

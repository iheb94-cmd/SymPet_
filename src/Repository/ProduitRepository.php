<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    public function search(?string $nom, ?float $prixMin, ?float $prixMax, ?string $categorie)
    {
        $qb = $this->createQueryBuilder('p');

        if (!empty($nom)) {
            $qb->andWhere('p.nom LIKE :nom')
                ->setParameter('nom', '%' . $nom . '%');
        }

        if ($prixMin !== null && $prixMin !== '' && is_numeric($prixMin)) {
            $qb->andWhere('p.prix >= :prixMin')
                ->setParameter('prixMin', (float) $prixMin);
        }

        if ($prixMax !== null && $prixMax !== '' && is_numeric($prixMax)) {
            $qb->andWhere('p.prix <= :prixMax')
                ->setParameter('prixMax', (float) $prixMax);
        }

        if (!empty($categorie)) {
            $qb->join('p.categorie', 'c')
                ->andWhere('c.nom LIKE :categorie')
                ->setParameter('categorie', '%' . $categorie . '%');
        }

        return $qb->orderBy('p.id', 'DESC');
    }
}

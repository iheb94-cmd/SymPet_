<?php

namespace App\Service;
use Doctrine\ORM\QueryBuilder;

class PaginationService
{
    private int $limit = 8;

    public function paginate(QueryBuilder $qb, int $page): array
    {
        $page = max($page, 1);
        $offset = ($page - 1) * $this->limit;

        // ✅ Clone AVANT toute modification
        $countQb = clone $qb;
        $alias = $qb->getRootAliases()[0];

        $total = $countQb
            ->select("COUNT(DISTINCT $alias.id)")  // DISTINCT évite les doublons avec les joins
            ->getQuery()
            ->getSingleScalarResult();

        // ✅ Requête principale séparée, non affectée par le clone
        $data = $qb
            ->setFirstResult($offset)
            ->setMaxResults($this->limit)
            ->getQuery()
            ->getResult();

        return [
            'data'        => $data,
            'total'       => $total,
            'pages'       => (int) ceil($total / $this->limit),
            'currentPage' => $page,
            'offset'      => $offset,
        ];
    }
}

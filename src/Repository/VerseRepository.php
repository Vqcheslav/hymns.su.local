<?php

namespace App\Repository;

use App\Entity\Verse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Verse>
 *
 * @method Verse|null find($id, $lockMode = null, $lockVersion = null)
 * @method Verse|null findOneBy(array $criteria, array $orderBy = null)
 * @method Verse[]    findAll()
 * @method Verse[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VerseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Verse::class);
    }

    public function add(Verse $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Verse $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function searchVerses(string $search, int $limit)
    {
        return $this->createQueryBuilder('v')
            ->select('b', 'v', 'h')
            ->join('v.hymn', 'h')
            ->join('h.book', 'b')
            ->andWhere('v.lyrics LIKE :search')
            ->orderBy('h.number', 'ASC')
            ->setMaxResults($limit)
            ->setParameter('search', $search)
            ->getQuery()
            ->getResult();
    }
}

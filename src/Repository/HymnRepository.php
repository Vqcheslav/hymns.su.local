<?php

namespace App\Repository;

use App\Entity\Hymn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Hymn>
 *
 * @method Hymn|null find($id, $lockMode = null, $lockVersion = null)
 * @method Hymn|null findOneBy(array $criteria, array $orderBy = null)
 * @method Hymn[]    findAll()
 * @method Hymn[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HymnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hymn::class);
    }

    public function add(Hymn $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Hymn $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @throws NonUniqueResultException|NoResultException
     */
    public function getMaxHymnNumber(string $bookId): ?int
    {
        return $this
            ->createQueryBuilder('h')
            ->select('MAX(h.number) as max_number')
            ->andWhere('h.book = :book')
            ->setParameter('book', $bookId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getHymnsByBookId(string $bookId, int $startNumber, int $endNumber, int $limit = 500)
    {
        return $this
            ->createQueryBuilder('h')
            ->select('h')
            ->andWhere('h.book = :book')
            ->andWhere('h.number >= :startNumber')
            ->andWhere('h.number <= :endNumber')
            ->orderBy('h.number', 'ASC')
            ->setMaxResults($limit)
            ->setParameter('book', $bookId)
            ->setParameter('startNumber', $startNumber)
            ->setParameter('endNumber', $endNumber)
            ->getQuery()
            ->getResult();
    }

    public function getHymnsByCategory(string $category, int $offset, int $limit = 100)
    {
        return $this
            ->createQueryBuilder('h')
            ->select('h')
            ->andWhere('h.category = :category')
            ->orderBy('h.number', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->setParameter('category', $category)
            ->getQuery()
            ->getResult();
    }

    public function getHymnsWithVerses(string $bookId, int $startNumber, int $endNumber, int $limit = 1000)
    {
        return $this
            ->createQueryBuilder('h')
            ->select('h', 'v')
            ->leftJoin('h.verses', 'v')
            ->andWhere('h.book = :book')
            ->andWhere('h.number >= :startNumber')
            ->andWhere('h.number <= :endNumber')
            ->orderBy('h.number', 'ASC')
            ->setMaxResults($limit)
            ->setParameter('book', $bookId)
            ->setParameter('startNumber', $startNumber)
            ->setParameter('endNumber', $endNumber)
            ->getQuery()
            ->getResult();
    }

    public function getHymnByHymnId(string $hymnId)
    {
        return $this
            ->createQueryBuilder('h')
            ->select('h', 'v')
            ->leftJoin('h.verses', 'v')
            ->andWhere('h.hymnId = :hymn_id')
            ->orderBy('v.position', 'ASC')
            ->addOrderBy('v.isChorus', 'DESC')
            ->setParameter('hymn_id', $hymnId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function searchHymnsByNumber(int $number)
    {
        return $this
            ->createQueryBuilder('h')
            ->select('b', 'h', 'v')
            ->join('h.book', 'b')
            ->leftJoin('h.verses', 'v', Join::WITH, 'v.position = 1')
            ->andWhere('h.number = :number')
            ->orderBy('b.totalSongs', 'DESC')
            ->setParameter('number', $number)
            ->getQuery()
            ->getResult();
    }

    public function searchHymnsByTitle(string $search, int $limit)
    {
        return $this
            ->createQueryBuilder('h')
            ->select('b', 'h', 'v')
            ->join('h.book', 'b')
            ->leftJoin('h.verses', 'v', Join::WITH, 'v.position = 1')
            ->andWhere('h.title LIKE :title')
            ->orderBy('h.number', 'ASC')
            ->setParameter('title', $search)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getHymnCategories()
    {
        return $this
            ->createQueryBuilder('h')
            ->select('h.category as title, COUNT(h.category) as total_songs')
            ->groupBy('h.category')
            ->orderBy('h.category', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getUpdatedHymns(string $afterDateTime, int $limit = 500)
    {
        return $this
            ->createQueryBuilder('h')
            ->select('h.hymnId, h.updatedAt')
            ->andWhere('h.updatedAt >= :afterDateTime')
            ->orderBy('h.updatedAt', 'DESC')
            ->setParameter('afterDateTime', $afterDateTime)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTitleByHymnId(string $hymnId): ?string
    {
        return $this
            ->createQueryBuilder('h')
            ->select('h.title')
            ->andWhere('h.hymnId = :hymn_id')
            ->setParameter('hymn_id', $hymnId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

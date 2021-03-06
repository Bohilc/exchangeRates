<?php

namespace App\Repository;

use App\Entity\RatesArchive;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method RatesArchive|null find($id, $lockMode = null, $lockVersion = null)
 * @method RatesArchive|null findOneBy(array $criteria, array $orderBy = null)
 * @method RatesArchive[]    findAll()
 * @method RatesArchive[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RatesArchiveRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RatesArchive::class);
    }

    // /**
    //  * @return RatesArchive[] Returns an array of RatesArchive objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RatesArchive
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

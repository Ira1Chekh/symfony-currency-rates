<?php

namespace App\Infrastructure\Repository;

use App\Domain\Entity\CryptoRate;
use App\Domain\Repository\CryptoRateRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineCryptoRateRepository extends ServiceEntityRepository implements CryptoRateRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CryptoRate::class);
    }

    public function save(CryptoRate $rate): void
    {
        $this->getEntityManager()->persist($rate);
        $this->getEntityManager()->flush();
    }

    public function findByPairAndTimeRange(
        string $pair,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array {
        return $this->createQueryBuilder('cr')
            ->andWhere('cr.pair = :pair')
            ->andWhere('cr.createdAt BETWEEN :start AND :end')
            ->setParameter('pair', $pair)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('cr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByPairAndDate(string $pair, \DateTimeInterface $date): array
    {
        $startOfDay = $date->setTime(0, 0, 0);
        $endOfDay = $date->setTime(23, 59, 59);

        return $this->findByPairAndTimeRange($pair, $startOfDay, $endOfDay);
    }
}

<?php

namespace App\Infrastructure\Repository;

use App\Domain\Entity\CryptoRate;
use App\Domain\Repository\CryptoRateRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineCryptoRateRepository extends ServiceEntityRepository implements CryptoRateRepositoryInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CryptoRate::class);
        $this->entityManager = $this->getEntityManager();
    }

    public function add(CryptoRate $rate): void
    {
        $this->entityManager->persist($rate);
    }

    public function save(CryptoRate $rate): void
    {
        $this->entityManager->persist($rate);
        $this->entityManager->flush();
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }

    public function beginTransaction(): void
    {
        $this->entityManager->beginTransaction();
    }

    public function commit(): void
    {
        $this->entityManager->commit();
    }

    public function rollback(): void
    {
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }
    }

    /**
     * @throws Exception
     */
    public function bulkUpsert(array $rates): void
    {
        $connection = $this->entityManager->getConnection();

        $sql = "INSERT INTO crypto_rate (pair, price, created_at)
                VALUES (:pair, :price, NOW())
                ON CONFLICT (pair) DO UPDATE
                SET price = EXCLUDED.price,
                    updated_at = NOW()";

        $connection->beginTransaction();

        try {
            foreach ($rates as $rate) {
                $connection->executeStatement($sql, [
                    'pair' => $rate->getPair()->value(),
                    'price' => $rate->getPrice()
                ]);
            }

            $connection->commit();

        } catch (\Throwable $e) {
            $connection->rollback();
            throw $e;
        }
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

<?php

namespace App\Repository;

use App\Entity\Call;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTime;

class CallRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Call::class);
    }

    public function save(Call $entity, bool $flush = false): void {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findIncomingForUser($user): array {
        return $this->createQueryBuilder('c')
            ->where('c.callee = :user')
            ->andWhere('c.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'RINGING')
            ->getQuery()
            ->getResult();
    }

    public function findCallHistory($user, int $limit = 50): array {
        return $this->createQueryBuilder('c')
            ->where('c.caller = :user OR c.callee = :user')
            ->andWhere('c.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', ['CONNECTED', 'ENDED', 'MISSED', 'REJECTED'])
            ->orderBy('c.created_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findActiveCall($user): ?Call {
        return $this->createQueryBuilder('c')
            ->where('(c.caller = :user OR c.callee = :user)')
            ->andWhere('c.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', ['RINGING', 'CONNECTED'])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

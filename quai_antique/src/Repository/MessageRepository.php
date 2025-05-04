<?php

namespace App\Repository;

use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 *
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * @return Message[] Returns an array of unread messages
     */
    public function findUnreadMessages(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.isRead = :val')
            ->setParameter('val', false)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
    
    /**
     * Count unread messages
     */
    public function countUnreadMessages(): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.isRead = :isRead')
            ->setParameter('isRead', false)
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    /**
     * Find messages by status
     */
    public function findByStatus(string $status, int $limit = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->orderBy('m.createdAt', 'DESC');
        
        if ($status !== 'all') {
            $qb->andWhere('m.status = :status')
               ->setParameter('status', $status);
        }
        
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * Search for messages by keyword
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.name LIKE :query')
            ->orWhere('m.email LIKE :query')
            ->orWhere('m.subject LIKE :query')
            ->orWhere('m.content LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Find messages by filter criteria
     */
    public function findByFilters(?string $status = null, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->orderBy('m.createdAt', 'DESC');
            
        if ($status && $status !== 'all') {
            $qb->andWhere('m.status = :status')
               ->setParameter('status', $status);
        }
        
        if ($search) {
            $qb->andWhere('m.name LIKE :search OR m.email LIKE :search OR m.subject LIKE :search OR m.content LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        return $qb->getQuery()->getResult();
    }
}

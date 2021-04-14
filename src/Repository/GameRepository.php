<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Game|null find($id, $lockMode = null, $lockVersion = null)
 * @method Game|null findOneBy(array $criteria, array $orderBy = null)
 * @method Game[]    findAll()
 * @method Game[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }



    public function findEmptyGames()
    {
        return $this->createQueryBuilder('game')
            ->where('game.user2 IS NULL')
            ->orderBy('game.id', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult()
            ;
    }

    public function findVictoiresM()
    {
        return $this->createQueryBuilder('game')
            ->where('game.typeVictoire =:type')
            ->setParameter('type', 'Mercenaire')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findVictoiresP()
    {
        return $this->createQueryBuilder('game')
            ->where('game.typeVictoire =:type')
            ->setParameter('type', 'Points')
            ->getQuery()
            ->getResult()
            ;
    }

    // /**
    //  * @return Game[] Returns an array of Game objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Game
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
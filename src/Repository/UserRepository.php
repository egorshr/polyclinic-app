<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;


class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function createUser(User $user): bool
    {
        try {
            $this->_em->persist($user);
            $this->_em->flush();
            return true;
        } catch (Exception) {
            return false;
        }
    }

    public function getUserByUsername(string $username): ?User
    {
        return $this->findOneBy(['username' => $username]);
    }

    public function getUserById(int $id): ?User
    {
        return $this->find($id);
    }
}


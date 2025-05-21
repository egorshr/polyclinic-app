<?php
// src/Repository/UserRepository.php
namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class UserRepository
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function createUser(User $user): bool
    {
        try {
            $this->em->persist($user);
            $this->em->flush();

            return true;
        } catch (Exception) {
            return false;
        }
    }

    public function getUserByUsername(string $username): ?User
    {
        return $this->em->getRepository(User::class)->findOneBy(['username' => $username]);
    }

    public function getUserById(int $id): ?User
    {
        return $this->em->getRepository(User::class)->find($id);
    }
}


<?php

namespace App\Repository;

use App\Entity\User;

// Убедись, что это твоя АКТУАЛЬНАЯ сущность User для поликлиники
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Exception;

// Оставим, если хочешь ловить Exception в createUser

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Сохраняет пользователя. Этот метод может быть более общим,
     * так как EntityManager уже предоставляет persist и flush.
     * Обычно такой метод не требуется, если только нет доп. логики.
     * В контроллере обычно делают:
     * $this->entityManager->persist($user);
     * $this->entityManager->flush();
     *
     * Если все же хочешь такой метод:
     */
    public function save(User $user, bool $flush = true): void
    {
        $this->getEntityManager()->persist($user);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Твой старый метод createUser, но лучше использовать просто save или EntityManager напрямую.
     * Возвращать bool не очень информативно, лучше пробрасывать исключение, если что-то пошло не так,
     * или ничего не возвращать (void), если успех гарантирован (кроме исключений).
     */
    public function createUser(User $user): bool
    {
        try {
            // _em - это protected свойство, лучше использовать $this->getEntityManager()
            $this->getEntityManager()->persist($user);
            $this->getEntityManager()->flush();
            return true;
        } catch (Exception $e) {
            // Здесь хорошо бы залогировать ошибку $e->getMessage()
            return false;
        }
    }

    /**
     * Используется для автоматического обновления (перехеширования) пароля пользователя со временем.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        // Предполагается, что у твоей сущности User есть метод setPasswordHash() или setPassword()
        // который принимает хешированный пароль.
        // Адаптируй имя метода, если оно другое в твоей сущности User.
        // Например, в User, который мы делали: $user->setPasswordHash($newHashedPassword);
        $user->setPasswordHash($newHashedPassword);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    // Методы getUserByUsername и getUserById уже предоставляются базовым
    // ServiceEntityRepository через findOneBy(['username' => $username]) и find($id)
    // Но если ты хочешь оставить их для явности, то можно:

    public function findOneByUsername(string $username): ?User
    {
        return $this->findOneBy(['username' => $username]);
    }

    // Метод find($id) уже есть, так что getUserById(int $id) является его дубликатом.
    // public function getUserById(int $id): ?User
    // {
    //     return $this->find($id);
    // }

    // Примеры других полезных методов, которые могут понадобиться:
    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByRole(string $role): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.role = :role')
    //            ->setParameter('role', $role)
    //            ->orderBy('u.username', 'ASC')
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneByEmail(string $email): ?User
    //    {
    //        return $this->findOneBy(['email' => $email]);
    //    }
}
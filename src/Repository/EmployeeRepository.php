<?php

namespace App\Repository;

use App\Entity\Employee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Employee>
 */
class EmployeeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Employee::class);
    }

    /**
     * @return Employee[] Returns an array of active Employee objects
     */
    public function findAllActive(): array
    {
        // Возвращаем всех сотрудников. Если нужна фильтрация по активности,
        // добавьте соответствующую логику здесь (например, по полю isActive в Employee или User).
        return $this->findAll();

        /*
        // Пример фильтрации, если у Employee есть поле 'isActive'
        return $this->createQueryBuilder('e')
            ->andWhere('e.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('e.lastName', 'ASC')
            ->addOrderBy('e.firstName', 'ASC')
            ->getQuery()
            ->getResult();
        */

        /*
        // Пример фильтрации, если у связанного User есть поле 'isActive'
        return $this->createQueryBuilder('e')
            ->innerJoin('e.user', 'u')
            ->andWhere('u.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('e.lastName', 'ASC')
            ->addOrderBy('e.firstName', 'ASC')
            ->getQuery()
            ->getResult();
        */
    }
}
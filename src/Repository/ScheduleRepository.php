<?php

namespace App\Repository;

use App\Entity\Employee;
use App\Entity\Schedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Schedule>
 */
class ScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Schedule::class);
    }

    //    /**
    //     * @return Schedule[] Returns an array of Schedule objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Schedule
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findFutureAvailableDatesByEmployee(Employee $employee): array
    {
        $today = (new \DateTimeImmutable())->setTime(0, 0, 0);

        $result = $this->createQueryBuilder('s')
            ->select('s.date') // Выбираем только поле даты
            ->distinct(true)    // Только уникальные даты
            ->andWhere('s.employee = :employee')
            ->andWhere('s.date >= :today') // Только сегодня и будущие даты
            ->setParameter('employee', $employee)
            ->setParameter('today', $today)
            ->orderBy('s.date', 'ASC')
            ->getQuery()
            ->getResult(); // Получаем массив массивов, например [['date' => obj], ['date' => obj]]

        // Преобразуем результат в простой массив объектов DateTimeImmutable
        return array_map(fn($row) => $row['date'], $result);
    }
}

<?php

namespace App\Repository;

use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Filesystem\Filesystem;
use Exception;


class BookingRepository extends ServiceEntityRepository
{
    private KernelInterface $kernel;
    private Filesystem $filesystem;
    private const CSV_DATA_SUBDIR = 'app_data/bookings_csv';

    public function __construct(ManagerRegistry $registry, KernelInterface $kernel, Filesystem $filesystem)
    {
        parent::__construct($registry, Booking::class);
        $this->kernel = $kernel;
        $this->filesystem = $filesystem;
    }

    private function getCsvFilePathForUser(int $userId): string
    {
        $baseDir = $this->kernel->getProjectDir() . '/var/' . self::CSV_DATA_SUBDIR;
        return $baseDir . '/bookings_' . $userId . '.csv';
    }

    public function saveBooking(Booking $booking): void
    {
        $this->saveToDatabase($booking);
    }

    private function saveToDatabase(Booking $booking): void
    {

        try {
            $entityManager = $this->getEntityManager();
            $entityManager->persist($booking);
            $entityManager->flush();
        } catch (Exception $e) {
            throw new Exception("Ошибка при сохранении в базу данных: " . $e->getMessage());
        }
    }


    public function getAllBookingsFromDb(array $filters = [], ?int $userId = null): array
    {
        try {
            $qb = $this->createQueryBuilder('b');

            if ($userId !== null) {
                $qb->andWhere('b.userId = :userId')
                    ->setParameter('userId', $userId);
            }

            if (!empty($filters['name'])) {
                $qb->andWhere('LOWER(b.name) LIKE LOWER(:name)')
                    ->setParameter('name', '%' . $filters['name'] . '%');
            }
            if (!empty($filters['service'])) {
                $qb->andWhere('b.service = :service')
                    ->setParameter('service', $filters['service']);
            }
            if (!empty($filters['photographer'])) {
                $qb->andWhere('b.photographer = :photographer')
                    ->setParameter('photographer', $filters['photographer']);
            }
            if (!empty($filters['date_from'])) {
                $qb->andWhere('b.date >= :date_from')
                    ->setParameter('date_from', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $qb->andWhere('b.date <= :date_to')
                    ->setParameter('date_to', $filters['date_to']);
            }
            $qb->orderBy('b.date', 'DESC')
                ->addOrderBy('b.id', 'DESC');


            return $qb->getQuery()->getArrayResult();

        } catch (Exception $e) {
            throw new Exception("Ошибка при получении данных из базы: " . $e->getMessage());
        }
    }

}
<?php

namespace App\Repository;

use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    private const CSV_FILE_PATH = __DIR__ . '/../../var/csv/bookings_%d.csv';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function save(Booking $booking, bool $flush = false): void
    {
        $this->getEntityManager()->persist($booking);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Booking $booking, bool $flush = false): void
    {
        $this->getEntityManager()->remove($booking);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getAllBookings(array $filters = [], ?int $userId = null): array
    {
        $qb = $this->createQueryBuilder('b');

        if ($userId !== null) {
            $qb->andWhere('b.userId = :userId')
                ->setParameter('userId', $userId);
        }

        if (!empty($filters['name'])) {
            $qb->andWhere('b.name LIKE :name')
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

        $qb->orderBy('b.date', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function saveToCsv(Booking $booking): void
    {
        $filePath = sprintf(self::CSV_FILE_PATH, $booking->getUserId());
        $isNewFile = !file_exists($filePath);

        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $file = fopen($filePath, 'a');

        if ($isNewFile) {
            fputcsv($file, ['name', 'service', 'photographer', 'date', 'user_id']);
        }

        fputcsv($file, [
            $booking->getName(),
            $booking->getService(),
            $booking->getPhotographer(),
            $booking->getDate()->format('Y-m-d'), // форматируем дату
            $booking->getUserId()
        ]);

        fclose($file);
    }

    public function getAllBookingsFromCsv(array $filters = [], ?int $userId = null): array
    {
        if ($userId === null) {
            return [];
        }

        $filePath = sprintf(self::CSV_FILE_PATH, $userId);
        if (!file_exists($filePath)) {
            return [];
        }

        $file = fopen($filePath, 'r');
        fgetcsv($file); // пропускаем заголовок

        $bookings = [];
        while (($data = fgetcsv($file)) !== false) {
            if (count($data) >= 5) {
                $booking = [
                    'name' => $data[0],
                    'service' => $data[1],
                    'photographer' => $data[2],
                    'date' => $data[3],
                    'user_id' => (int)($data[4] ?? $userId),
                ];

                $match = true;

                if (!empty($filters['name']) &&
                    stripos($booking['name'], $filters['name']) === false) {
                    $match = false;
                }

                if (!empty($filters['service']) &&
                    $booking['service'] !== $filters['service']) {
                    $match = false;
                }

                if (!empty($filters['photographer']) &&
                    $booking['photographer'] !== $filters['photographer']) {
                    $match = false;
                }

                if (!empty($filters['date_from']) &&
                    $booking['date'] < $filters['date_from']) {
                    $match = false;
                }

                if (!empty($filters['date_to']) &&
                    $booking['date'] > $filters['date_to']) {
                    $match = false;
                }

                if ($match) {
                    $bookings[] = $booking;
                }
            }
        }

        fclose($file);
        return array_reverse($bookings);
    }
}

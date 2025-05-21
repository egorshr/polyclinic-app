<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

class BookingRepository extends ServiceEntityRepository
{
    private const CSV_FILE_PATH = __DIR__ . '/../../data/bookings_%d.csv';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function getAllBookingsFromDb(): array
    {
        return $this->createQueryBuilder('b')
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function saveBooking(Booking $booking, string $storage = 'csv'): void
    {
        if ($storage === 'db') {
            $this->saveToDatabase($booking);
        } else {
            $this->saveToCsv($booking);
        }
    }

    private function saveToDatabase(Booking $booking): void
    {
        $em = $this->getEntityManager();

        try {
            $em->persist($booking);
            $em->flush();
        } catch (Exception $e) {
            throw new Exception('Ошибка при сохранении в базу данных: ' . $e->getMessage());
        }
    }

    private function saveToCsv(Booking $booking): void
    {
        $userId = $booking->getUserId();
        $filePath = sprintf(self::CSV_FILE_PATH, $userId);
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
            $booking->getDate()->format('Y-m-d'),
            $userId,
        ]);

        fclose($file);
    }

    public function findByFilters(array $filters = [], ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('b')
            ->orderBy('b.date', 'DESC');

        if ($user !== null) {
            $qb->andWhere('b.userId = :userId')
                ->setParameter('userId', $user->getId());
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

        return $qb->getQuery()->getResult();
    }

    /**
     * Получение всех бронирований из CSV
     */
    public function getAllBookingsFromCsv(array $filters = [], ?User $user = null): array
    {
        if ($user === null) {
            return [];
        }

        $userId = $user->getId();
        $filePath = sprintf(self::CSV_FILE_PATH, $userId);

        if (!file_exists($filePath)) {
            return [];
        }

        $file = fopen($filePath, 'r');
        fgetcsv($file); // пропустить заголовок

        $bookings = [];

        while (($data = fgetcsv($file)) !== false) {
            if (count($data) < 5) {
                continue;
            }

            $booking = [
                'name' => $data[0],
                'service' => $data[1],
                'photographer' => $data[2],
                'date' => $data[3],
                'user_id' => (int)$data[4],
            ];

            if (!$this->matchesFilters($booking, $filters)) {
                continue;
            }

            $bookings[] = $booking;
        }

        fclose($file);

        return array_reverse($bookings);
    }

    private function matchesFilters(array $booking, array $filters): bool
    {
        if (!empty($filters['name']) && stripos($booking['name'], $filters['name']) === false) {
            return false;
        }

        if (!empty($filters['service']) && $booking['service'] !== $filters['service']) {
            return false;
        }

        if (!empty($filters['photographer']) && $booking['photographer'] !== $filters['photographer']) {
            return false;
        }

        if (!empty($filters['date_from']) && $booking['date'] < $filters['date_from']) {
            return false;
        }

        if (!empty($filters['date_to']) && $booking['date'] > $filters['date_to']) {
            return false;
        }

        return true;
    }
}

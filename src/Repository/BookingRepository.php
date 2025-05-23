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

        try {
            $entityManager = $this->getEntityManager();
            $entityManager->persist($booking);
            $entityManager->flush();
        } catch (Exception $e) {
            throw new Exception("Ошибка при сохранении в базу данных: " . $e->getMessage());
        }
    }

    private function saveToCsv(Booking $booking): void
    {
        $filePath = $this->getCsvFilePathForUser($booking->getUserId());
        $dir = dirname($filePath);

        if (!$this->filesystem->exists($dir)) {
            $this->filesystem->mkdir($dir, 0775);
        }

        $isNewFile = !$this->filesystem->exists($filePath);
        $fileHandle = @fopen($filePath, 'a');

        if ($fileHandle === false) {
            throw new Exception("Не удалось открыть CSV файл для записи: " . $filePath);
        }

        if ($isNewFile) {
            fputcsv($fileHandle, ['name', 'service', 'photographer', 'date', 'user_id']);
        }

        fputcsv($fileHandle, [
            $booking->getName(),
            $booking->getService(),
            $booking->getPhotographer(),
            $booking->getDate(),
            $booking->getUserId()
        ]);

        fclose($fileHandle);
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

    public function getAllBookingsFromCsv(array $filters = [], ?int $userId = null): array
    {
        if ($userId === null) {
            return [];
        }

        $filePath = $this->getCsvFilePathForUser($userId);
        if (!$this->filesystem->exists($filePath)) {
            return [];
        }

        $fileHandle = @fopen($filePath, 'r');
        if ($fileHandle === false) {
            throw new Exception("Не удалось открыть CSV файл для чтения: " . $filePath);
        }

        fgetcsv($fileHandle);

        $bookings = [];
        while (($data = fgetcsv($fileHandle)) !== false) {
            if (count($data) >= 5) {
                $bookingData = [
                    'name' => $data[0],
                    'service' => $data[1],
                    'photographer' => $data[2],
                    'date' => $data[3],
                    'user_id' => (int)$data[4]
                ];
                if ($bookingData['user_id'] !== $userId) {
                    continue;
                }

                $match = true;

                if (!empty($filters['name']) && stripos($bookingData['name'], $filters['name']) === false) {
                    $match = false;
                }
                if ($match && !empty($filters['service']) && $bookingData['service'] !== $filters['service']) {
                    $match = false;
                }
                if ($match && !empty($filters['photographer']) && $bookingData['photographer'] !== $filters['photographer']) {
                    $match = false;
                }
                if ($match && !empty($filters['date_from']) && $bookingData['date'] < $filters['date_from']) {
                    $match = false;
                }
                if ($match && !empty($filters['date_to']) && $bookingData['date'] > $filters['date_to']) {
                    $match = false;
                }

                if ($match) {
                    $bookings[] = $bookingData;
                }
            }
        }
        fclose($fileHandle);
        return array_reverse($bookings);
    }
}
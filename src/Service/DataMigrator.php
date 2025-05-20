<?php

namespace App\Service;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class DataMigrator
{
    private EntityManagerInterface $entityManager;
    private string $csvBasePath;
    private Filesystem $filesystem;
    private BookingRepository $bookingRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        KernelInterface $kernel,
        BookingRepository $bookingRepository
    ) {
        $this->entityManager = $entityManager;
        $this->csvBasePath = $kernel->getProjectDir() . '/var/csv';
        $this->filesystem = new Filesystem();
        $this->bookingRepository = $bookingRepository;
    }

    public function migrateFromCsvToDb(int $userId): int
    {
        $filePath = sprintf('%s/bookings_%d.csv', $this->csvBasePath, $userId);

        if (!$this->filesystem->exists($filePath)) {
            return 0;
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new RuntimeException('Не удалось открыть CSV-файл: ' . $filePath);
        }

        $migrated = 0;

        try {
            // Пропустить заголовок
            fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 5) {
                    // Строка с недостаточным количеством колонок — пропускаем
                    continue;
                }

                try {
                    $date = DateTime::createFromFormat('Y-m-d', trim($row[3]));
                    if (!$date) {
                        // Некорректная дата — пропускаем строку
                        continue;
                    }

                    $booking = new Booking();
                    $booking
                        ->setName(trim($row[0]))
                        ->setService(trim($row[1]))
                        ->setPhotographer(trim($row[2]))
                        ->setDate($date)
                        ->setUserId((int)($row[4] ?? $userId));

                    $this->entityManager->persist($booking);
                    $migrated++;
                } catch (Exception) {
                    // Можно логировать ошибку $e->getMessage()
                    continue;
                }
            }
        } finally {
            fclose($handle);
        }

        $this->entityManager->flush();

        // Очистить CSV-файл, оставив только заголовок
        if ($migrated > 0) {
            $cleanHandle = fopen($filePath, 'w');
            if ($cleanHandle) {
                fputcsv($cleanHandle, ['name', 'service', 'photographer', 'date', 'user_id']);
                fclose($cleanHandle);
            }
        }

        return $migrated;
    }

    public function migrateFromDbToCsv(int $userId): int
    {
        // Получаем все бронирования пользователя из БД
        $bookings = $this->bookingRepository->getAllBookings([], $userId);

        if (empty($bookings)) {
            return 0;
        }

        $filePath = sprintf('%s/bookings_%d.csv', $this->csvBasePath, $userId);

        // Подготавливаем директорию, если она не существует
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            $this->filesystem->mkdir($dir, 0777);
        }

        // Открываем файл для записи или добавления
        $fileExists = $this->filesystem->exists($filePath);
        $handle = fopen($filePath, $fileExists ? 'a' : 'w');

        if (!$handle) {
            throw new RuntimeException('Не удалось открыть CSV-файл для записи: ' . $filePath);
        }

        $migrated = 0;

        try {
            // Если файл новый, записываем заголовок
            if (!$fileExists) {
                fputcsv($handle, ['name', 'service', 'photographer', 'date', 'user_id']);
            }

            // Добавляем записи из БД в CSV
            foreach ($bookings as $booking) {
                fputcsv($handle, [
                    $booking->getName(),
                    $booking->getService(),
                    $booking->getPhotographer(),
                    $booking->getDate()->format('Y-m-d'),
                    $booking->getUserId(),
                ]);
                $migrated++;
            }
        } finally {
            fclose($handle);
        }

        // Не удаляем записи из БД, только если специально не запрошено

        return $migrated;
    }
}
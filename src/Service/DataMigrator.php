<?php

namespace App\Service;

use App\Entity\Booking;
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

    public function __construct(EntityManagerInterface $entityManager, KernelInterface $kernel)
    {
        $this->entityManager = $entityManager;
        $this->csvBasePath = $kernel->getProjectDir() . '/var/csv';
        $this->filesystem = new Filesystem();
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
        $cleanHandle = fopen($filePath, 'w');
        if ($cleanHandle) {
            fputcsv($cleanHandle, ['name', 'service', 'photographer', 'date', 'user_id']);
            fclose($cleanHandle);
        }

        return $migrated;
    }
}
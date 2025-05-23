<?php

namespace App\Service;

use App\Entity\Booking;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Filesystem\Filesystem;
use Exception;

class DataMigrator
{
    private EntityManagerInterface $entityManager;
    private KernelInterface $kernel;
    private Filesystem $filesystem;
    private const CSV_DATA_SUBDIR = 'app_data/bookings_csv';

    public function __construct(
        EntityManagerInterface $entityManager,
        KernelInterface        $kernel,
        Filesystem             $filesystem
    )
    {
        $this->entityManager = $entityManager;
        $this->kernel = $kernel;
        $this->filesystem = $filesystem;
    }

    private function getCsvFilePathForUser(int $userId): string
    {
        $baseDir = $this->kernel->getProjectDir() . '/var/' . self::CSV_DATA_SUBDIR;
        return $baseDir . '/bookings_' . $userId . '.csv';
    }

    public function migrateFromCsvToDb(int $userId): int
    {
        $filePath = $this->getCsvFilePathForUser($userId);

        if (!$this->filesystem->exists($filePath)) {
            return 0;
        }

        $fileHandle = @fopen($filePath, 'r');
        if (!$fileHandle) {
            throw new Exception('Не удалось открыть файл CSV: ' . $filePath);
        }

        fgetcsv($fileHandle);

        $migrated = 0;
        $batchSize = 50;

        while (($data = fgetcsv($fileHandle)) !== false) {

            if (count($data) >= 4) {
                $name = $data[0];
                $service = $data[1];
                $photographer = $data[2];
                $date = $data[3];
                $csvUserId = isset($data[4]) ? (int)$data[4] : $userId;


                $bookingUserIdToSave = $csvUserId;

                $booking = new Booking(
                    $name,
                    $service,
                    $photographer,
                    $date,
                    $bookingUserIdToSave
                );

                $this->entityManager->persist($booking);
                $migrated++;

                if ($migrated % $batchSize === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
            }
        }
        fclose($fileHandle);


        if ($migrated % $batchSize !== 0 && $migrated > 0) {
            $this->entityManager->flush();
            $this->entityManager->clear();
        }


        $clearFileHandle = @fopen($filePath, 'w');
        if ($clearFileHandle) {
            fputcsv($clearFileHandle, ['name', 'service', 'photographer', 'date', 'user_id']);
            fclose($clearFileHandle);
        }
        return $migrated;
    }
}
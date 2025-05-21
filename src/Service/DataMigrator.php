<?php
// src/Service/DataMigrator.php
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
    private const CSV_DATA_SUBDIR = 'app_data/bookings_csv'; // Должно совпадать с BookingRepository

    public function __construct(
        EntityManagerInterface $entityManager,
        KernelInterface $kernel,
        Filesystem $filesystem
    ) {
        $this->entityManager = $entityManager;
        $this->kernel = $kernel;
        $this->filesystem = $filesystem;
    }

    private function getCsvFilePathForUser(int $userId): string
    {
        // Эта логика должна быть идентична той, что используется в BookingRepository
        // для генерации пути к CSV файлу пользователя.
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

        fgetcsv($fileHandle); // Пропускаем строку заголовка

        $migrated = 0;
        $batchSize = 50; // Для периодического flush

        while (($data = fgetcsv($fileHandle)) !== false) {
            // Оригинальная проверка count($data) >= 4.
            // BookingRepository пишет 5 колонок: name, service, photographer, date, user_id
            // Если мы строго следуем оригиналу DataMigrator:
            if (count($data) >= 4) {
                $name = $data[0];
                $service = $data[1];
                $photographer = $data[2];
                $date = $data[3];
                // user_id из CSV (data[4]) или переданный $userId, если data[4] отсутствует
                $csvUserId = isset($data[4]) ? (int)$data[4] : $userId;

                // Важно: убедитесь, что $csvUserId корректен, или всегда используйте $userId
                // если данные в CSV могут быть неконсистентны по user_id.
                // Для строгого сохранения логики $_POST[4] ?? $userId:
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
                    $this->entityManager->clear(); // Освобождаем память
                }
            }
        }
        fclose($fileHandle);

        // Flush оставшихся записей
        if ($migrated % $batchSize !== 0 && $migrated > 0) {
            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        // Очищаем CSV файл, оставляя только заголовок
        // (Эта часть оригинальной логики означает, что данные удаляются из CSV после миграции)
        $clearFileHandle = @fopen($filePath, 'w');
        if ($clearFileHandle) {
            fputcsv($clearFileHandle, ['name', 'service', 'photographer', 'date', 'user_id']);
            fclose($clearFileHandle);
        } else {
            // Логирование ошибки открытия файла для очистки, если это необходимо
            // throw new Exception('Не удалось открыть CSV файл для очистки: ' . $filePath);
            // В оригинальном коде ошибка не обрабатывается критически.
        }

        return $migrated;
    }
}
<?php
namespace App\Service;

use App\Entity\Booking;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class DataMigrator
{
    private const CSV_FILE_PATTERN = __DIR__ . '/../data/bookings_%d.csv';

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function migrateFromCsvToDb(int $userId): int
    {
        $filePath = sprintf(self::CSV_FILE_PATTERN, $userId);
        if (!file_exists($filePath)) {
            return 0;
        }

        $file = fopen($filePath, 'r');
        if (!$file) {
            throw new Exception('Не удалось открыть файл CSV');
        }

        // Пропускаем заголовок
        fgetcsv($file);

        $migrated = 0;

        while (($data = fgetcsv($file)) !== false) {
            if (count($data) >= 4) {
                $bookingUserId = isset($data[4]) ? (int)$data[4] : $userId;

                // Создаем объект Booking с параметрами из CSV
                $booking = new Booking(
                    $data[0],            // name
                    $data[1],            // service
                    $data[2],            // photographer
                    $data[3],            // date (строка 'YYYY-MM-DD')
                    $bookingUserId
                );

                $this->em->persist($booking);
                $migrated++;
            }
        }

        fclose($file);

        $this->em->flush();

        // Очищаем CSV, оставляя только заголовок
        $file = fopen($filePath, 'w');
        fputcsv($file, ['name', 'service', 'photographer', 'date', 'user_id']);
        fclose($file);

        return $migrated;
    }
}

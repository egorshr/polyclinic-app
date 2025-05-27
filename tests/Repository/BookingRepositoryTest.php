<?php


namespace App\Tests\Repository;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class BookingRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private BookingRepository $bookingRepository;
    private Filesystem $filesystem;
    private int $testUserId = 999;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->entityManager = $container->get('doctrine.orm.entity_manager');
        $this->filesystem = new Filesystem();
        $kernel = $container->get(KernelInterface::class);


        $testCsvDir = $kernel->getProjectDir() . '/var/test_csv_data_' . uniqid();
        $this->filesystem->mkdir($testCsvDir);


        $mockKernel = $this->createMock(KernelInterface::class);
        $mockKernel->method('getProjectDir')->willReturn($kernel->getProjectDir()); // Keep original project dir for other things

        $appDataSubdir = 'app_data/bookings_csv';
        $testCsvDir = $kernel->getProjectDir() . '/var/test_csv_data/' . $appDataSubdir;
        $this->filesystem->mkdir($testCsvDir, 0775);


        $customKernelMock = $this->createMock(KernelInterface::class);
        $customKernelMock->method('getProjectDir')->willReturn($kernel->getProjectDir());

        $this->bookingRepository = $container->get(BookingRepository::class);
        $this->cleanupTestUserCsv();
    }

    private function getTestCsvFilePath(): string
    {

        $kernel = static::getContainer()->get(KernelInterface::class);
        $baseDir = $kernel->getProjectDir() . '/var/app_data/bookings_csv';
        return $baseDir . '/bookings_' . $this->testUserId . '.csv';
    }

    private function cleanupTestUserCsv(): void
    {
        $filePath = $this->getTestCsvFilePath();
        if ($this->filesystem->exists($filePath)) {
            $this->filesystem->remove($filePath);
        }
        $dir = dirname($filePath);
        if ($this->filesystem->exists($dir) && count(scandir($dir)) == 2) {

        }
    }

    public function testSaveBookingToCsv(): void
    {
        $booking = new Booking('CSV Test', 'Портретная съёмка', 'Анна Иванова', '2025-01-01', $this->testUserId);
        $this->bookingRepository->saveBooking($booking, 'csv');

        $csvFilePath = $this->getTestCsvFilePath();
        $this->assertFileExists($csvFilePath);

        $csvContents = file_get_contents($csvFilePath);
        $this->assertStringContainsString('name,service,photographer,date,user_id', $csvContents); // Header should be fine


        $expectedDataString = sprintf(
            '"%s","%s","%s",%s,%d',
            'CSV Test',
            'Портретная съёмка',
            'Анна Иванова',
            '2025-01-01',
            $this->testUserId
        );
        $this->assertStringContainsString($expectedDataString, $csvContents);
    }


    public function testGetAllBookingsFromCsv(): void
    {
        $booking1 = new Booking('CSV Read Test 1', 'Семейная фотосессия', 'Игорь Петров', '2025-02-01', $this->testUserId);
        $booking2 = new Booking('CSV Read Test 2', 'Портретная съёмка', 'Анна Иванова', '2025-02-05', $this->testUserId);
        $bookingOtherUser = new Booking('Other User', 'Портретная съёмка', 'Анна Иванова', '2025-02-06', $this->testUserId + 1);

        $this->bookingRepository->saveBooking($booking1, 'csv');
        $this->bookingRepository->saveBooking($booking2, 'csv');
        $otherUserFilePath = str_replace(
            'bookings_' . $this->testUserId . '.csv',
            'bookings_' . ($this->testUserId + 1) . '.csv',
            $this->getTestCsvFilePath()
        );
        $this->filesystem->appendToFile($otherUserFilePath, "name,service,photographer,date,user_id\n");
        $this->filesystem->appendToFile($otherUserFilePath, implode(',', [
                $bookingOtherUser->getName(), $bookingOtherUser->getService(), $bookingOtherUser->getPhotographer(), $bookingOtherUser->getDate(), $bookingOtherUser->getUserId()
            ]) . "\n");


        $bookings = $this->bookingRepository->getAllBookingsFromCsv([], $this->testUserId);
        $this->assertCount(2, $bookings);

        $this->assertEquals('CSV Read Test 2', $bookings[0]['name']);
        $this->assertEquals('CSV Read Test 1', $bookings[1]['name']);


        $filteredBookings = $this->bookingRepository->getAllBookingsFromCsv(['name' => 'Test 1'], $this->testUserId);
        $this->assertCount(1, $filteredBookings);
        $this->assertEquals('CSV Read Test 1', $filteredBookings[0]['name']);
    }

    public function testSaveAndRetrieveBookingFromDb(): void
    {

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        try {
            $schemaTool->createSchema($metadata);
        } catch (ToolsException) {
        }


        $booking = new Booking('DB Test', 'Творческая съёмка', 'Екатерина Смирнова', '2025-03-01', $this->testUserId);
        $this->bookingRepository->saveBooking($booking, 'db');


        $this->entityManager->clear();

        $retrievedBookings = $this->bookingRepository->getAllBookingsFromDb(['name' => 'DB Test'], $this->testUserId);
        $this->assertCount(1, $retrievedBookings);
        $this->assertEquals('DB Test', $retrievedBookings[0]['name']);
        $this->assertEquals($this->testUserId, $retrievedBookings[0]['userId']);


        $this->entityManager->remove($this->entityManager->find(Booking::class, $retrievedBookings[0]['id']));
        $this->entityManager->flush();
    }


    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanupTestUserCsv();


        $kernel = static::getContainer()->get(KernelInterface::class);
        $baseTestCsvDir = $kernel->getProjectDir() . '/var/test_csv_data';
        if ($this->filesystem->exists($baseTestCsvDir)) {

        }

        if (isset($this->entityManager) && $this->entityManager->isOpen()) {
            $this->entityManager->close();
        }

    }
}
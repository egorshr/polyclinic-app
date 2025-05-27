<?php


namespace App\Tests\Entity;

use App\Entity\Booking;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class BookingTest extends TestCase
{
    public function testBookingCreationAndGetters(): void
    {
        $name = 'Test Customer';
        $service = 'Портретная съёмка';
        $photographer = 'Анна Иванова';
        $date = '2024-12-25';
        $userId = 123;

        $booking = new Booking($name, $service, $photographer, $date, $userId);

        $this->assertNull($booking->getId());
        $this->assertSame($name, $booking->getName());
        $this->assertSame($service, $booking->getService());
        $this->assertSame($photographer, $booking->getPhotographer());
        $this->assertSame($date, $booking->getDate());
        $this->assertSame($userId, $booking->getUserId());
        $this->assertInstanceOf(DateTimeImmutable::class, $booking->getCreatedAt());

        $now = new DateTimeImmutable();
        $this->assertLessThan(5, $now->getTimestamp() - $booking->getCreatedAt()->getTimestamp());
    }
}
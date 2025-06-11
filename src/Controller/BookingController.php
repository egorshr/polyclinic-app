<?php
// src/Controller/Api/BookingController.php

namespace App\Controller;

use App\Entity\Employee;
use App\Repository\ScheduleRepository;
use App\Repository\VisitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use DateTimeImmutable;

class BookingController extends AbstractController
{
    #[Route('/api/available-slots/{id}/{date}', name: 'api_available_slots', methods: ['GET'])]
    public function getAvailableSlots(
        Employee $employee, // Symfony автоматически найдет врача по {id}
        string $date,       // Дата придет в виде строки, например '2024-05-21'
        ScheduleRepository $scheduleRepository,
        VisitRepository $visitRepository
    ): JsonResponse {
        $selectedDate = new DateTimeImmutable($date);
        $availableSlots = [];

        // 1. Найти рабочее расписание врача на выбранный день
        $schedule = $scheduleRepository->findOneBy([
            'employee' => $employee,
            'date' => $selectedDate
        ]);

        if (!$schedule) {
            // Если расписания на этот день нет, возвращаем пустой массив
            return $this->json(['slots' => []]);
        }

        // 2. Получить длительность приема у этого врача
        $durationOfVisit = $employee->getDurationOfVisit();
        if (!$durationOfVisit) {
            // Если у врача не указана длительность, используем значение по умолчанию (например, 30 минут)
            $interval = new \DateInterval('PT30M');
        } else {
            // Преобразуем 'H:i:s' в DateInterval
            $interval = new \DateInterval('PT' . $durationOfVisit->format('H') . 'H' . $durationOfVisit->format('i') . 'M');
        }

        // 3. Найти все уже существующие записи (визиты) к этому врачу на этот день
        $existingVisits = $visitRepository->findBy([
            'employee' => $employee,
            'visitDate' => $selectedDate
        ]);

        $bookedSlots = [];
        foreach ($existingVisits as $visit) {
            // Сохраняем время начала уже забронированных визитов
            $bookedSlots[] = $visit->getVisitTime()->format('H:i');
        }

        // 4. Сгенерировать все возможные слоты и отфильтровать занятые
        $currentTime = $schedule->getTimeFrom();
        $endTime = $schedule->getTimeTo();

        while ($currentTime < $endTime) {
            $slotTime = $currentTime->format('H:i');

            // Если этот слот НЕ забронирован, добавляем его в список доступных
            if (!in_array($slotTime, $bookedSlots)) {
                $availableSlots[] = $slotTime;
            }

            // Переходим к следующему слоту
            $currentTime = $currentTime->add($interval);
        }

        return $this->json(['slots' => $availableSlots]);
    }
}
<?php
// src/Service/ScheduleService.php

namespace App\Service;

use App\Entity\Employee;
use App\Repository\ScheduleRepository;
use App\Repository\VisitRepository;
use DateInterval;
use DateTimeImmutable;

class ScheduleService
{
    private ScheduleRepository $scheduleRepository;
    private VisitRepository $visitRepository;

    public function __construct(ScheduleRepository $scheduleRepository, VisitRepository $visitRepository)
    {
        $this->scheduleRepository = $scheduleRepository;
        $this->visitRepository = $visitRepository;
    }


    public function getAvailableSlots(Employee $employee, DateTimeImmutable $date): array
    {
        // 1. Находим расписание сотрудника на этот день
        $schedule = $this->scheduleRepository->findOneBy(['employee' => $employee, 'date' => $date]);

        // Если расписания нет или у врача не указана длительность приема, то слотов нет
        if (!$schedule || !$employee->getDurationOfVisit()) {
            return [];
        }

        // 2. Получаем все существующие записи (визиты) на этот день
        $existingVisits = $this->visitRepository->findVisitsByEmployeeAndDate($employee, $date);
        $bookedTimes = [];
        foreach ($existingVisits as $visit) {
            // Сохраняем время начала каждого визита в формате 'H:i'
            $bookedTimes[] = $visit->getDateAndTime()->format('H:i');
        }

        // 3. Генерируем все возможные слоты и фильтруем их
        $availableSlots = [];
        $startTime = $schedule->getTimeFrom();
        $endTime = $schedule->getTimeTo();
        $durationMinutes = (int) $employee->getDurationOfVisit()->format('i');
        $duration = new DateInterval('PT' . $durationMinutes . 'M'); // Создаем объект интервала ОДИН раз

        $currentSlotTime = $startTime;
        while ($currentSlotTime < $endTime) {
            if (!in_array($currentSlotTime->format('H:i'), $bookedTimes)) {
                $availableSlots[] = $currentSlotTime;
            }

            // Переходим к следующему слоту
            $currentSlotTime = $currentSlotTime->add($duration); // <-- ПРАВИЛЬНО. Просто используем уже созданный объект.
        }

        return $availableSlots;
    }
}
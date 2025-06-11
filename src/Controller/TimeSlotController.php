<?php
// src/Controller/Api/TimeSlotController.php

namespace App\Controller;

use App\Entity\Employee;
use App\Repository\ScheduleRepository;
use App\Service\ScheduleService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use DateTimeImmutable;
use Symfony\Component\Routing\Attribute\Route;

class TimeSlotController extends AbstractController
{
    #[Route('/api/employees/{id}/available-slots', name: 'api_get_available_slots', methods: ['GET'])]
    public function getAvailableSlots(Employee $employee, Request $request, ScheduleService $scheduleService): JsonResponse
    {
        $dateString = $request->query->get('date');
        if (!$dateString) {
            return new JsonResponse(['error' => 'Date parameter is required'], 400);
        }

        try {
            $date = new DateTimeImmutable($dateString);
        } catch (Exception) {
            return new JsonResponse(['error' => 'Invalid date format'], 400);
        }

        // Получаем слоты через наш сервис
        $slots = $scheduleService->getAvailableSlots($employee, $date);

        // Форматируем для ответа в JSON
        $formattedSlots = [];
        foreach ($slots as $slot) {
            $formattedSlots[] = $slot->format('H:i');
        }

        return new JsonResponse($formattedSlots);
    }

    #[Route('/api/employees/{id}/available-dates', name: 'api_get_available_dates', methods: ['GET'])]
    public function getAvailableDates(Employee $employee, ScheduleRepository $scheduleRepository): JsonResponse
    {
        // Находим все будущие даты, на которые у врача есть расписание
        $availableDates = $scheduleRepository->findFutureAvailableDatesByEmployee($employee);

        // Форматируем для ответа в JSON (Y-m-d)
        $formattedDates = [];
        foreach ($availableDates as $date) {
            $formattedDates[] = $date->format('Y-m-d');
        }

        return new JsonResponse($formattedDates);
    }
}
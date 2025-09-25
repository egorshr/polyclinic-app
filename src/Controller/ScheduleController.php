<?php

namespace App\Controller;

use App\Entity\Employee;
use App\Entity\Schedule;
use App\Form\ScheduleForm;
use App\Repository\ScheduleRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('admin/schedule')]
final class ScheduleController extends AbstractController
{
    #[Route(name: 'app_schedule_index', methods: ['GET'])]
    public function index(ScheduleRepository $scheduleRepository): Response
    {
        return $this->render('schedule/index.html.twig', [
            'schedules' => $scheduleRepository->findAll(),
        ]);
    }

    #[Route('/new/for-employee/{id}', name: 'app_schedule_new_for_employee', methods: ['GET', 'POST'])]
    public function newForEmployee(Request $request, Employee $employee, EntityManagerInterface $entityManager): Response
    {
        // Создаем новое расписание, сразу привязанное к врачу
        $schedule = new Schedule(
            $employee,
            new DateTimeImmutable(), // Дата по умолчанию
            new DateTimeImmutable('09:00'), // Время "с" по умолчанию
            new DateTimeImmutable('17:00')  // Время "по" по умолчанию
        );

        $form = $this->createForm(ScheduleForm::class, $schedule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($schedule);
            $entityManager->flush();

            // Перенаправляем на страницу врача или список его расписаний
            return $this->redirectToRoute('app_employee_show', ['id' => $employee->getId()]);
        }

        return $this->render('schedule/new.html.twig', [
            'employee' => $employee,
            'schedule' => $schedule,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_schedule_show', methods: ['GET'])]
    public function show(Schedule $schedule): Response
    {
        return $this->render('schedule/show.html.twig', [
            'schedule' => $schedule,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_schedule_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Schedule $schedule, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ScheduleForm::class, $schedule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_schedule_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('schedule/edit.html.twig', [
            'schedule' => $schedule,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_schedule_delete', methods: ['POST'])]
    public function delete(Request $request, Schedule $schedule, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$schedule->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($schedule);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_schedule_index', [], Response::HTTP_SEE_OTHER);
    }
}

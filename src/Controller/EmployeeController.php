<?php

namespace App\Controller;

use App\Entity\Employee;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface; // Добавлено
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

// Добавлено
// use Symfony\Component\Security\Http\Attribute\IsGranted; // Убрали IsGranted

#[Route('/admin/employee')]
// #[IsGranted('ROLE_ADMIN')] <-- Атрибут удален
class EmployeeController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager, SessionInterface $session)
    {
        // Проверяем роль администратора вручную
        if ($session->get('role') !== 'ROLE_ADMIN') {
            throw new AccessDeniedHttpException('Для доступа к этому разделу требуются права администратора.');
        }
        $this->entityManager = $entityManager;
    }

    /**
     * Lists all employees.
     */
    #[Route('/', name: 'app_employee_index', methods: ['GET'])]
    public function index(): Response
    {
        $employees = $this->entityManager->getRepository(Employee::class)->findAll();

        return $this->render('employee/index.html.twig', [
            'employees' => $employees,
        ]);
    }

    /**
     * Displays a form to edit an existing employee.
     */
    #[Route('/{id}/edit', name: 'app_employee_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Employee $employee): Response
    {
        if ($request->isMethod('POST')) {
            $employee->setPhoneNumber($request->request->get('phoneNumber'));
            $this->entityManager->flush();

            $this->addFlash('success', 'Данные сотрудника обновлены.');
            return $this->redirectToRoute('app_employee_index');
        }

        return $this->render('employee/edit.html.twig', [
            'employee' => $employee,
        ]);
    }

    /**
     * Deletes an employee and their associated user account.
     */
    #[Route('/{id}', name: 'app_employee_delete', methods: ['POST'])]
    public function delete(Request $request, Employee $employee): Response
    {
        if ($this->isCsrfTokenValid('delete'.$employee->getId(), $request->request->get('_token'))) {
            $userToDelete = $employee->getUser();

            $this->entityManager->remove($employee);
            if ($userToDelete) {
                $this->entityManager->remove($userToDelete);
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'Сотрудник и его учетная запись были удалены.');
        }

        return $this->redirectToRoute('app_employee_index');
    }
}
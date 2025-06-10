<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class HomepageController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_homepage')]
    public function index(SessionInterface $session): RedirectResponse
    {
        if ($session->has('user_id')) {
            $userId = $session->get('user_id');
            /** @var User|null $user */
            $user = $this->entityManager->getRepository(User::class)->find($userId);

            if ($user) {
                $userRole = $user->getRole();

                // 1. Администратор перенаправляется на список всех записей
                if ($userRole === 'ROLE_ADMIN') {
                    return $this->redirectToRoute('visit_list');
                }

                // 2. Врач также перенаправляется на список (для просмотра своего расписания)
                if ($userRole === 'ROLE_DOCTOR') {
                    return $this->redirectToRoute('visit_list');
                }

                // 3. Для пациентов (и базовых пользователей) проверяем наличие профиля
                if ($userRole === 'ROLE_PATIENT' || $userRole === 'ROLE_USER') {
                    if ($user->getPatientProfile()) {
                        // Профиль есть - на форму записи
                        return $this->redirectToRoute('visit_form_show');
                    } else {
                        // Профиля нет - на его создание
                        $this->addFlash('warning', 'Для продолжения необходимо завершить создание профиля пациента.');
                        return $this->redirectToRoute('patient_profile_create_form');
                    }
                }

                // 4. Резервный вариант для других возможных ролей
                return $this->redirectToRoute('visit_form_show');

            } else {
                // Пользователь не найден в БД, хотя ID есть в сессии. Очищаем сессию.
                $session->invalidate();
            }
        }

        // Если user_id в сессии нет или он был невалидным
        return $this->redirectToRoute('auth_login_form');
    }
}
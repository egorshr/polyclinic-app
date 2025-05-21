<?php
// src/Controller/HomepageController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function index(SessionInterface $session): RedirectResponse
    {
        // Проверяем, залогинен ли уже пользователь
        // (используем ту же логику, что и в AuthController::isLoggedIn, но напрямую через сессию)
        if ($session->has('user_id')) {
            // Если залогинен, перенаправляем на форму бронирования (или другую главную страницу приложения)
            return $this->redirectToRoute('booking_form_show');
        }

        // Если не залогинен, перенаправляем на страницу входа
        return $this->redirectToRoute('auth_login_form');
    }
}
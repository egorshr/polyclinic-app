<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController
{
    private UserRepository $userRepository;
    private SessionInterface $session;
    private EntityManagerInterface $entityManager;

    public function __construct(UserRepository $userRepository, SessionInterface $session, EntityManagerInterface $entityManager)
    {
        $this->userRepository = $userRepository;
        $this->session = $session;
        $this->entityManager = $entityManager;
    }

    #[Route('/login', name: 'login_form', methods: ['GET'])]
    public function showLoginForm(): Response
    {
        return $this->render('auth/login.html.twig', ['errors' => []]);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): Response
    {
        $username = $request->request->get('username', '');
        $password = $request->request->get('password', '');
        $errors = [];

        if (empty($username) || empty($password)) {
            return $this->render('auth/login.html.twig', ['errors' => ['Введите логин и пароль']]);
        }

        $user = $this->userRepository->getUserByUsername($username);

        if (!$user || !password_verify($password, $user->getPassword())) {
            return $this->render('auth/login.html.twig', ['errors' => ['Неверные учетные данные']]);
        }

        $this->session->set('user_id', $user->getId());
        $this->session->set('username', $user->getUsername());
        $this->session->set('role', $user->getRoles());

        return $this->redirectToRoute('form_page');
    }

    #[Route('/register', name: 'register_form', methods: ['GET'])]
    public function showRegisterForm(): Response
    {
        return $this->render('auth/register.html.twig', ['errors' => []]);
    }

    #[Route('/form', name: 'form_page', methods: ['GET'])]
    public function formPage(): Response
    {
        // Здесь можно отрисовать шаблон с формой или любой другой контент
        return $this->render('auth/form_page.html.twig');
    }



    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): Response
    {
        $username = $request->request->get('username', '');
        $password = $request->request->get('password', '');
        $confirmPassword = $request->request->get('confirm_password', '');
        $errors = [];

        if (empty($username)) {
            $errors[] = "Логин обязателен для заполнения";
        } elseif (strlen($username) < 3) {
            $errors[] = "Логин должен содержать минимум 3 символа";
        }

        if (empty($password)) {
            $errors[] = "Пароль обязателен для заполнения";
        } elseif (strlen($password) < 6) {
            $errors[] = "Пароль должен содержать минимум 6 символов";
        }

        if ($password !== $confirmPassword) {
            $errors[] = "Пароли не совпадают";
        }

        if ($this->userRepository->getUserByUsername($username)) {
            $errors[] = "Пользователь с таким логином уже существует";
        }


        if (!empty($errors)) {
            return $this->render('auth/register.html.twig', ['errors' => $errors]);
        }

        $user = new User();
        $user->setUsername($username);
        $user->setPassword(password_hash($password, PASSWORD_DEFAULT));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->redirectToRoute('login_form');
    }

    #[Route('/logout', name: 'logout')]
    #[NoReturn]
    public function logout(): RedirectResponse
    {
        $this->session->clear();
        return $this->redirectToRoute('login_form');
    }

    public static function isLoggedIn(SessionInterface $session): bool
    {
        return $session->has('user_id');
    }

    public static function hasRole(SessionInterface $session, string $role): bool
    {
        return $session->get('role') === $role;
    }

    public static function requireLogin(SessionInterface $session): RedirectResponse|null
    {
        if (!self::isLoggedIn($session)) {
            return new RedirectResponse('/login');
        }
        return null;
    }

    public static function requireAdmin(SessionInterface $session): Response|null
    {
        if (!self::isLoggedIn($session)) {
            return new RedirectResponse('/login');
        }

        if (!self::hasRole($session, 'admin')) {
            return new Response('Доступ запрещен. Требуются права администратора.', 403);
        }

        return null;
    }
}

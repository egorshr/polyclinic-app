<?php
// src/Controller/AuthController.php
namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthController extends AbstractController
{
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private EntityManagerInterface $entityManager;

    public function __construct(
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ) {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
    }

    #[Route('/login', name: 'auth_login_form', methods: ['GET'])]
    public function showLoginForm(Request $request): Response
    {
        $errors = $request->getSession()->getFlashBag()->get('login_errors', []);
        $usernameAttempt = $request->getSession()->getFlashBag()->get('login_username_attempt');
        return $this->render('auth/login.html.twig', [
            'errors' => $errors,
            'username' => $usernameAttempt[0] ?? ''
        ]);
    }

    #[Route('/login', name: 'auth_login_handle', methods: ['POST'])]
    public function login(Request $request, SessionInterface $session): Response
    {
        $username = $request->request->get('username', '');
        $password = $request->request->get('password', '');
        $errors = [];

        if (empty($username) || empty($password)) {
            $session->getFlashBag()->add('login_errors', 'Логин и пароль обязательны для заполнения.');
            if (!empty($username)) {
                $session->getFlashBag()->add('login_username_attempt', $username);
            }
            return $this->redirectToRoute('auth_login_form');
        }

        $user = $this->userRepository->findOneBy(['username' => $username]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            $session->getFlashBag()->add('login_errors', 'Неверный логин или пароль.');
            $session->getFlashBag()->add('login_username_attempt', $username);
            return $this->redirectToRoute('auth_login_form');
        }

        $session->set('user_id', $user->getId());
        $session->set('username', $user->getUsername());
        $session->set('role', $user->getRole());

        return $this->redirectToRoute('booking_form_show');
    }

    #[Route('/register', name: 'auth_register_form', methods: ['GET'])]
    public function showRegisterForm(Request $request): Response
    {
        $errors = $request->getSession()->getFlashBag()->get('register_errors', []);
        $formData = $request->getSession()->getFlashBag()->get('register_form_data');
        $currentFormData = $formData[0] ?? [];

        return $this->render('auth/register.html.twig', [
            'errors' => $errors,
            'username' => $currentFormData['username'] ?? '',
        ]);
    }

    #[Route('/register', name: 'auth_register_handle', methods: ['POST'])]
    public function register(Request $request, SessionInterface $session): Response
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

        if ($this->userRepository->findOneBy(['username' => $username])) {
            $errors[] = "Пользователь с таким логином уже существует";
        }

        if (!empty($errors)) {
            $session->getFlashBag()->add('register_errors', $errors);
            $session->getFlashBag()->add('register_form_data', ['username' => $username]);
            return $this->redirectToRoute('auth_register_form');
        }

        $tempUserForHasher = new User($username, '');
        $hashedPassword = $this->passwordHasher->hashPassword($tempUserForHasher, $password);

        $user = new User($username, $hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $session->getFlashBag()->add('login_success', 'Регистрация прошла успешно. Пожалуйста, войдите.');
        return $this->redirectToRoute('auth_login_form');
    }

    #[Route('/logout', name: 'auth_logout')]
    public function logout(SessionInterface $session): RedirectResponse
    {
        $session->invalidate();
        return $this->redirectToRoute('auth_login_form');
    }

    public function isLoggedIn(SessionInterface $session): bool
    {
        return $session->has('user_id');
    }

    public function hasRole(string $role, SessionInterface $session): bool
    {
        return $session->has('role') && $session->get('role') === $role;
    }

    public function requireLogin(SessionInterface $session, UrlGeneratorInterface $urlGenerator): ?RedirectResponse
    {
        if (!$this->isLoggedIn($session)) {
            return new RedirectResponse($urlGenerator->generate('auth_login_form'));
        }
        return null;
    }

    public function requireAdmin(SessionInterface $session, UrlGeneratorInterface $urlGenerator): ?Response
    {
        $redirect = $this->requireLogin($session, $urlGenerator);
        if ($redirect) {
            return $redirect;
        }
        if (!$this->hasRole('admin', $session)) {
            throw new AccessDeniedHttpException("Доступ запрещен. Требуются права администратора.");
        }
        return null;
    }
}
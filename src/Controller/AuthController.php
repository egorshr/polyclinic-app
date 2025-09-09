<?php

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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface; // Может понадобиться для requireLogin/Admin
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException; // Для requireAdmin
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;


class AuthController extends AbstractController
{
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    // UrlGeneratorInterface не нужен в конструкторе, если requireLogin/Admin не будут его использовать напрямую здесь

    public function __construct(
        UserRepository              $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface      $entityManager,
        ValidatorInterface          $validator
    )
    {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    #[Route('/login', name: 'auth_login_form', methods: ['GET'])]
    public function showLoginForm(Request $request): Response
    {
        // Если пользователь уже залогинен, перенаправляем его
        if ($request->getSession()->has('user_id')) {
            /** @var User|null $currentUser */
            $currentUser = $this->entityManager->getRepository(User::class)->find($request->getSession()->get('user_id'));
            if ($currentUser) {
                if ($currentUser->getRole() === 'ROLE_PATIENT' && !$currentUser->getPatientProfile()) {
                    return $this->redirectToRoute('patient_profile_create_form');
                }
                return $this->redirectToRoute('visit_form_show'); // или app_homepage
            }
        }

        $errors = $request->getSession()->getFlashBag()->get('login_errors', []);
        $usernameAttemptMessages = $request->getSession()->getFlashBag()->get('login_username_attempt');
        $usernameAttempt = $usernameAttemptMessages[0] ?? '';

        // Собираем все типы flash-сообщений для отображения
        $allFlashes = $request->getSession()->getFlashBag()->all();

        return $this->render('auth/login.html.twig', [
            'errors' => $errors, // Специфичные для логина ошибки
            'username' => $usernameAttempt,
            'all_flashes' => $allFlashes, // Передаем все flash-сообщения
        ]);
    }

    #[Route('/login', name: 'auth_login_handle', methods: ['POST'])]
    public function login(Request $request, SessionInterface $session): Response
    {
        // Если пользователь уже залогинен, не даем ему логиниться снова
        if ($session->has('user_id')) {
            return $this->redirectToRoute('app_homepage'); // или куда-нибудь еще
        }

        $username = $request->request->get('username', '');
        $password = $request->request->get('password', '');

        if (empty($username) || empty($password)) {
            $session->getFlashBag()->add('login_errors', ['_general' => 'Логин и пароль обязательны для заполнения.']);
            if (!empty($username)) {
                $session->getFlashBag()->add('login_username_attempt', $username);
            }
            return $this->redirectToRoute('auth_login_form');
        }

        /** @var User|null $user */
        $user = $this->userRepository->findOneBy(['username' => $username]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            $session->getFlashBag()->add('login_errors', ['_general' => 'Неверный логин или пароль.']);
            $session->getFlashBag()->add('login_username_attempt', $username);
            return $this->redirectToRoute('auth_login_form');
        }

        $session->set('user_id', $user->getId());
        $session->set('username', $user->getUsername());
        $session->set('role', $user->getRole());

        $this->addFlash('success', 'Вход выполнен успешно!');

        // Проверяем, есть ли сохраненный целевой путь
        $targetPath = $session->get('_security.main.target_path');
        if ($targetPath) {
            $session->remove('_security.main.target_path'); // Очищаем после использования
            return $this->redirect($targetPath);
        }

        // Логика редиректа по умолчанию в зависимости от роли и наличия профиля
        if ($user->getRole() === 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_homepage'); // Заглушка, замени на 'admin_dashboard'
        } elseif ($user->getRole() === 'ROLE_DOCTOR') {
            return $this->redirectToRoute('app_homepage'); // Заглушка, замени на 'doctor_dashboard'
        }

        if ($user->getRole() === 'ROLE_PATIENT' || $user->getRole() === 'ROLE_USER') {
            if ($user->getPatientProfile()) {
                return $this->redirectToRoute('visit_form_show');
            } else {
                $this->addFlash('warning', 'Пожалуйста, завершите создание вашего профиля пациента.');
                return $this->redirectToRoute('patient_profile_create_form');
            }
        }

        return $this->redirectToRoute('app_homepage');
    }

    #[Route('/register', name: 'auth_register_form', methods: ['GET'])]
    public function showRegisterForm(Request $request): Response
    {
        // Если пользователь уже залогинен, перенаправляем его
        if ($request->getSession()->has('user_id')) {
            return $this->redirectToRoute('app_homepage');
        }

        $flashErrors = $request->getSession()->getFlashBag()->get('register_errors');
        $actualErrors = $flashErrors[0] ?? [];

        $formDataMessages = $request->getSession()->getFlashBag()->get('register_form_data');
        $currentFormData = $formDataMessages[0] ?? [];

        $allFlashes = $request->getSession()->getFlashBag()->all();


        return $this->render('auth/register.html.twig', [
            'errors' => $actualErrors,
            'username' => $currentFormData['username'] ?? '',
            'email' => $currentFormData['email'] ?? '',
            'firstName' => $currentFormData['firstName'] ?? '',
            'lastName' => $currentFormData['lastName'] ?? '',
            'all_flashes' => $allFlashes,
        ]);
    }

    #[Route('/register', name: 'auth_register_handle', methods: ['POST'])]
    public function register(Request $request, SessionInterface $session): Response
    {
        // Если пользователь уже залогинен
        if ($session->has('user_id')) {
            return $this->redirectToRoute('app_homepage');
        }

        $username = trim($request->request->get('username', ''));
        $email = trim($request->request->get('email', ''));
        $firstName = trim($request->request->get('firstName', ''));
        $lastName = trim($request->request->get('lastName', ''));
        $password = $request->request->get('password', '');
        $confirmPassword = $request->request->get('confirm_password', '');
        $errors = [];

        // ... (код валидации полей остается таким же, как в предыдущей версии) ...
        // Валидация username
        if (empty($username)) {
            $errors['username'] = "Логин обязателен для заполнения";
        } elseif (strlen($username) < 3) {
            $errors['username'] = "Логин должен содержать минимум 3 символа";
        } elseif ($this->userRepository->findOneBy(['username' => $username])) {
            $errors['username'] = "Пользователь с таким логином уже существует";
        }
        // Валидация email
        if (empty($email)) {
            $errors['email'] = "Email обязателен для заполнения";
        } else {
            $emailConstraint = new Assert\Email();
            $emailConstraint->message = 'Некорректный формат email адреса.';
            $violations = $this->validator->validate($email, $emailConstraint);
            if (count($violations) > 0) {
                $errors['email'] = $violations[0]->getMessage();
            } elseif ($this->userRepository->findOneBy(['email' => $email])) {
                $errors['email'] = "Пользователь с таким email уже существует";
            }
        }
        // Валидация firstName, lastName, password, confirmPassword (без изменений)
        if (empty($firstName)) $errors['firstName'] = "Имя обязательно для заполнения";
        if (empty($lastName)) $errors['lastName'] = "Фамилия обязательна для заполнения";
        if (empty($password)) $errors['password'] = "Пароль обязателен для заполнения";
        elseif (strlen($password) < 6) $errors['password'] = "Пароль должен содержать минимум 6 символов";
        if ($password !== $confirmPassword) $errors['confirm_password'] = "Пароли не совпадают";


        if (!empty($errors)) {
            $session->getFlashBag()->add('register_errors', $errors);
            $session->getFlashBag()->add('register_form_data', [
                'username' => $username, 'email' => $email,
                'firstName' => $firstName, 'lastName' => $lastName,
            ]);
            return $this->redirectToRoute('auth_register_form');
        }

        $defaultRole = 'ROLE_PATIENT';
        $user = new User($username, $email, $firstName, $lastName, $defaultRole);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPasswordHash($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Автоматический вход
        $session->set('user_id', $user->getId());
        $session->set('username', $user->getUsername());
        $session->set('role', $user->getRole());

        $this->addFlash('success', 'Регистрация прошла успешно! Теперь, пожалуйста, заполните ваш профиль пациента.');
        return $this->redirectToRoute('patient_profile_create_form'); // Сразу на создание профиля
    }

    #[Route('/logout', name: 'auth_logout')]
    public function logout(Request $request): RedirectResponse // Используем Request для доступа к сессии
    {
        $session = $request->getSession();

        $session->remove('user_id');
        $session->remove('username');
        $session->remove('role');
        // Можно также удалить _security.main.target_path, если он там есть
        // $session->remove('_security.main.target_path');

        $session->invalidate();

        $this->addFlash('success', 'Вы успешно вышли из системы.');
        return $this->redirectToRoute('auth_login_form');
    }

    // Вспомогательные методы isLoggedIn, hasRole, requireLogin, requireAdmin
    // Если они не используются вне этого контроллера, можно сделать private
    // или рассмотреть их вынос в трейт/сервис, если они нужны в других местах
    // и ты не хочешь использовать встроенную систему безопасности Symfony для этого.

    public function isLoggedIn(SessionInterface $session): bool
    {
        return $session->has('user_id');
    }

    public function hasRole(string $role, SessionInterface $session): bool
    {
        $sessionRole = strtoupper((string)$session->get('role'));
        $targetRole = strtoupper($role);
        if (!str_starts_with($sessionRole, 'ROLE_')) $sessionRole = 'ROLE_' . $sessionRole;
        if (!str_starts_with($targetRole, 'ROLE_')) $targetRole = 'ROLE_' . $targetRole;
        return $session->has('role') && $sessionRole === $targetRole;
    }

}
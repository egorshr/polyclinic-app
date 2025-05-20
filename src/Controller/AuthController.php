<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\LoginFormType;
use App\Form\RegisterFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Если пользователь уже авторизован, перенаправляем на страницу бронирования
        if ($this->getUser()) {
            return $this->redirectToRoute('booking_index');
        }

        // Получаем ошибку аутентификации, если есть
        $error = $authenticationUtils->getLastAuthenticationError();
        // Получаем последнее введенное имя пользователя
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // Если пользователь уже авторизован, перенаправляем на страницу бронирования
        if ($this->getUser()) {
            return $this->redirectToRoute('booking_index');
        }

        $user = new User('', '');
        $form = $this->createForm(RegisterFormType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Хешируем пароль перед сохранением
            $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPasswordHash($hashedPassword);

            $em->persist($user);
            $em->flush();

            // Добавляем сообщение об успешной регистрации
            $this->addFlash('success', 'Регистрация успешно завершена. Теперь вы можете войти.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Этот метод может быть пустым - Symfony перехватывает его
        // и выполняет выход из системы автоматически
        throw new \LogicException('Этот метод никогда не должен вызываться напрямую.');
    }
}
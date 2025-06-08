<?php

namespace App\Controller;

use App\Entity\Patient;
use App\Entity\User;
use App\Form\PatientProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface; // Для генерации URL в requireLogin

#[Route('/profile/patient')]
// Убираем #[IsGranted('ROLE_USER')] на уровне класса, чтобы управлять доступом через requireLogin в методах
class PatientProfileController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator)
    {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Вспомогательный метод для проверки, залогинен ли пользователь по нашей ручной сессии.
     * Если нет, редиректит на страницу логина.
     */
    private function requireLogin(Request $request): ?RedirectResponse
    {
        if (!$request->getSession()->has('user_id')) {
            $this->addFlash('error', 'Для доступа к этой странице необходимо авторизоваться.');

            // Сохраняем целевой путь, чтобы вернуться сюда после логина
            // Это полезно, если пользователь напрямую зашел на URL профиля будучи не залогиненым
            if ($request->attributes->get('_route') !== 'auth_login_form') { // Не сохраняем, если мы уже на странице логина
                $request->getSession()->set('_security.main.target_path', $request->getUri());
            }
            return new RedirectResponse($this->urlGenerator->generate('auth_login_form'));
        }
        return null;
    }

    /**
     * Получает текущего пользователя User из сессии.
     * Важно: этот метод не использует систему безопасности Symfony, а нашу ручную сессию.
     */
    private function getCurrentUserFromSession(Request $request): ?User
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return null;
        }
        return $this->entityManager->getRepository(User::class)->find($userId);
    }

    #[Route('/create', name: 'patient_profile_create_form', methods: ['GET', 'POST'])]
    #[Route('/edit', name: 'patient_profile_edit_form', methods: ['GET', 'POST'])] // Один метод для создания и редактирования
    public function createOrEditProfile(Request $request): Response
    {
        if ($redirect = $this->requireLogin($request)) {
            return $redirect;
        }

        /** @var User|null $user */
        $user = $this->getCurrentUserFromSession($request);

        if (!$user) {
            // Эта ситуация не должна возникнуть, если requireLogin отработал,
            // но на всякий случай для защиты.
            $this->addFlash('error', 'Ошибка определения пользователя. Пожалуйста, перезайдите.');
            return $this->redirectToRoute('auth_login_form');
        }

        $patient = $user->getPatientProfile();
        $isNewProfile = false;

        if (!$patient) {
            $patient = new Patient($user); // Предполагаем, что конструктор Patient принимает User
            // и может инициализировать firstName/lastName из User
            $isNewProfile = true;
        }

        // Если User уже имеет имя/фамилию, можно их предзаполнить в Patient, если они пусты и профиль новый.
        // Это уже должно быть сделано в конструкторе Patient, если мы его так настроили.
        // Если нет, то можно здесь:
        // if ($isNewProfile) {
        //     if (empty($patient->getFirstName()) && !empty($user->getFirstName())) {
        //         $patient->setFirstName($user->getFirstName());
        //     }
        //     if (empty($patient->getLastName()) && !empty($user->getLastName())) {
        //         $patient->setLastName($user->getLastName());
        //     }
        // }


        $form = $this->createForm(PatientProfileType::class, $patient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Убедимся, что пациент связан с правильным пользователем
            // (на случай если форма это как-то изменила, хотя не должна)
            if ($patient->getUser() !== $user) {
                $patient->setUser($user);
            }

            $this->entityManager->persist($patient);
            $this->entityManager->flush();

            $this->addFlash('success', $isNewProfile ? 'Профиль пациента успешно создан!' : 'Профиль пациента успешно обновлен!');

            // После создания/обновления профиля пытаемся вернуться на целевую страницу или на форму записи
            $targetPath = $request->getSession()->get('_security.main.target_path');
            if ($targetPath && $targetPath !== $request->getUri()) { // Не редиректим на самих себя
                $request->getSession()->remove('_security.main.target_path');
                return $this->redirect($targetPath);
            }

            return $this->redirectToRoute('visit_form_show'); // Или на страницу просмотра профиля 'patient_profile_view'
        }

        return $this->render('patient_profile/form.html.twig', [
            'profileForm' => $form->createView(),
            'isNewProfile' => $isNewProfile,
            'page_title' => $isNewProfile ? 'Создание профиля пациента' : 'Редактирование профиля пациента',
        ]);
    }

    #[Route('', name: 'patient_profile_view', methods: ['GET'])]
    public function viewProfile(Request $request): Response
    {
        if ($redirect = $this->requireLogin($request)) {
            return $redirect;
        }

        /** @var User|null $user */
        $user = $this->getCurrentUserFromSession($request);
        if (!$user) {
            $this->addFlash('error', 'Ошибка определения пользователя.');
            return $this->redirectToRoute('auth_login_form');
        }

        $patient = $user->getPatientProfile();

        if (!$patient) {
            $this->addFlash('warning', 'Профиль пациента не найден. Пожалуйста, создайте его.');
            // Сохраняем текущий URL, чтобы вернуться сюда, если это не страница создания профиля
            if ($request->attributes->get('_route') !== 'patient_profile_create_form') {
                $request->getSession()->set('_security.main.target_path', $request->getUri());
            }
            return $this->redirectToRoute('patient_profile_create_form');
        }

        return $this->render('patient_profile/form.html.twig', [
            'patient' => $patient,
        ]);
    }
}
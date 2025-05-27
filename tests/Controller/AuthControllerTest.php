<?php

namespace App\Tests\Controller;

use App\Entity\User;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class AuthControllerTest extends WebTestCase
{


    public function testShowLoginForm(): void
    {
        $client = static::createClient();
        $router = static::getContainer()->get('router');

        $loginFormUrl = $router->generate('auth_login_form');
        $loginHandleUrl = $router->generate('auth_login_handle');
        $registerFormUrl = $router->generate('auth_register_form');

        $client->request('GET', $loginFormUrl);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1.page-title', 'Вход в систему');
        $this->assertSelectorExists('form[action="' . $loginHandleUrl . '"][method="POST"]');
        $this->assertSelectorExists('input[name="username"]');
        $this->assertSelectorTextContains('label[for="username"]', 'Логин');
        $this->assertSelectorExists('input[name="password"]');
        $this->assertSelectorTextContains('label[for="password"]', 'Пароль');
        $this->assertSelectorExists('button[type="submit"]');
        $this->assertSelectorTextContains('button[type="submit"]', 'Войти');
        $this->assertSelectorExists('div.register-link-custom a[href="' . $registerFormUrl . '"]');
        $this->assertSelectorTextContains('div.register-link-custom a[href="' . $registerFormUrl . '"]', 'Зарегистрироваться');
    }

    public function testSuccessfulLogin(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $router = $container->get('router');


        $testUsername = 'testuser_login_' . uniqid();
        $testPassword = 'password123';

        $user = new User($testUsername, '');
        $hashedPassword = $passwordHasher->hashPassword($user, $testPassword);
        $user->setPasswordHash($hashedPassword);

        $entityManager->persist($user);
        $entityManager->flush();


        $loginFormUrl = $router->generate('auth_login_form');
        $crawler = $client->request('GET', $loginFormUrl);
        $this->assertResponseIsSuccessful();


        $form = $crawler->selectButton('Войти')->form([
            'username' => $testUsername,
            'password' => $testPassword,
        ]);
        $client->submit($form);


        $this->assertResponseRedirects($router->generate('booking_form_show'));
        $client->followRedirect();


        $session = $client->getRequest()->getSession();
        $this->assertNotNull($session->get('user_id'), 'User ID should be in session after login.');
        $this->assertEquals($testUsername, $session->get('username'), 'Username should be in session after login.');
        $this->assertEquals('user', $session->get('role'), 'Default role should be in session after login.');


        $userToRemove = $entityManager->getRepository(User::class)->findOneBy(['username' => $testUsername]);
        if ($userToRemove) {
            $entityManager->remove($userToRemove);
            $entityManager->flush();
        }
    }

    public function testFailedLoginWithWrongCredentials(): void
    {
        $client = static::createClient();
        $router = static::getContainer()->get('router');

        $loginFormUrl = $router->generate('auth_login_form');
        $crawler = $client->request('GET', $loginFormUrl);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Войти')->form([
            'username' => 'wronguser' . uniqid(),
            'password' => 'wrongpassword',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects($router->generate('auth_login_form'));
        $client->followRedirect();
        $this->assertSelectorTextContains('.login-errors', 'Неверный логин или пароль.');

        $session = $client->getRequest()->getSession();
        $this->assertNull($session->get('user_id'), 'User ID should not be in session after failed login.');
    }

    public function testFailedLoginWithEmptyCredentials(): void
    {
        $client = static::createClient();
        $router = static::getContainer()->get('router');

        $loginFormUrl = $router->generate('auth_login_form');
        $crawler = $client->request('GET', $loginFormUrl);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Войти')->form([
            'username' => '',
            'password' => '',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects($router->generate('auth_login_form'));
        $client->followRedirect();
        $this->assertSelectorTextContains('.login-errors', 'Логин и пароль обязательны для заполнения.');
    }

}
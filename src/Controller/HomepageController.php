<?php

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
        if ($session->has('user_id')) {
            return $this->redirectToRoute('booking_form_show');
        }
        return $this->redirectToRoute('auth_login_form');
    }
}
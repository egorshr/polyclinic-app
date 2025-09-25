<?php

namespace App\Controller;

use App\Entity\Service;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/service')]
class ServiceController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager, SessionInterface $session)
    {
        if ($session->get('role') !== 'ROLE_ADMIN') {
            throw new AccessDeniedHttpException('Для доступа к этому разделу требуются права администратора.');
        }
        $this->entityManager = $entityManager;
    }

    /**
     * Displays a form to create a new service.
     */
    #[Route('/new', name: 'app_service_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name', '');
            $price = $request->request->get('price', '');

            if (!empty($name) && is_numeric($price) && $price >= 0) {
                $service = new Service();
                $service->setName($name);
                $service->setPrice($price);

                $this->entityManager->persist($service);
                $this->entityManager->flush();

                $this->addFlash('success', 'Услуга успешно создана.');
                return $this->redirectToRoute('app_service_index');
            } else {
                $this->addFlash('danger', 'Ошибка валидации. Проверьте введенные данные.');
            }
        }

        return $this->render('service/new.html.twig');
    }

    /**
     * Lists all services and provides actions to manage them.
     */
    #[Route('/', name: 'app_service_index', methods: ['GET'])]
    public function index(): Response
    {
        $services = $this->entityManager->getRepository(Service::class)->findAll();

        return $this->render('service/index.html.twig', [
            'services' => $services,
        ]);
    }

    /**
     * Displays a form to edit an existing service.
     */
    #[Route('/{id}/edit', name: 'app_service_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Service $service): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name', '');
            $price = $request->request->get('price', '');

            if (!empty($name) && is_numeric($price) && $price >= 0) {
                $service->setName($name);
                $service->setPrice($price);

                $this->entityManager->flush();
                $this->addFlash('success', 'Услуга успешно обновлена.');

                return $this->redirectToRoute('app_service_index');
            } else {
                $this->addFlash('danger', 'Ошибка валидации. Проверьте введенные данные.');
            }
        }

        return $this->render('service/edit.html.twig', [
            'service' => $service,
        ]);
    }

    /**
     * Deletes a service.
     */
    #[Route('/{id}', name: 'app_service_delete', methods: ['POST'])]
    public function delete(Request $request, Service $service): Response
    {
        if ($this->isCsrfTokenValid('delete'.$service->getId(), $request->request->get('_token'))) {
            if (!$service->getVisits()->isEmpty()) {
                $this->addFlash('danger', 'Невозможно удалить услугу, так как она связана с существующими записями на прием.');
                return $this->redirectToRoute('app_service_index');
            }

            $this->entityManager->remove($service);
            $this->entityManager->flush();
            $this->addFlash('success', 'Услуга была успешно удалена.');
        }

        return $this->redirectToRoute('app_service_index');
    }
}
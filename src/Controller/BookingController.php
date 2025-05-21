<?php


namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Photographer;
use App\Entity\Service;
use App\Repository\BookingRepository;
use App\Repository\ServiceRepository;
use App\Repository\PhotographerRepository;
use App\Service\DataMigrator;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Exception;

#[Route('/booking')]
class BookingController extends AbstractController
{
    private BookingRepository $repository;
    private EntityManagerInterface $entityManager;
    private SessionInterface $session;
    private UrlGeneratorInterface $urlGenerator;
    private KernelInterface $kernel;
    private ServiceRepository $serviceRepository;
    private PhotographerRepository $photographerRepository;
    private DataMigrator $dataMigrator; // Добавлено свойство для DataMigrator

    public function __construct(
        BookingRepository $bookingRepository,
        EntityManagerInterface $entityManager,
        SessionInterface $session,
        UrlGeneratorInterface $urlGenerator,
        KernelInterface $kernel,
        ServiceRepository $serviceRepository,
        PhotographerRepository $photographerRepository,
        DataMigrator $dataMigrator // Добавлена инъекция DataMigrator
    ) {
        $this->repository = $bookingRepository;
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->urlGenerator = $urlGenerator;
        $this->kernel = $kernel;
        $this->serviceRepository = $serviceRepository;
        $this->photographerRepository = $photographerRepository;
        $this->dataMigrator = $dataMigrator; // Присвоено свойство
    }

    private function getUserId(): int
    {
        return $this->session->get('user_id', 0);
    }

    private function requireLogin(): ?RedirectResponse
    {
        if ($this->getUserId() <= 0) {
            $this->addFlash('error', 'Для доступа к этой странице необходимо авторизоваться.');
            return new RedirectResponse($this->urlGenerator->generate('auth_login_form'));
        }
        return null;
    }

    #[Route('/form', name: 'booking_form_show', methods: ['GET'])]
    public function showForm(Request $request): Response
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $errors = $this->session->getFlashBag()->get('form_errors', []);
        $formDataFromSession = $this->session->getFlashBag()->get('form_data');
        $currentData = $formDataFromSession[0] ?? [];

        $storageType = $request->cookies->get('storage_type', 'csv');

        return $this->render('booking/form.html.twig', [
            'errors' => $errors,
            'data' => $currentData,
            'storageType' => $storageType,
            'availableServices' => Service::getAvailableServices(),
            'availablePhotographers' => Photographer::getAvailablePhotographers(),
        ]);
    }

    #[Route('/submit', name: 'booking_form_submit', methods: ['POST'])]
    public function submitForm(Request $request): Response
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $errors = [];
        $data = $request->request->all();
        $storageType = $request->cookies->get('storage_type', 'csv');

        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            $errors[] = "Имя не может быть пустым.";
        } elseif (mb_strlen($name) < 2) {
            $errors[] = "Имя должно содержать минимум 2 символа.";
        } elseif (!preg_match('/^[А-яЁёA-Za-z\s\-]+$/u', $name)) {
            $errors[] = "Имя может содержать только буквы, пробелы и дефисы.";
        }

        $dateInput = $data['date'] ?? '';
        if (empty($dateInput)) {
            $errors[] = "Дата не может быть пустой.";
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateInput)) {
            $errors[] = "Неверный формат даты.";
        } elseif (strtotime($dateInput) < strtotime(date('Y-m-d'))) {
            $errors[] = "Дата не может быть в прошлом.";
        }

        $serviceNameInput = $data['service'] ?? '';
        try {
            if (!in_array($serviceNameInput, Service::getAvailableServices(), true)) {
                throw new InvalidArgumentException('Невалидная услуга');
            }
        } catch (InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        }

        $photographerNameInput = $data['photographer'] ?? '';
        try {
            if (!in_array($photographerNameInput, Photographer::getAvailablePhotographers(), true)) {
                throw new InvalidArgumentException('Невалидный фотограф');
            }
        } catch (InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        }

        if (empty($errors)) {
            $userId = $this->getUserId();
            if ($userId <= 0) {
                $errors[] = "Ошибка сессии пользователя. Пожалуйста, перезайдите.";
                $this->session->getFlashBag()->add('form_errors', $errors);
                $this->session->getFlashBag()->add('form_data', $data);
                return $this->redirectToRoute('booking_form_show');
            }

            $booking = new Booking(
                $name,
                $serviceNameInput,
                $photographerNameInput,
                $dateInput,
                $userId
            );

            try {
                $this->repository->saveBooking($booking, $storageType);
            } catch (Exception $e) {
                $this->session->getFlashBag()->add('form_errors', ["Ошибка сохранения: " . $e->getMessage()]);
                $this->session->getFlashBag()->add('form_data', $data);
                return $this->redirectToRoute('booking_form_show');
            }

            return $this->redirectToRoute('booking_success');
        }

        $this->session->getFlashBag()->add('form_errors', $errors);
        $this->session->getFlashBag()->add('form_data', $data);
        return $this->redirectToRoute('booking_form_show');
    }

    #[Route('/success', name: 'booking_success', methods: ['GET'])]
    public function showSuccess(): Response
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }
        return $this->render('booking/sucсess.html.twig');
    }

    #[Route('/migrate', name: 'booking_migrate_data', methods: ['GET', 'POST'])]
    public function migrateData(Request $request): Response
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $message = ''; // Это переменная больше не используется для передачи в Twig, так как есть flash
        $migratedCount = 0;
        try {
            $userId = $this->getUserId();
            if ($userId <= 0) {
                throw new Exception("Необходимо авторизоваться для миграции данных");
            }

            $migratedCount = $this->dataMigrator->migrateFromCsvToDb($userId);
            $successMessage = "Успешно мигрировано записей: $migratedCount";
            $this->addFlash('success', $successMessage);

        } catch (Exception $e) {
            $errorMessage = "Ошибка при миграции данных: " . $e->getMessage();
            $this->addFlash('error', $errorMessage);
        }

        $storageType = $request->cookies->get('storage_type', 'csv');
        return $this->render('booking/migrate.html.twig', [
            'storageType' => $storageType,
        ]);
    }

    #[Route('/set-storage', name: 'booking_set_storage_type', methods: ['POST'])]
    public function setStorageType(Request $request): RedirectResponse
    {
        $type = $request->request->get('storage_type', 'csv');
        $response = new RedirectResponse($this->urlGenerator->generate('booking_form_show'));
        $response->headers->setCookie(Cookie::create('storage_type', $type, time() + 30 * 24 * 60 * 60, '/'));
        return $response;
    }

    #[Route('/list', name: 'booking_list', methods: ['GET'])]
    public function showBookings(Request $request): Response
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $storageType = $request->cookies->get('storage_type', 'csv');
        $userId = $this->getUserId();

        $filters = [
            'name' => $request->query->get('filter_name', ''),
            'service' => $request->query->get('filter_service', ''),
            'photographer' => $request->query->get('filter_photographer', ''),
            'date_from' => $request->query->get('filter_date_from', ''),
            'date_to' => $request->query->get('filter_date_to', '')
        ];

        $bookings = [];
        try {
            if ($storageType === 'db') {
                $bookings = $this->repository->getAllBookingsFromDb($filters, $userId);
            } else {
                $bookings = $this->repository->getAllBookingsFromCsv($filters, $userId);
            }
        } catch (Exception $e) {
            $this->addFlash('error', "Ошибка при загрузке бронирований: " . $e->getMessage());
        }

        return $this->render('booking/list.html.twig', [
            'bookings' => $bookings,
            'filters' => $filters,
            'storageType' => $storageType,
            'availableServices' => Service::getAvailableServices(),
            'availablePhotographers' => Photographer::getAvailablePhotographers(),
        ]);
    }

    private function getFilteredBookings(Request $request): array
    {
        $storageType = $request->cookies->get('storage_type', 'csv');
        $userId = $this->getUserId();
        if ($userId <= 0) {
            return [];
        }

        $filters = [
            'name' => $request->query->get('filter_name', ''),
            'service' => $request->query->get('filter_service', ''),
            'photographer' => $request->query->get('filter_photographer', ''),
            'date_from' => $request->query->get('filter_date_from', ''),
            'date_to' => $request->query->get('filter_date_to', '')
        ];

        try {
            if ($storageType === 'db') {
                return $this->repository->getAllBookingsFromDb($filters, $userId);
            } else {
                return $this->repository->getAllBookingsFromCsv($filters, $userId);
            }
        } catch (Exception $e) {
            $this->addFlash('error', "Ошибка при получении отфильтрованных бронирований для отчета: " . $e->getMessage());
            return [];
        }
    }

    private function renderPdfHtml(array $bookings): string
    {
        return $this->renderView('booking/pdf_template.html.twig', [
            'bookings' => $bookings,
        ]);
    }

    #[Route('/report/pdf', name: 'booking_report_pdf', methods: ['GET'])]
    public function generatePdfReport(Request $request): Response
    {
        if ($redirect = $this->requireLogin()) return $redirect;

        $bookings = $this->getFilteredBookings($request);
        if (empty($bookings)) {
            $this->addFlash('info', 'Нет данных для генерации PDF отчета.');
            return $this->redirectToRoute('booking_list');
        }

        $html = $this->renderPdfHtml($bookings);
        $tempDir = $this->kernel->getCacheDir() . '/mpdf_tmp';

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }
        if (!is_writable($tempDir)) {
            $this->addFlash('error', "Директория для временных файлов mPDF недоступна для записи: $tempDir");
            return $this->redirectToRoute('booking_list');
        }

        try {
            $mpdf = new Mpdf([
                'tempDir' => $tempDir,
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15,
            ]);

            $mpdf->WriteHTML($html);
            return new Response($mpdf->Output('bookings_report.pdf', 'S'), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="bookings_report.pdf"',
            ]);
        } catch (MpdfException $e) {
            $this->addFlash('error', 'Ошибка генерации PDF: ' . $e->getMessage());
            return $this->redirectToRoute('booking_list');
        }
    }

    #[Route('/report/excel', name: 'booking_report_excel', methods: ['GET'])]
    public function generateExcelReport(Request $request): Response
    {
        if ($redirect = $this->requireLogin()) return $redirect;

        $bookings = $this->getFilteredBookings($request);
        if (empty($bookings)) {
            $this->addFlash('info', 'Нет данных для генерации Excel отчета.');
            return $this->redirectToRoute('booking_list');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'ID')
            ->setCellValue('B1', 'Имя')
            ->setCellValue('C1', 'Услуга')
            ->setCellValue('D1', 'Фотограф')
            ->setCellValue('E1', 'Дата');

        $row = 2;
        foreach ($bookings as $bookingData) {
            $sheet->setCellValue('A'.$row, $bookingData['id'] ?? '');
            $sheet->setCellValue('B'.$row, $bookingData['name']);
            $sheet->setCellValue('C'.$row, $bookingData['service']);
            $sheet->setCellValue('D'.$row, $bookingData['photographer']);
            $sheet->setCellValue('E'.$row, $bookingData['date']);
            $row++;
        }

        $writer = new Xlsx($spreadsheet);

        $response = new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="bookings_report.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    #[Route('/report/csv', name: 'booking_report_csv', methods: ['GET'])]
    public function generateCsvReport(Request $request): Response
    {
        if ($redirect = $this->requireLogin()) return $redirect;

        $bookings = $this->getFilteredBookings($request);
        if (empty($bookings)) {
            $this->addFlash('info', 'Нет данных для генерации CSV отчета.');
            return $this->redirectToRoute('booking_list');
        }

        $response = new StreamedResponse();
        $response->setCallback(function () use ($bookings) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Имя', 'Услуга', 'Фотограф', 'Дата']);

            foreach ($bookings as $bookingData) {
                fputcsv($output, [
                    $bookingData['id'] ?? '',
                    $bookingData['name'],
                    $bookingData['service'],
                    $bookingData['photographer'],
                    $bookingData['date']
                ]);
            }
            fclose($output);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="bookings_report.csv"');

        return $response;
    }
}
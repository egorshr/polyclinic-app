<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Photographer;
use App\Entity\Service;
use App\Repository\BookingRepository;
use App\Service\DataMigrator;
use Exception;
use InvalidArgumentException;
use JetBrains\PhpStorm\NoReturn;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response, Session\SessionInterface};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Cookie;



class BookingController extends AbstractController
{
    public function __construct(
        private readonly BookingRepository $repository
    )
    {
    }

    #[Route('/form', name: 'booking_form', methods: ['GET'])]
    public function showForm(Request $request): Response
    {
        $errors = [];
        $data = $request->request->all();
        $storageType = $request->cookies->get('storage_type', 'csv');

        return $this->render('booking/form.html.twig', compact('errors', 'data', 'storageType'));
    }

    #[Route('/form/submit', name: 'submit_form', methods: ['POST'])]
    public function submitForm(Request $request, SessionInterface $session): Response
    {
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

        $date = $data['date'] ?? '';
        if (empty($date)) {
            $errors[] = "Дата не может быть пустой.";
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $errors[] = "Неверный формат даты.";
        } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
            $errors[] = "Дата не может быть в прошлом.";
        }

        try {
            $service = new Service($data['service'] ?? '');
        } catch (InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        }

        try {
            $photographer = new Photographer($data['photographer'] ?? '');
        } catch (InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        }

        $userId = $session->get('user_id', 0);
        if ($userId <= 0) {
            $errors[] = "Вы не авторизованы.";
        }

        if (!empty($errors)) {
            return $this->render('booking/bookings.html.twig', compact('errors', 'data', 'storageType'));
        }

        $booking = new Booking();

        $this->repository->saveBooking($booking, $storageType);

        return $this->redirectToRoute('booking_success');
    }

    #[Route('/form/success', name: 'booking_success', methods: ['GET'])]
    public function showSuccess(): Response
    {
        return $this->render('success.html.twig');
    }

    #[Route('/migrate', name: 'migrate_data', methods: ['GET'])]
    public function migrateData(SessionInterface $session): Response
    {
        try {
            $userId = $session->get('user_id', 0);
            if ($userId <= 0) {
                throw new Exception("Необходимо авторизоваться для миграции данных");
            }

            $migratedCount = DataMigrator::migrateFromCsvToDb($userId);
            $message = "Успешно мигрировано записей: $migratedCount";
        } catch (Exception $e) {
            $message = "Ошибка при миграции данных: " . $e->getMessage();
        }

        $storageType = $_COOKIE['storage_type'] ?? 'csv';
        return $this->render('migrate.html.twig', compact('message', 'storageType'));
    }

    #[Route('/set-storage', name: 'set_storage', methods: ['POST'])]
    #[NoReturn]
    public function setStorageType(Request $request): Response
    {
        $response = $this->redirectToRoute('booking_form');
        $type = $request->request->get('storage_type', 'csv');
        $response->headers->setCookie(new Cookie('storage_type', $type, time() + 30 * 24 * 60 * 60));
        return $response;
    }

    #[Route('/bookings', name: 'show_bookings', methods: ['GET'])]
    public function showBookings(Request $request, SessionInterface $session): Response
    {
        $storageType = $request->cookies->get('storage_type', 'csv');
        $userId = $session->get('user_id', 0);

        if ($userId <= 0) {
            return $this->redirectToRoute('login');
        }

        $filters = [
            'name' => $request->query->get('filter_name', ''),
            'service' => $request->query->get('filter_service', ''),
            'photographer' => $request->query->get('filter_photographer', ''),
            'date_from' => $request->query->get('filter_date_from', ''),
            'date_to' => $request->query->get('filter_date_to', '')
        ];

        $bookings = $storageType === 'db'
            ? $this->repository->getAllBookingsFromDb($filters, $userId)
            : $this->repository->getAllBookingsFromCsv($filters, $userId);

        $availableServices = Service::getAvailableServices();
        $availablePhotographers = Photographer::getAvailablePhotographers();

        return $this->render('booking/bookings.html.twig', compact('bookings', 'availableServices', 'availablePhotographers'));
    }

    #[Route('/report/pdf', name: 'generate_pdf', methods: ['GET'])]
    #[NoReturn]
    public function generatePdfReport(Request $request, SessionInterface $session): Response
    {
        $bookings = $this->getFilteredBookings($request, $session);

        $html = $this->renderView('pdf_template.html.twig', ['bookings' => $bookings]);

        $tempDir = sys_get_temp_dir() . '/mpdf_tmp';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $mpdf = new Mpdf([
            'tempDir' => $tempDir,
            'mode' => 'utf-8',
            'format' => 'A4',
        ]);

        $mpdf->WriteHTML($html);
        $mpdf->Output('bookings_report.pdf', 'D');
        exit;
    }

    #[Route('/report/excel', name: 'generate_excel', methods: ['GET'])]
    #[NoReturn]
    public function generateExcelReport(Request $request, SessionInterface $session): Response
    {
        $bookings = $this->getFilteredBookings($request, $session);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray(['ID', 'Имя', 'Услуга', 'Фотограф', 'Дата'], null, 'A1');

        $row = 2;
        foreach ($bookings as $booking) {
            $sheet->fromArray([
                $booking['id'] ?? '',
                $booking['name'],
                $booking['service'],
                $booking['photographer'],
                $booking['date']
            ], null, "A{$row}");
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $response = new Response();
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="bookings_report.xlsx"');

        ob_start();
        $writer->save('php://output');
        $excelOutput = ob_get_clean();
        $response->setContent($excelOutput);
        return $response;
    }

    #[Route('/report/csv', name: 'generate_csv', methods: ['GET'])]
    #[NoReturn]
    public function generateCsvReport(Request $request, SessionInterface $session): Response
    {
        $bookings = $this->getFilteredBookings($request, $session);

        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="bookings_report.csv"');

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['ID', 'Имя', 'Услуга', 'Фотограф', 'Дата']);
        foreach ($bookings as $booking) {
            fputcsv($handle, [
                $booking['id'] ?? '',
                $booking['name'],
                $booking['service'],
                $booking['photographer'],
                $booking['date']
            ]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $response->setContent($csv);
        return $response;
    }

    private function getFilteredBookings(Request $request, SessionInterface $session): array
    {
        $storageType = $request->cookies->get('storage_type', 'csv');
        $userId = $session->get('user_id', 0);
        $filters = [
            'name' => $request->query->get('filter_name', ''),
            'service' => $request->query->get('filter_service', ''),
            'photographer' => $request->query->get('filter_photographer', ''),
            'date_from' => $request->query->get('filter_date_from', ''),
            'date_to' => $request->query->get('filter_date_to', '')
        ];

        return $storageType === 'db'
            ? $this->repository->getAllBookingsFromDb($filters, $userId)
            : $this->repository->getAllBookingsFromCsv($filters, $userId);
    }
}

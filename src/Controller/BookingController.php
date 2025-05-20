<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Service\DataMigrator;
use DateTime;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class BookingController extends AbstractController
{
    private BookingRepository $repository;
    private DataMigrator $dataMigrator;

    public function __construct(BookingRepository $repository, DataMigrator $dataMigrator)
    {
        $this->repository = $repository;
        $this->dataMigrator = $dataMigrator;
    }

    #[Route('/booking/submit', name: 'booking_submit', methods: ['POST'])]
    public function submitForm(Request $request): Response
    {
        $data = $request->request->all();
        $errors = [];
        $storageType = $request->cookies->get('storage_type', 'csv');

        $name = trim($data['name'] ?? '');

        // Валидация имени
        if (empty($name)) {
            $errors[] = "Имя не может быть пустым.";
        } elseif (mb_strlen($name) < 2) {
            $errors[] = "Имя должно содержать минимум 2 символа.";
        } elseif (!preg_match('/^[А-яЁёA-Za-z\s\-]+$/u', $name)) {
            $errors[] = "Имя может содержать только буквы, пробелы и дефисы.";
        }

        // Валидация даты
        $dateStr = $data['date'] ?? '';
        $dateObj = DateTime::createFromFormat('Y-m-d', $dateStr);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $dateStr) {
            $errors[] = "Неверный формат даты.";
        } elseif ($dateObj < new DateTime('today')) {
            $errors[] = "Дата не может быть в прошлом.";
        }

        $serviceName = $data['service'] ?? '';
        $photographerName = $data['photographer'] ?? '';

        if (empty($serviceName)) {
            $errors[] = "Услуга не выбрана.";
        }
        if (empty($photographerName)) {
            $errors[] = "Фотограф не выбран.";
        }

        if (!empty($errors)) {
            return $this->render('booking/form.html.twig', [
                'errors' => $errors,
                'data' => $data,
                'storage_type' => $storageType,
            ]);
        }

        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $booking = new Booking();
        $booking->setName($name);
        $booking->setService($serviceName);
        $booking->setPhotographer($photographerName);
        $booking->setDate($dateObj);
        $booking->setUserId($user->getId());

        if ($storageType === 'db') {
            $this->repository->save($booking, true);
        } else {
            $this->repository->saveToCsv($booking);
        }

        return $this->redirectToRoute('booking_success');
    }

    #[Route('/booking/migrate', name: 'booking_migrate')]
    public function migrateData(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Необходимо авторизоваться для миграции данных');
            return $this->redirectToRoute('app_login');
        }

        try {
            $migratedCount = $this->dataMigrator->migrateFromCsvToDb($user->getId());
            $this->addFlash('success', "Успешно мигрировано записей: {$migratedCount}");
        } catch (\Exception $e) {
            $this->addFlash('error', 'Ошибка при миграции: ' . $e->getMessage());
        }

        return $this->redirectToRoute('booking_index');
    }

    #[Route('/bookings', name: 'booking_index')]
    public function index(Request $request): Response
    {
        $filters = $request->query->all();
        $storageType = $request->cookies->get('storage_type', 'csv');

        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($storageType === 'db') {
            $bookings = $this->repository->getAllBookings($filters, $user->getId());
        } else {
            $bookings = $this->repository->getAllBookingsFromCsv($filters, $user->getId());
        }

        return $this->render('booking/list.html.twig', [
            'bookings' => $bookings,
            'storage_type' => $storageType,
            'filters' => $filters,
        ]);
    }

    #[Route('/bookings/export/pdf', name: 'booking_export_pdf')]
    public function exportPdf(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $storageType = $request->cookies->get('storage_type', 'csv');
        $filters = $request->query->all();

        if ($storageType === 'db') {
            $bookings = $this->repository->getAllBookings($filters, $user->getId());
        } else {
            $bookings = $this->repository->getAllBookingsFromCsv($filters, $user->getId());
        }

        $mpdf = new Mpdf();
        $html = $this->renderView('booking/report_pdf.html.twig', [
            'bookings' => $bookings,
        ]);
        $mpdf->WriteHTML($html);

        return new Response($mpdf->Output('bookings.pdf', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="bookings.pdf"',
        ]);
    }

    #[Route('/bookings/export/excel', name: 'booking_export_excel')]
    public function exportExcel(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $storageType = $request->cookies->get('storage_type', 'csv');
        $filters = $request->query->all();

        if ($storageType === 'db') {
            $bookings = $this->repository->getAllBookings($filters, $user->getId());
        } else {
            $bookings = $this->repository->getAllBookingsFromCsv($filters, $user->getId());
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Заголовки
        $sheet->fromArray(['ID', 'Имя', 'Услуга', 'Фотограф', 'Дата', 'Пользователь'], null, 'A1');

        $row = 2;
        foreach ($bookings as $booking) {
            $sheet->setCellValue("A{$row}", $booking->getId());
            $sheet->setCellValue("B{$row}", $booking->getName());
            $sheet->setCellValue("C{$row}", $booking->getService());
            $sheet->setCellValue("D{$row}", $booking->getPhotographer());
            $sheet->setCellValue("E{$row}", $booking->getDate()->format('Y-m-d'));
            $sheet->setCellValue("F{$row}", $booking->getUserId());
            $row++;
        }

        $writer = new Xlsx($spreadsheet);

        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        return $this->file($tempFile, 'bookings.xlsx', ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    #[Route('/bookings/export/csv', name: 'booking_export_csv')]
    public function exportCsv(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $storageType = $request->cookies->get('storage_type', 'csv');
        $filters = $request->query->all();

        if ($storageType === 'db') {
            $bookings = $this->repository->getAllBookings($filters, $user->getId());
        } else {
            $bookings = $this->repository->getAllBookingsFromCsv($filters, $user->getId());
        }

        $handle = fopen('php://temp', 'r+');

        // Заголовки CSV
        fputcsv($handle, ['ID', 'Имя', 'Услуга', 'Фотограф', 'Дата', 'Пользователь']);

        foreach ($bookings as $booking) {
            fputcsv($handle, [
                $booking->getId(),
                $booking->getName(),
                $booking->getService(),
                $booking->getPhotographer(),
                $booking->getDate()->format('Y-m-d'),
                $booking->getUserId(),
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return new Response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="bookings.csv"',
        ]);
    }
}

<?php

namespace App\Controller;

// Сущности поликлиники
use App\Entity\Visit;
use App\Entity\Employee;
use App\Entity\Patient;
use App\Entity\Service;

// Медицинская услуга
use App\Entity\Specialty;
use App\Entity\User;

// Репозитории поликлиники
use App\Repository\VisitRepository;
use App\Repository\EmployeeRepository;
use App\Repository\ServiceRepository as MedicalServiceRepository;

// Переименовываем, чтобы не конфликтовать с Request->Service
use App\Repository\SpecialtyRepository;
use App\Repository\PatientRepository;

// Enum
use App\Enum\VisitStatus;

// Предположим, что есть такой Enum для статусов визита
// Остальные use
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

// Можно оставить, если будет ручная валидация списков
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Exception;
use DateTimeImmutable;

// Для работы с датой и временем

#[Route('/visit')] // Изменено с /booking
class VisitController extends AbstractController
{
    private VisitRepository $visitRepository;
    private EntityManagerInterface $entityManager;
    private SessionInterface $session;
    private UrlGeneratorInterface $urlGenerator;
    private KernelInterface $kernel;
    private MedicalServiceRepository $medicalServiceRepository; // Репозиторий для медицинских услуг
    private EmployeeRepository $employeeRepository;             // Репозиторий для сотрудников (врачей)
    private SpecialtyRepository $specialtyRepository;           // Репозиторий для специальностей
    private PatientRepository $patientRepository;               // Репозиторий для пациентов

    public function __construct(
        VisitRepository          $visitRepository,
        EntityManagerInterface   $entityManager,
        SessionInterface         $session,
        UrlGeneratorInterface    $urlGenerator,
        KernelInterface          $kernel,
        MedicalServiceRepository $medicalServiceRepository,
        EmployeeRepository       $employeeRepository,
        SpecialtyRepository      $specialtyRepository,
        PatientRepository        $patientRepository
    )
    {
        $this->visitRepository = $visitRepository;
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->urlGenerator = $urlGenerator;
        $this->kernel = $kernel;
        $this->medicalServiceRepository = $medicalServiceRepository;
        $this->employeeRepository = $employeeRepository;
        $this->specialtyRepository = $specialtyRepository;
        $this->patientRepository = $patientRepository;
    }

    private function getCurrentUser(): ?User
    {
        $userId = $this->session->get('user_id');
        if (!$userId) {
            return null;
        }
        return $this->entityManager->getRepository(User::class)->find($userId);
    }

    private function getCurrentPatient(): ?Patient
    {
        $user = $this->getCurrentUser();
        if ($user && $user->getPatientProfile()) {
            return $user->getPatientProfile();
        }
        // Или если администратор может записывать любого пациента
        // $patientId = $this->session->get('selected_patient_id_for_visit');
        // if ($patientId) return $this->patientRepository->find($patientId);
        return null;
    }


    private function requireLogin(): ?RedirectResponse
    {
        if (!$this->getCurrentUser()) {
            $this->addFlash('error', 'Для доступа к этой странице необходимо авторизоваться.');
            return new RedirectResponse($this->urlGenerator->generate('auth_login_form'));
        }
        return null;
    }

    // Новый метод для проверки, является ли пользователь пациентом
    private function requirePatientProfile(): ?RedirectResponse
    {
        $loginRedirect = $this->requireLogin();
        if ($loginRedirect) {
            return $loginRedirect;
        }

        /** @var User $currentUser */
        $currentUser = $this->getCurrentUser(); // Гарантированно есть после requireLogin

        if (!$currentUser->getPatientProfile()) { // Проверяем профиль пациента у текущего пользователя
            $this->addFlash('warning', 'Для записи на прием необходимо заполнить профиль пациента.');

            // Сохраняем текущий URL, чтобы вернуться сюда после создания профиля
            // Symfony Security делает это автоматически для защищенных ресурсов,
            // но здесь мы делаем это вручную для нашего кастомного флоу.
            $request = $this->container->get('request_stack')->getCurrentRequest();
            if ($request && $request->attributes->get('_route') !== 'patient_profile_create_form') { // Не сохраняем, если мы уже на странице профиля
                $request->getSession()->set('_security.main.target_path', $request->getUri());
            }

            return $this->redirectToRoute('patient_profile_create_form');
        }
        return null;
    }


    #[Route('/form', name: 'visit_form_show', methods: ['GET'])]
    public function showForm(Request $request): Response
    {
        if ($redirect = $this->requirePatientProfile()) { // Пациент должен иметь профиль для записи
            return $redirect;
        }

        $errors = $this->session->getFlashBag()->get('form_errors', []); // Оставляем совместимость с текущей передачей ошибок
        $formDataFromSession = $this->session->getFlashBag()->get('form_data');
        $currentData = $formDataFromSession[0] ?? [];

        // Получаем доступные специальности, услуги и сотрудников (врачей)
        $availableSpecialties = $this->specialtyRepository->findAll(); // Предполагается, что все специальности активны
        $availableServices = $this->medicalServiceRepository->findAll(); // Все медицинские услуги

        // Врачи будут загружаться динамически через AJAX или передаваться все, если их не много
        // Для простоты пока передадим всех, но это может быть не оптимально
        $availableEmployees = $this->employeeRepository->findAllActive(); // Предположим есть метод для получения активных врачей

        return $this->render('visit/form.html.twig', [ // Изменен путь к шаблону
            'errors' => $errors,
            'data' => $currentData,
            'availableSpecialties' => $availableSpecialties,
            'availableServices' => $availableServices,
            'availableEmployees' => $availableEmployees, // Для выбора врача (может быть отфильтровано по специальности на фронте)
        ]);
    }

    #[Route('/submit', name: 'visit_form_submit', methods: ['POST'])]
    public function submitForm(Request $request): Response
    {
        if ($redirect = $this->requirePatientProfile()) {
            return $redirect;
        }

        $errors = [];
        $data = $request->request->all();
        $patient = $this->getCurrentPatient();

        if (!$patient) { // Дополнительная проверка, хотя requirePatientProfile должен был отсечь
            $this->addFlash('error', 'Профиль пациента не найден. Пожалуйста, перезайдите.');
            return $this->redirectToRoute('visit_form_show');
        }

        // Валидация даты и времени
        $visitDateTimeInput = $data['visit_datetime'] ?? ''; // Например, '2024-12-31 14:30'
        $visitDateTime = null;
        if (empty($visitDateTimeInput)) {
            $errors['visit_datetime'] = "Дата и время приема не могут быть пустыми.";
        } else {
            try {
                $visitDateTime = new DateTimeImmutable($visitDateTimeInput);
                if ($visitDateTime < new DateTimeImmutable('now')) {
                    $errors['visit_datetime'] = "Дата и время приема не могут быть в прошлом.";
                }
                // TODO: Добавить проверку на рабочее время поликлиники, доступность врача в это время (по расписанию)
            } catch (Exception $e) {
                $errors['visit_datetime'] = "Неверный формат даты и времени приема.";
            }
        }

        // Валидация сотрудника (врача)
        $employeeId = $data['employee_id'] ?? null;
        $employee = null;
        if (empty($employeeId)) {
            $errors['employee'] = "Необходимо выбрать врача.";
        } else {
            $employee = $this->employeeRepository->find((int)$employeeId);
            if (!$employee) {
                $errors['employee'] = "Выбранный врач не найден.";
            }
            // TODO: Проверить, соответствует ли выбранный врач выбранной специальности, если специальность тоже выбиралась
        }

        // Валидация медицинской услуги (опционально, если есть поле)
        $serviceId = $data['service_id'] ?? null;
        $medicalService = null;
        if (!empty($serviceId)) { // Если услуга обязательна, убрать !empty
            $medicalService = $this->medicalServiceRepository->find((int)$serviceId);
            if (!$medicalService) {
                $errors['service'] = "Выбранная медицинская услуга не найдена.";
            }
        } else {
            // Если услуга обязательна для записи
            // $errors['service'] = "Необходимо выбрать медицинскую услугу.";
        }


        if (empty($errors)) {
            // Создаем новый Visit
            $visit = new Visit(
                $patient,
                $employee,
                $visitDateTime,
                VisitStatus::PLANNED // Статус по умолчанию "Запланирован"
            // $medicalService, // Если услуга часть конструктора Visit
            // discount // Если скидка применяется при записи
            );
            // Если услуга не в конструкторе, а как отдельное свойство или связь ManyToMany:
            // if ($medicalService) { $visit->addServiceRendered($medicalService); }


            try {
                $this->entityManager->persist($visit);
                $this->entityManager->flush();
            } catch (Exception $e) {
                $this->addFlash('form_errors', "Ошибка сохранения записи: " . $e->getMessage());
                $this->session->getFlashBag()->add('form_data', $data); // Сохраняем данные формы
                return $this->redirectToRoute('visit_form_show');
            }

            return $this->redirectToRoute('visit_success');
        }

        // Передача ошибок в виде массива с ключами
        $this->session->getFlashBag()->add('form_errors', $errors);
        $this->session->getFlashBag()->add('form_data', $data);
        return $this->redirectToRoute('visit_form_show');
    }

    #[Route('/success', name: 'visit_success', methods: ['GET'])]
    public function showSuccess(): Response
    {
        if ($redirect = $this->requireLogin()) { // Достаточно просто логина для страницы успеха
            return $redirect;
        }
        // Можно передать ID визита или другую информацию на страницу успеха
        return $this->render('visit/sucсess.html.twig'); // Изменен путь к шаблону
    }


    #[Route('/list', name: 'visit_list', methods: ['GET'])]
    public function showVisits(Request $request): Response
    {


        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $currentUser = $this->getCurrentUser(); // Гарантированно не null после requireLogin
        $patientProfile = $currentUser->getPatientProfile();
        $employeeProfile = $currentUser->getEmployeeProfile();

        $filters = [
            'patient_name' => $request->query->get('filter_patient_name', ''),
            'employee_id' => $request->query->get('filter_employee_id', ''),
            'specialty_id' => $request->query->get('filter_specialty_id', ''),
            'service_id' => $request->query->get('filter_service_id', ''),
            'date_from' => $request->query->get('filter_date_from', ''),
            'date_to' => $request->query->get('filter_date_to', ''),
            'status' => $request->query->get('filter_status', '')
        ];

        $criteria = [];
        // Заполняем $criteria из $filters как и раньше
        if (!empty($filters['date_from'])) $criteria['dateFrom'] = new DateTimeImmutable($filters['date_from']);
        if (!empty($filters['date_to'])) $criteria['dateTo'] = (new DateTimeImmutable($filters['date_to']))->setTime(23, 59, 59);
        if (!empty($filters['employee_id'])) $criteria['employee'] = (int)$filters['employee_id'];
        if (!empty($filters['specialty_id'])) $criteria['specialty'] = (int)$filters['specialty_id'];
        if (!empty($filters['service_id'])) $criteria['service'] = (int)$filters['service_id'];
        if (!empty($filters['status']) && $statusEnum = VisitStatus::tryFrom($filters['status'])) {
            $criteria['status'] = $statusEnum;
        }


        $canViewVisits = false; // Флаг, может ли пользователь вообще просматривать записи

        if ($this->isGranted('ROLE_ADMIN')) {
            $canViewVisits = true;
            // Администратор видит все, может фильтровать по имени пациента
            if (!empty($filters['patient_name'])) $criteria['patientName'] = $filters['patient_name'];
        } elseif ($this->isGranted('ROLE_DOCTOR') && $employeeProfile) {
            $canViewVisits = true;
            $criteria['employee'] = $employeeProfile->getId();
            if (!empty($filters['patient_name'])) $criteria['patientName'] = $filters['patient_name'];
        } elseif ($this->isGranted('ROLE_PATIENT') && $patientProfile) {
            $canViewVisits = true;
            $criteria['patient'] = $patientProfile->getId();
            // Пациент не может фильтровать по имени другого пациента или по врачу/специальности (если не предусмотрено)
            // Убираем фильтры, которые не должны быть доступны пациенту
            unset($criteria['patientName'], $criteria['employee'], $criteria['specialty']);
        }

        $visits = [];
        if ($canViewVisits) {
            try {
                $visits = $this->visitRepository->findVisitsByCriteria($criteria);
            } catch (Exception $e) {
                $this->addFlash('error', "Ошибка при загрузке записей на прием: " . $e->getMessage());
                // $visits останется пустым
            }
        } else {
            // Если $canViewVisits остался false, значит, пользователь залогинен,
            // но его роль или состояние профиля не позволяют видеть записи.
            // Это более специфичная ситуация, чем просто "не удалось определить права".
            // Можно либо ничего не показывать, либо дать более точное сообщение.
            // Например, если это пользователь с ROLE_USER, но без профиля пациента/врача.
            // Для простоты, если пользователь не админ, не врач с профилем и не пациент с профилем,
            // он не увидит записи. Сообщение "Записей не найдено" будет выведено шаблоном.
            // Если же нужно явное сообщение об отсутствии прав, то:
            // $this->addFlash('info', 'У вас нет прав для просмотра списка записей, или ваш профиль не настроен.');
        }

        return $this->render('visit/list.html.twig', [
            'visits' => $visits,
            'filters' => $filters,
            'availableSpecialties' => $this->specialtyRepository->findAll(),
            'availableEmployees' => $this->employeeRepository->findAllActive(),
            'availableServices' => $this->medicalServiceRepository->findAll(),
            'availableStatuses' => VisitStatus::cases()
        ]);
    }

    private function getFilteredVisits(Request $request): array
    {
        // Логика аналогична showVisits, но только для получения данных
        $currentUser = $this->getCurrentUser();
        if (!$currentUser) return [];

        $patientProfile = $currentUser->getPatientProfile();
        $employeeProfile = $currentUser->getEmployeeProfile();

        $filters = [ /* ... как в showVisits ... */
            'patient_name' => $request->query->get('filter_patient_name', ''),
            'employee_id' => $request->query->get('filter_employee_id', ''),
            'specialty_id' => $request->query->get('filter_specialty_id', ''),
            'service_id' => $request->query->get('filter_service_id', ''),
            'date_from' => $request->query->get('filter_date_from', ''),
            'date_to' => $request->query->get('filter_date_to', ''),
            'status' => $request->query->get('filter_status', '')
        ];

        $criteria = [];
        if (!empty($filters['date_from'])) $criteria['dateFrom'] = new DateTimeImmutable($filters['date_from']);
        if (!empty($filters['date_to'])) $criteria['dateTo'] = (new DateTimeImmutable($filters['date_to']))->setTime(23, 59, 59);
        if (!empty($filters['employee_id'])) $criteria['employee'] = (int)$filters['employee_id'];
        if (!empty($filters['specialty_id'])) $criteria['specialty'] = (int)$filters['specialty_id'];
        if (!empty($filters['service_id'])) $criteria['service'] = (int)$filters['service_id'];
        if (!empty($filters['status']) && $statusEnum = VisitStatus::tryFrom($filters['status'])) {
            $criteria['status'] = $statusEnum;
        }


        if ($this->isGranted('ROLE_PATIENT') && $patientProfile) {
            $criteria['patient'] = $patientProfile->getId();
        } elseif ($this->isGranted('ROLE_DOCTOR') && $employeeProfile) {
            $criteria['employee'] = $employeeProfile->getId();
            if (!empty($filters['patient_name'])) $criteria['patientName'] = $filters['patient_name'];
        } elseif ($this->isGranted('ROLE_ADMIN')) {
            if (!empty($filters['patient_name'])) $criteria['patientName'] = $filters['patient_name'];
        } else {
            return [];
        }

        try {
            return $this->visitRepository->findVisitsByCriteriaForReport($criteria); // Метод может возвращать массив данных, а не сущностей
        } catch (Exception $e) {
            $this->addFlash('error', "Ошибка при получении отфильтрованных записей для отчета: " . $e->getMessage());
            return [];
        }
    }

    private function renderPdfVisitHtml(array $visitsData): string // Изменено имя и параметр
    {
        // Убедитесь, что pdf_template.html.twig адаптирован под данные Visit
        return $this->renderView('visit/pdf_template.html.twig', [
            'visits' => $visitsData,
        ]);
    }

    #[Route('/report/pdf', name: 'visit_report_pdf', methods: ['GET'])]
    public function generatePdfReport(Request $request): Response
    {
        if ($redirect = $this->requireLogin()) return $redirect;

        $visitsData = $this->getFilteredVisits($request);
        if (empty($visitsData)) {
            $this->addFlash('info', 'Нет данных для генерации PDF отчета.');
            return $this->redirectToRoute('visit_list');
        }

        $html = $this->renderPdfVisitHtml($visitsData);
        $tempDir = $this->kernel->getCacheDir() . '/mpdf_tmp';

        if (!is_dir($tempDir)) @mkdir($tempDir, 0775, true); // @ для подавления ошибки, если директория уже создана другим процессом
        if (!is_writable($tempDir)) {
            $this->addFlash('error', "Директория для временных файлов mPDF недоступна для записи: $tempDir");
            return $this->redirectToRoute('visit_list');
        }

        try {
            $mpdf = new Mpdf([
                'tempDir' => $tempDir, 'mode' => 'utf-8', 'format' => 'A4',
                'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 15, 'margin_bottom' => 15,
            ]);
            // Для кириллицы может потребоваться настройка шрифтов
            $mpdf->SetFont('dejavusans'); // Или другой шрифт, поддерживающий кириллицу
            $mpdf->WriteHTML($html);
            return new Response($mpdf->Output('visits_report.pdf', 'S'), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="visits_report.pdf"',
            ]);
        } catch (MpdfException $e) {
            $this->addFlash('error', 'Ошибка генерации PDF: ' . $e->getMessage());
            error_log('MPDF Error: ' . $e->getMessage() . ' | TempDir: ' . $tempDir . ' | Writable: ' . is_writable($tempDir));
            return $this->redirectToRoute('visit_list');
        }
    }

    #[Route('/report/excel', name: 'visit_report_excel', methods: ['GET'])]
    public function generateExcelReport(Request $request): Response
    {
        if ($redirect = $this->requireLogin()) return $redirect;

        $visitsData = $this->getFilteredVisits($request); // Ожидаем массив ассоциативных массивов
        if (empty($visitsData)) {
            $this->addFlash('info', 'Нет данных для генерации Excel отчета.');
            return $this->redirectToRoute('visit_list');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // Заголовки для отчета по визитам
        $sheet->setCellValue('A1', 'ID Визита')
            ->setCellValue('B1', 'Пациент')
            ->setCellValue('C1', 'Врач')
            ->setCellValue('D1', 'Специальность')
            ->setCellValue('E1', 'Дата и Время')
            ->setCellValue('F1', 'Статус')
            ->setCellValue('G1', 'Мед. Услуга'); // Если есть

        $row = 2;
        foreach ($visitsData as $visit) { // $visit теперь это массив данных
            $sheet->setCellValue('A' . $row, $visit['id'] ?? '');
            $sheet->setCellValue('B' . $row, $visit['patientName'] ?? ''); // Предполагается, что getFilteredVisits возвращает это поле
            $sheet->setCellValue('C' . $row, $visit['employeeName'] ?? '');
            $sheet->setCellValue('D' . $row, $visit['specialtyName'] ?? '');
            $sheet->setCellValue('E' . $row, $visit['dateTime'] ?? ''); // Отформатированная дата и время
            $sheet->setCellValue('F' . $row, $visit['status'] ?? ''); // Текстовое представление статуса
            $sheet->setCellValue('G' . $row, $visit['serviceName'] ?? '');
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="visits_report.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');
        return $response;
    }

    #[Route('/report/csv', name: 'visit_report_csv', methods: ['GET'])]
    public function generateCsvReport(Request $request): Response
    {
        if ($redirect = $this->requireLogin()) return $redirect;

        $visitsData = $this->getFilteredVisits($request);
        if (empty($visitsData)) {
            $this->addFlash('info', 'Нет данных для генерации CSV отчета.');
            return $this->redirectToRoute('visit_list');
        }

        $response = new StreamedResponse();
        $response->setCallback(function () use ($visitsData) {
            $output = fopen('php://output', 'w');
            // Заголовки для CSV
            fputcsv($output, ['ID Визита', 'Пациент', 'Врач', 'Специальность', 'Дата и Время', 'Статус', 'Мед. Услуга']);

            foreach ($visitsData as $visit) {
                fputcsv($output, [
                    $visit['id'] ?? '',
                    $visit['patientName'] ?? '',
                    $visit['employeeName'] ?? '',
                    $visit['specialtyName'] ?? '',
                    $visit['dateTime'] ?? '',
                    $visit['status'] ?? '',
                    $visit['serviceName'] ?? ''
                ]);
            }
            fclose($output);
        });
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8'); // Добавляем charset
        $response->headers->set('Content-Disposition', 'attachment; filename="visits_report.csv"');
        return $response;
    }
}
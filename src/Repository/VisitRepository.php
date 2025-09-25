<?php

namespace App\Repository;

use App\Entity\Visit;
use App\Entity\Patient;
use App\Entity\Employee;
use App\Entity\Specialty;
use App\Entity\Service;

use Doctrine\ORM\Query; // <-- ДОБАВЬТЕ ЭТОТ USE
use App\Enum\VisitStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use DateTimeImmutable;

/**
 * @extends ServiceEntityRepository<Visit>
 *
 * @method Visit|null find($id, $lockMode = null, $lockVersion = null)
 * @method Visit|null findOneBy(array $criteria, array $orderBy = null)
 * @method Visit[]    findAll()
 * @method Visit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VisitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Visit::class);
    }

    public function save(Visit $visit, bool $flush = true): void
    {
        $this->getEntityManager()->persist($visit);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Visit $visit, bool $flush = true): void
    {
        $this->getEntityManager()->remove($visit);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Query
     */
    public function findVisitsByCriteria(array $criteria = [], array $orderBy = null): Query
    {
        try {
            // <-- ИЗМЕНЕНО: Добавлен 'rs' в select и join для услуг -->
            $qb = $this->createQueryBuilder('v')
                ->addSelect('p', 'e', 's', 'rs')
                ->leftJoin('v.patient', 'p')
                ->leftJoin('v.employee', 'e')
                ->leftJoin('e.specialty', 's')
                ->leftJoin('v.renderedServices', 'rs');

            if (isset($criteria['patient'])) {
                $qb->andWhere('p.id = :patientId')
                    ->setParameter('patientId', $criteria['patient']);
            }

            if (!empty($criteria['patientName'])) {
                $qb->andWhere($qb->expr()->orX(
                    $qb->expr()->like('LOWER(p.firstName)', 'LOWER(:patientName)'),
                    $qb->expr()->like('LOWER(p.lastName)', 'LOWER(:patientName)'),
                    $qb->expr()->like('LOWER(CONCAT(p.lastName, \' \', p.firstName))', 'LOWER(:patientName)'),
                    $qb->expr()->like('LOWER(CONCAT(p.firstName, \' \', p.lastName))', 'LOWER(:patientName)')
                ))
                    ->setParameter('patientName', '%' . trim($criteria['patientName']) . '%');
            }

            if (!empty($criteria['employee'])) {
                $qb->andWhere('e.id = :employeeId')
                    ->setParameter('employeeId', (int)$criteria['employee']);
            }

            if (!empty($criteria['specialty'])) {
                $qb->andWhere('s.id = :specialtyId')
                    ->setParameter('specialtyId', (int)$criteria['specialty']);
            }

            // <-- ИЗМЕНЕНО: Добавлен фильтр по услуге -->
            if (!empty($criteria['service'])) {
                $qb->andWhere(':serviceId MEMBER OF v.renderedServices')
                    ->setParameter('serviceId', (int)$criteria['service']);
            }

            if (isset($criteria['dateFrom']) && $criteria['dateFrom'] instanceof DateTimeImmutable) {
                $qb->andWhere('v.dateAndTime >= :dateFrom')
                    ->setParameter('dateFrom', $criteria['dateFrom']);
            }

            if (isset($criteria['dateTo']) && $criteria['dateTo'] instanceof DateTimeImmutable) {
                $qb->andWhere('v.dateAndTime <= :dateTo')
                    ->setParameter('dateTo', $criteria['dateTo']);
            }

            if (isset($criteria['status']) && $criteria['status'] instanceof VisitStatus) {
                $qb->andWhere('v.status = :status')
                    ->setParameter('status', $criteria['status']->value);
            }

            if ($orderBy === null) {
                $qb->orderBy('v.dateAndTime', 'DESC')->addOrderBy('v.id', 'DESC');
            } else {
                foreach ($orderBy as $field => $direction) {
                    $qb->addOrderBy($field, $direction);
                }
            }

            return $qb->getQuery();

        } catch (Exception $e) {
            throw new Exception("Ошибка при получении записей из базы: " . $e->getMessage(), 0, $e);
        }
    }

    public function findVisitsByCriteriaForReport(array $criteria = []): array
    {
        try {
            // Убираем GROUP_CONCAT и получаем данные без группировки
            $qb = $this->createQueryBuilder('v')
                ->select(
                    'v.id', 'v.dateAndTime', 'v.status',
                    'p.firstName as patientFirstName', 'p.lastName as patientLastName', 'p.middleName as patientMiddleName',
                    'e.firstName as employeeFirstName', 'e.lastName as employeeLastName', 'e.middleName as employeeMiddleName',
                    's.name as specialtyName'
                )
                ->leftJoin('v.patient', 'p')
                ->leftJoin('v.employee', 'e')
                ->leftJoin('e.specialty', 's');

            // Применение фильтров
            if (isset($criteria['patient'])) {
                $qb->andWhere('p.id = :patientId')->setParameter('patientId', $criteria['patient']);
            }
            if (!empty($criteria['patientName'])) {
                $qb->andWhere($qb->expr()->orX(
                    $qb->expr()->like('LOWER(p.firstName)', 'LOWER(:patientName)'),
                    $qb->expr()->like('LOWER(p.lastName)', 'LOWER(:patientName)'),
                    $qb->expr()->like('LOWER(CONCAT(p.lastName, \' \', p.firstName))', 'LOWER(:patientName)'),
                    $qb->expr()->like('LOWER(CONCAT(p.firstName, \' \', p.lastName))', 'LOWER(:patientName)')
                ))->setParameter('patientName', '%' . trim($criteria['patientName']) . '%');
            }
            if (!empty($criteria['employee'])) {
                $qb->andWhere('e.id = :employeeId')->setParameter('employeeId', (int)$criteria['employee']);
            }
            if (!empty($criteria['specialty'])) {
                $qb->andWhere('s.id = :specialtyId')->setParameter('specialtyId', (int)$criteria['specialty']);
            }
            if (!empty($criteria['service'])) {
                $qb->andWhere(':serviceId MEMBER OF v.renderedServices')
                    ->setParameter('serviceId', (int)$criteria['service']);
            }
            if (isset($criteria['dateFrom']) && $criteria['dateFrom'] instanceof DateTimeImmutable) {
                $qb->andWhere('v.dateAndTime >= :dateFrom')->setParameter('dateFrom', $criteria['dateFrom']);
            }
            if (isset($criteria['dateTo']) && $criteria['dateTo'] instanceof DateTimeImmutable) {
                $qb->andWhere('v.dateAndTime <= :dateTo')->setParameter('dateTo', $criteria['dateTo']);
            }
            if (isset($criteria['status']) && $criteria['status'] instanceof VisitStatus) {
                $qb->andWhere('v.status = :status')->setParameter('status', $criteria['status']->value);
            }

            $qb->orderBy('v.dateAndTime', 'ASC');
            $results = $qb->getQuery()->getArrayResult();

            // Получаем услуги отдельно для каждого визита
            $visitIds = array_column($results, 'id');
            $services = [];

            if (!empty($visitIds)) {
                $servicesQuery = $this->getEntityManager()->createQueryBuilder()
                    ->select('v.id as visitId', 's.name as serviceName')
                    ->from(Visit::class, 'v')
                    ->leftJoin('v.renderedServices', 's')
                    ->where('v.id IN (:visitIds)')
                    ->setParameter('visitIds', $visitIds)
                    ->getQuery()
                    ->getArrayResult();

                // Группируем услуги по ID визита
                foreach ($servicesQuery as $service) {
                    if ($service['serviceName']) {
                        $services[$service['visitId']][] = $service['serviceName'];
                    }
                }
            }

            return array_map(function ($row) use ($services) {
                $patientFullName = trim(($row['patientLastName'] ?? '') . ' ' . ($row['patientFirstName'] ?? '') . ' ' . ($row['patientMiddleName'] ?? ''));
                $employeeFullName = trim(($row['employeeLastName'] ?? '') . ' ' . ($row['employeeFirstName'] ?? '') . ' ' . ($row['employeeMiddleName'] ?? ''));

                // Исправляем обработку статуса
                $statusValue = null;
                if (isset($row['status'])) {
                    if ($row['status'] instanceof VisitStatus) {
                        $statusValue = $row['status'];
                    } elseif (is_string($row['status'])) {
                        $statusValue = VisitStatus::tryFrom($row['status']);
                    }
                }

                // Получаем список услуг для текущего визита
                $visitServices = $services[$row['id']] ?? [];
                $serviceNames = !empty($visitServices) ? implode(', ', $visitServices) : '';

                return [
                    'id' => $row['id'],
                    'dateTime' => $row['dateAndTime'] instanceof DateTimeImmutable ? $row['dateAndTime']->format('d.m.Y H:i') : null,
                    'status' => $this->translateVisitStatus($statusValue),
                    'patientName' => $patientFullName ?: 'N/A',
                    'employeeName' => $employeeFullName ?: 'N/A',
                    'specialtyName' => $row['specialtyName'] ?? 'N/A',
                    'serviceName' => $serviceNames,
                ];
            }, $results);

        } catch (Exception $e) {
            throw new Exception("Ошибка при получении данных для отчета: " . $e->getMessage(), 0, $e);
        }
    }

    private function translateVisitStatus(?VisitStatus $status): ?string
    {
        if ($status === null) return null;
        return match ($status) {
            VisitStatus::PLANNED => 'Запланирован',
            VisitStatus::COMPLETED => 'Завершен',
            VisitStatus::CANCELLED => 'Отменен',
            VisitStatus::MISSED => 'Пропущен',
        };
    }
    public function findVisitsByEmployeeAndDate(Employee $employee, \DateTimeImmutable $date): array
    {
        $startOfDay = $date->setTime(0, 0, 0);
        $endOfDay = $date->setTime(23, 59, 59);

        return $this->createQueryBuilder('v')
            ->andWhere('v.employee = :employee')
            ->andWhere('v.dateAndTime BETWEEN :start AND :end')
            ->setParameter('employee', $employee)
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay)
            ->getQuery()
            ->getResult();
    }
}
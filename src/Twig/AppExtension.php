<?php
// src/Twig/AppExtension.php
namespace App\Twig;

use App\Enum\VisitStatus;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('trans_visit_status', $this->translateVisitStatus(...)),
        ];
    }

    public function translateVisitStatus(?string $statusValue): string
    {
        if ($statusValue === null) return 'Неизвестно';
        $status = VisitStatus::tryFrom($statusValue);
        if ($status === null) return ucfirst($statusValue); // Если не удалось найти в Enum

        return match ($status) {
            VisitStatus::PLANNED => 'Запланирован',
            VisitStatus::COMPLETED => 'Завершен',
            VisitStatus::CANCELLED => 'Отменен',
            VisitStatus::MISSED => 'Пропущен',
            default => ucfirst($status->value),
        };
    }
}

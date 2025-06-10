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
            // Используем стандартный синтаксис для callable, который работает везде
            new TwigFilter('trans_visit_status', $this->translateVisitStatus(...)),
        ];
    }

    // Ваш метод translateVisitStatus абсолютно правильный, оставляем его без изменений
    public function translateVisitStatus(?string $statusValue): string
    {
        if ($statusValue === null) {
            return 'Неизвестно';
        }

        $status = VisitStatus::tryFrom($statusValue);
        if ($status === null) {
            return ucfirst($statusValue); // Если не удалось найти в Enum
        }

        return match ($status) {
            VisitStatus::PLANNED => 'Запланирован',
            VisitStatus::COMPLETED => 'Завершен',
            VisitStatus::CANCELLED => 'Отменен',
            VisitStatus::MISSED => 'Пропущен',
            // default здесь не нужен, так как tryFrom уже отсек все неизвестные случаи
        };
    }
}
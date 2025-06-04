<?php

namespace App\Enum;

enum VisitStatus: string
{
    case PLANNED = 'planned';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case MISSED = 'missed';

}
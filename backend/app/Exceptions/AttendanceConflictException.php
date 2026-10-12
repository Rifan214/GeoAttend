<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AttendanceConflictException extends ConflictHttpException
{
    public function __construct(string $message = 'Operasi presensi berkonflik dengan status saat ini.')
    {
        parent::__construct($message);
    }
}

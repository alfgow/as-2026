<?php

declare(strict_types=1);

namespace App\Helpers;

class DateHelper
{
    /**
     * Obtiene la fecha/hora actual en la zona horaria de México (CDMX),
     * formateada en ISO-8601 (ejemplo: 2025-09-15T17:45:33-06:00).
     */
    public static function nowMexico(): string
    {
        $dt = new \DateTime('now', new \DateTimeZone('America/Mexico_City'));
        return $dt->format(DATE_ATOM);
    }

    /**
     * Convierte una fecha dada a ISO-8601 en horario de México.
     * @param string $fecha Fecha original (ej: de MySQL: "2025-09-15 22:30:00")
     */
    public static function toMexico(string $fecha): string
    {
        $dt = new \DateTime($fecha, new \DateTimeZone('UTC'));
        $dt->setTimezone(new \DateTimeZone('America/Mexico_City'));
        return $dt->format(DATE_ATOM);
    }
}

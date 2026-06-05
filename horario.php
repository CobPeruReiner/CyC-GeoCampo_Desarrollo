<?php

date_default_timezone_set('America/Lima');

// Validacion solo hora
// function validarHorario() {
//     $horaActual = (int)date('H'); // Obtener la hora actual en formato 24 horas
//     $diaSemana = (int)date('N'); // Obtener el número del día de la semana (1=Lunes, 7=Domingo)

//     // Horarios de semana (lunes a viernes)
//     $inicioSemana = 10; // 7am
//     $finSemana = 20;   // 8pm (20 en formato 24 horas)

//     // Horarios de fin de semana (sábado y domingo)
//     $inicioFinSemana = 7;
//     $finFinSemana = 13;

//     // Si es un día de semana (lunes a viernes)
//     if ($diaSemana >= 1 && $diaSemana <= 5) {
//         return $horaActual >= $inicioSemana && $horaActual <= $finSemana;
//     }
//     // Si es fin de semana (sábado o domingo)
//     if ($diaSemana == 6 || $diaSemana == 7) {
//         return $horaActual >= $inicioFinSemana && $horaActual <= $finFinSemana;
//     }

//     return false;
// }

// Validacion hora y minutos
function validarHorario()
{
    $horaActual = (int)date('H'); // Obtener la hora actual en formato 24 horas
    $minutosActual = (int)date('i'); // Obtener los minutos actuales
    $horaMinutoActual = $horaActual * 60 + $minutosActual; // Convertir la hora actual a minutos

    $diaSemana = (int)date('N'); // Obtener el número del día de la semana (1=Lunes, 7=Domingo)

    // Horarios de semana (lunes a viernes). Flexible seconds
    $inicioSemana = 7 * 60 + 00; // 7 * 60 = horas, 00 = minutos
    $finSemana = 20 * 60 + 00;

    // Horarios de fin de semana (sábado y domingo)
    $inicioFinSemana = 7 * 60 + 00; // 7:10 AM en minutos (430 minutos)
    $finFinSemana = 13 * 60 + 00;   // 1:30 PM en minutos (810 minutos)

    // Si es un día de semana (lunes a viernes)
    if ($diaSemana >= 1 && $diaSemana <= 5) {
        return $horaMinutoActual >= $inicioSemana && $horaMinutoActual <= $finSemana;
    }
    // Si es fin de semana (sábado o domingo)
    if ($diaSemana == 6 || $diaSemana == 7) {
        return $horaMinutoActual >= $inicioFinSemana && $horaMinutoActual <= $finFinSemana;
    }

    return false;
}

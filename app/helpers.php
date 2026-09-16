<?php

if (! function_exists('get_estado_badge')) {
    function get_estado_badge(?string $estado): string
    {
        return match ($estado) {
            'INGRESADO' => '<span class="badge bg-secondary"><i class="fa-solid fa-user-clock me-1"></i> Ingresado</span>',
            'EN_TRANSCRIPCION' => '<span class="badge bg-primary"><i class="fa-solid fa-keyboard me-1"></i> En Transcripción</span>',
            'TRANSCRITO_COMPLETO' => '<span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i> Transcrito Completo</span>',
            'TRANSCRITO_PENDIENTE' => '<span class="badge bg-warning text-dark"><i class="fa-solid fa-triangle-exclamation me-1"></i> Con Pendientes</span>',
            'SIN_STOCK' => '<span class="badge bg-danger"><i class="fa-solid fa-boxes-packing me-1"></i> Sin Stock</span>',
            'ALISTADO' => '<span class="badge bg-info text-dark"><i class="fa-solid fa-box-open me-1"></i> Alistado</span>',
            'ENTREGADO' => '<span class="badge bg-dark"><i class="fa-solid fa-square-check me-1"></i> Entregado</span>',
            'CANCELADO' => '<span class="badge bg-outline-secondary">Cancelado</span>',
            default => '<span class="badge bg-light text-dark">'.e($estado).'</span>',
        };
    }
}

if (! function_exists('get_prioridad_badge')) {
    function get_prioridad_badge(?string $prioridad): string
    {
        return match ($prioridad) {
            'TERCERA_EDAD' => '<span class="badge bg-warning text-dark fw-bold"><i class="fa-solid fa-person-cane me-1"></i> 👴 Tercera Edad</span>',
            'EMBARAZADA' => '<span class="badge bg-danger text-white fw-bold"><i class="fa-solid fa-person-pregnant me-1"></i> 🤰 Embarazada</span>',
            'DISCAPACIDAD' => '<span class="badge bg-info text-dark fw-bold"><i class="fa-solid fa-wheelchair me-1"></i> ♿ Discapacidad</span>',
            'NIÑO_LACTANTE' => '<span class="badge bg-primary text-white fw-bold"><i class="fa-solid fa-baby me-1"></i> 👶 Niño / Lactante</span>',
            'OTRO_PREFERENCIAL' => '<span class="badge bg-warning text-dark fw-bold"><i class="fa-solid fa-star me-1"></i> ⭐ Preferencial</span>',
            default => '',
        };
    }
}

<?php

function mostrarEstrellas($rating) {
    // Redondea por si el promedio tiene decimales 
    $rating = round($rating);
    
    $html = '<div class="text-warning d-inline-block">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $html .= '<i class="bi bi-star-fill"></i>'; // Estrella llena
        } else {
            $html .= '<i class="bi bi-star"></i>';      // Estrella vacía
        }
    }
    $html .= ' <span class="text-muted small">(' . ($rating > 0 ? $rating : '0') . ')</span>';
    $html .= '</div>';
    return $html;
}
?>
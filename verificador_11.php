<?php
function verificador($rut) {
    // Remover possíveis pontos e traços no RUT
    $rut = preg_replace('/[^0-9]/', '', $rut);
    
    // Pegar apenas os primeiros 8 números (sem o dígito verificador)
    $rut = substr($rut, 0, 8);
    
    // Pesos fixos do cálculo (cíclicos: 2 a 7)
    $pesos = [2, 3, 4, 5, 6, 7];
    
    $soma = 0;
    $j = 0; // Índice dos pesos
    
    // Percorrer os dígitos do RUT da direita para a esquerda
    for ($i = strlen($rut) - 1; $i >= 0; $i--) {
        $soma += intval($rut[$i]) * $pesos[$j];
        $j = ($j + 1) % count($pesos); // Ciclar os pesos de 2 a 7
    }

    // Calcular módulo 11
    $resto = $soma % 11;
    $dv = 11 - $resto;

    // Regras para definir o DV final
    if ($dv == 11) {
        return '0';
    } elseif ($dv == 10) {
        return 'K';
    } else {
        return (string) $dv;
    }
}

?>
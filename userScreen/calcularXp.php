<?php
/**
 * Calcula o XP TOTAL acumulado necessário para alcançar um determinado nível.
 * O XP nunca é zerado — o threshold cresce exponencialmente.
 *
 * Nível 1 → 0 XP (começa aqui)
 * Nível 2 → 500 XP
 * Nível 3 → 500 + 600 = 1100 XP
 * Nível 4 → 500 + 600 + 720 = 1820 XP
 * ...e assim por diante (cada nível requer 1.2x mais que o anterior)
 */
function xpNecessario($nivel)
{
    $xpBase = 500;
    $fator = 1.2;
    $totalXp = 0;

    for ($i = 1; $i < $nivel; $i++) {
        $totalXp += round($xpBase * pow($fator, $i - 1));
    }

    return $totalXp;
}

/**
 * Retorna o XP necessário APENAS para o próximo nível (delta).
 * Usado para calcular a porcentagem da barra de progresso.
 */
function xpParaProximoNivel($nivel)
{
    $xpBase = 500;
    $fator = 1.2;
    return round($xpBase * pow($fator, $nivel - 1));
}

/**
 * Calcula o nível do usuário baseado no XP total acumulado.
 */
function calcularNivelPorXP($xpTotal)
{
    $nivel = 1;
    while ($xpTotal >= xpNecessario($nivel + 1)) {
        $nivel++;
    }
    return $nivel;
}

/**
 * Formata o XP para exibição:
 * - Abaixo de 1000: mostra o número inteiro (ex: 750)
 * - 1000 a 999999: mostra em "k" (ex: 1.5k, 150.8k)
 * - 1000000+: mostra em "M" (ex: 1.2M)
 */
function formatarXP($xp)
{
    $xp = (int) $xp;

    if ($xp < 1000) {
        return (string) $xp;
    }

    if ($xp < 1000000) {
        $valor = $xp / 1000;
        // Remove casas decimais desnecessárias (ex: 2.0k → 2k)
        if (fmod($valor, 1) == 0) {
            return number_format($valor, 0) . 'k';
        }
        return number_format($valor, 1, '.', '') . 'k';
    }

    $valor = $xp / 1000000;
    if (fmod($valor, 1) == 0) {
        return number_format($valor, 0) . 'M';
    }
    return number_format($valor, 1, '.', '') . 'M';
}

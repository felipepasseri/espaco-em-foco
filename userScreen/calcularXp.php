<?php
function xpNecessario($nivel)
{
    $xpBase = 500;
    $fator = 1.2;

    return round($xpBase * pow($fator, $nivel - 1));
}

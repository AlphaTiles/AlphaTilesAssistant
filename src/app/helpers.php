<?php

if (!function_exists('generateJavaScriptColorArray')) {
    function generateJavaScriptColorArray($enumClassName) {
        $reflection = new ReflectionClass($enumClassName);
        $constants = $reflection->getConstants();
        $values = [];
        foreach ($constants as $constant) {
            $enumClass = $enumClassName::from($constant->value);
            $values[$constant->value] = $enumClass->hexCode();
        }

        return $values;
    }
}
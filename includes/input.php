<?php

function getGetString(string $key, string $default = ''): string
{
    $value = filter_input(INPUT_GET, $key, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    return $value !== null ? trim($value) : $default;
}

function getGetInt(string $key, int $default = 0): int
{
    $value = filter_input(INPUT_GET, $key, FILTER_VALIDATE_INT);
    return $value !== false && $value !== null ? (int)$value : $default;
}

function getAllowedEnum(string $key, array $allowed, string $default = ''): string
{
    $value = getGetString($key, $default);
    return in_array($value, $allowed, true) ? $value : $default;
}

function getPostString(string $key, string $default = ''): string
{
    $value = filter_input(INPUT_POST, $key, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    return $value !== null ? trim($value) : $default;
}

<?php

From::_('page', static function (?string $value): array {
    if (!$value = n($value)) {
        return [];
    }
    if ('---' === rtrim($value)) {
        return ['content' => null];
    }
    if (3 === strspn($value, '-')) {
        $value = ltrim(substr($value, 3), "\n");
    }
    $v = explode("\n...\n", $value . "\n", 2);
    if (is_array($v[0] = From::YAML(trim($v[0], "\n"), true))) {
        $v[1] = trim($v[1] ?? "", "\n");
        return array_replace("" !== $v[1] ? ['content' => $v[1]] : [], $v[0]);
    }
    return ['content' => $value];
});
<?php

From::_('page', static function (?string $value, $array = false) {
    static $r;
    $r ??= function (array $v, $array) {
        return $array ? $v : (object) $v;
    };
    if (!$value = n($value)) {
        return $r([], $array);
    }
    if (0 !== strncmp($value, '---', 3)) {
        return $r(['content' => $value], $array);
    }
    $value = "\n" . ltrim(substr($value, 3), "\n") . "\n";
    $a = strpos($value, "\n...\n");
    $b = strpos($value, "\n---\n");
    if (false === $a && false === $b) {
        return $r(['content' => trim($value, "\n")], $array);
    }
    if (false === $a || (false !== $b && $b < $a)) {
        $c = $b;
    } else {
        $c = $a;
    }
    $content = substr($value, $c + 5);
    $lot = substr($value, 1, $c - 1);
    if (is_array($lot = From::YAML(trim($lot, "\n"), true))) {
        $content = trim($content, "\n");
        return $r(array_replace("" !== $content ? ['content' => $content] : [], $lot), $array);
    }
    return $r(['content' => trim($value, "\n")], $array);
});
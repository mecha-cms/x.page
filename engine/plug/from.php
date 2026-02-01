<?php

From::_('page', static function (?string $value, $array = false) {
    static $r;
    $r ??= function (array $v, $array) {
        return $array ? $v : o($v);
    };
    if (!$value = n($value)) {
        return $r([], $array);
    }
    if (0 !== strncmp($value, '---', 3)) {
        return $r(['content' => $value], $array);
    }
    $value = "\n" . ltrim(substr($value, 3), "\n") . "\n";
    // <https://yaml.org/spec/1.2.2#document-markers>
    if (false === ($n = strpos($value, "\n...\n"))) {
        // <https://jekyllrb.com/docs/front-matter>
        $n = strpos($value, "\n---\n");
    }
    if (false === $n) {
        return $r(['content' => trim($value, "\n")], $array);
    }
    $content = substr($value, $n + 5);
    $lot = substr($value, 1, $n - 1);
    if (is_array($lot = From::YAML(trim($lot, "\n"), true))) {
        $content = trim($content, "\n");
        return $r(array_replace("" !== $content ? ['content' => $content] : [], $lot), $array);
    }
    return $r(['content' => trim($value, "\n")], $array);
});
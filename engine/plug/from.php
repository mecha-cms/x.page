<?php

From::_('page', static function (?string $value, $array = false) {
    if (!$value = n($value)) {
        return null;
    }
    if (0 !== strncmp($value, '---', 3)) {
        return null;
    }
    $value = "\n" . trim(substr($value, 3), "\n") . "\n";
    // <https://yaml.org/spec/1.2.2#document-markers>
    if (false === ($n = strpos($value, "\n...\n"))) {
        // <https://jekyllrb.com/docs/front-matter>
        $n = strpos($value, "\n---\n");
    }
    if (false === $n) {
        return null;
    }
    $content = substr($value, $n + 5);
    $lot = substr($value, 1, $n - 1);
    if (!is_array($lot = From::YAML(trim($lot, "\n"), true))) {
        return null;
    }
    $content = trim($content, "\n");
    $lot = array_replace("" !== $content ? ['content' => $content] : [], $lot);
    return $array ? $lot : o($lot);
});
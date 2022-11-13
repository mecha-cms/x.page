<?php

From::_('page', static function (string $value, $eval = false) {
    if (0 !== strpos($value = n($value), YAML\SOH . "\n")) {
        // Add empty header
        $value = YAML\SOH . "\n" . YAML\EOT . "\n\n" . $value;
    }
    $v = From::YAML($value, '  ', true, $eval);
    return $v[0] + ['content' => $v["\t"] ?? null];
});

To::_('page', static function (array $value) {
    $content = $value['content'] ?? null;
    unset($value['content']);
    $value = [
        0 => $value,
        "\t" => $content
    ];
    return To::YAML($value, '  ', true);
});

function page(...$lot) {
    return Page::from(...$lot);
}
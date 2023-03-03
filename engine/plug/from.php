<?php

From::_('page', static function (?string $value, $eval = false) {
    if (!$value) {
        return [];
    }
    if (0 !== strpos($value = n($value), YAML\SOH . "\n")) {
        // Add empty header
        $value = YAML\SOH . "\n" . YAML\EOT . "\n\n" . $value;
    }
    $v = From::YAML($value, '  ', "\t", $eval);
    return $v[0] + ['content' => $v["\t"] ?? null];
});
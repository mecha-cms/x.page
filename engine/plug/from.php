<?php

From::_('page', static function (?string $value, $eval = false): array {
    if (!$value = n($value)) {
        return [];
    }
    if (0 !== strpos($value, YAML\SOH . "\n")) {
        // Make sure page header is present even if it is empty
        $value = YAML\SOH . "\n" . YAML\EOT . "\n\n" . $value;
    }
    $v = (array) From::YAML($value, '  ', P, $eval);
    return a($v[0]) + ['content' => $v[P] ?? null];
});
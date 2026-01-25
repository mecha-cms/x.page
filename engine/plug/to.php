<?php

To::_('description', static function (?string $value, $max = 200): ?string {
    if ("" === ($value ?? "")) {
        return null;
    }
    if (is_int($max)) {
        $max = [$max, '&#x2026;'];
    }
    $m = extension_loaded('mbstring');
    if (false !== strpos($value, '&') || false !== strpos($value, '<')) {
        $c = 0;
        $keep = P . implode(P, ['a', 'abbr', 'b', 'bdi', 'bdo', 'br', 'cite', 'code', 'data', 'del', 'dfn', 'em', 'i', 'ins', 'kbd', 'mark', 'q', 's', 'samp', 'small', 'span', 'strong', 'sub', 'sup', 'time', 'u', 'var', 'wbr']) . P;
        $r = "";
        $raw = ['audio', 'embed', 'noscript', 'script', 'style', 'textarea', 'video'];
        // <https://html.spec.whatwg.org/multipage/syntax.html#void-elements>
        $void = ['area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'];
        $stack = [];
        foreach (apart($value, $raw, $void) as $v) {
            if (-1 === $v[1]) {
                if ($c >= $max[0]) {
                    break;
                }
                $c += 2;
                $r .= $v[0] . ' ';
                continue;
            }
            if (0 === $v[1]) {
                $v[0] = trim(preg_replace('/\s+/', ' ', $v[0]));
                $c += $m ? mb_strlen($v[0]) : strlen($v[0]);
                if ("" !== $v[0]) {
                    $c += 1;
                    $r .= $v[0] . ' ';
                    if ($c > $max[0]) {
                        $r = $m ? mb_substr($r, 0, $max[0] - $c) : substr($r, 0, $max[0] - $c);
                        break;
                    }
                    continue;
                }
            }
            if (1 === $v[1]) {
                $k = rtrim(substr($v[0], 1, strcspn($v[0], " \n\r\t>", 1)), '/');
                if ('br' === $k || 'hr' === $k) {
                    if ($c >= $max[0]) {
                        break;
                    }
                    $c += 1;
                    $r .= ' ';
                    continue;
                }
                continue;
            }
            if (2 === $v[1]) {
                $k = rtrim(strtok(substr($v[0], 1), " \n\r\t>"), '/');
                if (false === strpos($keep, P . trim($k, '/') . P)) {
                    continue;
                }
                if ('/' === $k[0]) {
                    $r = trim($r) . $v[0] . ($c >= $max[0] ? "" : ' ');
                    array_pop($stack);
                    continue;
                }
                $r .= $v[0];
                $stack[] = $k;
                continue;
            }
        }
        $r = trim($r);
        while ($k = array_pop($stack)) {
            $r .= '</' . $k . '>';
        }
        if ("" === trim(strip_tags($r))) {
            return null;
        }
        return $r . ($c > $max[0] ? $max[1] : "");
    }
    ;
    if ("" === ($r = trim($m ? mb_substr($value, 0, $max[0]) : substr($value, 0, $max[0])))) {
        return null;
    }
    return $r . (($m ? mb_strlen($r) : strlen($r)) > $max[0] ? $max[1] : "");
});

To::_('page', static function (?array $value, $dent = true): ?string {
    if (!$value) {
        return null;
    }
    $content = $value['content'] ?? "";
    unset($value['content']);
    $value = rtrim("---\n" . To::YAML($value, true === $dent ? 4 : (is_int($dent) && $dent > 0 ? $dent : 4)) . "\n...\n\n" . $content, "\n");
    return "---\n\n..." !== $value ? $value : null;
});

To::_('title', static function (?string $value): ?string {
    if ("" === ($value = w(trim($value ?? "")) ?? "")) {
        return null;
    }
    $out = extension_loaded('mbstring') ? mb_convert_case($value, MB_CASE_TITLE) : ucwords($value);
    // Convert to abbreviation if all case(s) are in upper
    $out = u($out) === $out ? strtr($out, [' ' => ""]) : $out;
    return "" !== $out ? $out : null;
});
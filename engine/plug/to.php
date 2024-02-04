<?php

To::_('description', static function (?string $value, $max = 200): ?string {
    if (!$value) {
        return null;
    }
    // Add a space at the end of the block tag(s) that will be removed.
    // So that `<p>asdf.</p><p>asdf</p>` becomes `asdf. asdf` and not `asdf.asdf`.
    $value = preg_replace(['/\s+/', '/\s*(<\/(?:' . implode('|', [
        'address',
        'article',
        'blockquote',
        'd[dt]',
        'details',
        'div',
        'figcaption',
        'figure',
        'footer',
        'h[123456r]',
        'header',
        'li',
        'main',
        'nav',
        'p',
        'pre',
        'section',
        'summary',
        't[dh]'
    ]) . ')>)\s*/i'], [' ', '$1 '], $value);
    $value = trim(strip_tags($value, [
        'a',
        'abbr',
        'b',
        'bdi',
        'bdo',
        'br',
        'cite',
        'code',
        'data',
        'del',
        'dfn',
        'em',
        'i',
        'ins',
        'kbd',
        'mark',
        'q',
        's',
        'samp',
        'small',
        'span',
        'strong',
        'sub',
        'sup',
        'time',
        'u',
        'var',
        'wbr'
    ]));
    if ("" === $value) {
        return null;
    }
    if (is_int($max)) {
        $max = [$max, '&#x2026;'];
    }
    $utf8 = extension_loaded('mbstring');
    // <https://stackoverflow.com/a/1193598/1163000>
    if (false !== strpos($value, '<') || false !== strpos($value, '&')) {
        $out = "";
        $done = $i = 0;
        $tags = [];
        while ($done < $max[0] && preg_match('/<(?:\/[a-z\d:.-]+|[a-z\d:.-]+(?:\s(?:"[^"]*"|\'[^\']*\'|[^>])*)?)>|&(?:[a-z\d]+|#\d+|#x[a-f\d]+);|[\x80-\xFF][\x80-\xBF]*/i', $value, $m, PREG_OFFSET_CAPTURE, $i)) {
            $tag = $m[0][0];
            $pos = $m[0][1];
            $str = substr($value, $i, $pos - $i);
            if ($done + strlen($str) > $max[0]) {
                $out .= substr($str, 0, $max[0] - $done);
                $done = $max[0];
                break;
            }
            $out .= $str;
            $done += strlen($str);
            if ($done >= $max[0]) {
                break;
            }
            if ('&' === $tag[0] || ord($tag) >= 0x80) {
                $out .= $tag;
                ++$done;
            } else {
                // `tag`
                $n = trim(strtok($tag, "\n\r\t "), '<>/');
                // `</tag>`
                if ('/' === $tag[1]) {
                    $open = array_pop($tags);
                    assert($open === $n); // Check that tag(s) are properly nested!
                    $out .= $tag;
                // `<tag/>` or <https://www.w3.org/TR/2011/WD-html-markup-20110113/syntax.html#void-element>
                } else if ('/>' === substr($tag, -2) || preg_match('/^<(?:area|base|br|col|command|embed|hr|img|input|keygen|link|meta|param|source|track|wbr)(?=[\s>])/i', $tag)) {
                    $out .= $tag;
                // `<tag>`
                } else {
                    $out .= $tag;
                    $tags[] = $n;
                }
            }
            // Continue after the tag…
            $i = $pos + strlen($tag);
        }
        // Print rest of the text…
        if ($done < $max[0] && $i < strlen($value)) {
            $out .= substr($value, $i, $max[0] - $done);
        }
        // Close any open tag(s)…
        while ($close = array_pop($tags)) {
            $out .= '</' . $close . '>';
        }
        $out = trim(preg_replace('/\s*<br(?:\s(?:"[^"]*"|\'[^\']*\'|[^>])*)?>\s*/', ' ', $out));
        $value = trim(strip_tags($value));
        $count = $utf8 ? mb_strlen($value) : strlen($value);
        $out = trim($out) . ($count > $max[0] ? $max[1] : "");
        return "" !== $out ? $out : null;
    }
    $out = $utf8 ? mb_substr($value, 0, $max[0]) : substr($value, 0, $max[0]);
    $count = $utf8 ? mb_strlen($value) : strlen($value);
    $out = trim($out) . ($count > $max[0] ? $max[1] : "");
    return "" !== $out ? $out : null;
});

To::_('page', static function (?array $value): ?string {
    if (!$value) {
        return null;
    }
    $content = $value['content'] ?? "";
    unset($value['content']);
    $value = rtrim("---\n" . To::YAML($value, 2) . "\n...\n\n" . $content, "\n");
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
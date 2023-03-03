<?php

To::_('description', static function (?string $value, $max = 200): ?string {
    if (!$value) {
        return null;
    }
    $value = strip_tags(preg_replace(['/\s+/', '/\s*(<\/(?:' . implode('|', [
        'address',
        'article',
        'blockquote',
        'dd',
        'details',
        'div',
        'dt',
        'figcaption',
        'figure',
        'footer',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'header',
        'hr',
        'li',
        'main',
        'nav',
        'p',
        'pre',
        'section',
        'summary',
        'td',
        'th'
    // Make sure to add space at the end of the block tag(s) that will be removed. To make `<p>asdf.</p><p>asdf</p>`
    // becomes `asdf. asdf` and not `asdf.asdf`.
    ]) . ')>)\s*/i'], [' ', '$1 '], $value), [
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
    ]);
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

To::_('page', static function (?array $value) {
    if (!$value) {
        return null;
    }
    $content = $value['content'] ?? null;
    unset($value['content']);
    $value = [
        0 => $value,
        "\t" => $content
    ];
    return To::YAML($value, '  ', "\t");
});

To::_('sentence', static function (?string $value, string $tail = '.'): ?string {
    if ("" === ($value = trim($value ?? ""))) {
        return null;
    }
    if (extension_loaded('mbstring')) {
        return mb_strtoupper(mb_substr($value, 0, 1)) . mb_strtolower(mb_substr($value, 1)) . $tail;
    }
    return ucfirst(strtolower($value)) . $tail;
});

To::_('title', static function (?string $value): ?string {
    $value = w($value ?? "");
    $out = extension_loaded('mbstring') ? mb_convert_case($value, MB_CASE_TITLE) : ucwords($value);
    // Convert to abbreviation if all case(s) are in upper
    $out = u($out) === $out ? strtr($out, [' ' => ""]) : $out;
    return "" !== $out ? $out : null;
});
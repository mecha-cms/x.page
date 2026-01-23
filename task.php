<?php

$files = []; // g(LOT, 'archive,data,draft,page', true);

$r = "";

$r .= '<style>';
$r .= <<<CSS
main {
  margin-left: auto;
  margin-right: auto;
  max-width: 900px;
}
CSS;
$r .= '</style>';
$r .= '<main>';
$r .= '<form method="post">';
$r .= '<p>Since the release of Mecha version 4.0.0, our page file specifications have evolved, leaving your existing page files obsolete. This tool can help you convert them all at once.</p>';
$r .= '<p>The rules are as follows:</p>';
$r .= '<ol>';
$r .= '<li><p>The <code>*.archive</code>, <code>*.draft</code>, and <code>*.page</code> extensions are now deprecated in favor of the <code>*.json</code>, <code>*.txt</code>, and <code>*.yaml</code> extensions. Use the <code>*.txt</code> extension if you want to preserve the content of the existing page files as it is. It is the default extension for the former page format. You can assume that any page files with an extension other than <code>*.json</code> or <code>*.yaml</code> will be treated as if they had the <code>*.txt</code> extension. Thus, Mecha will parse their content as if they were written in the former page format.</p></li>';
$r .= '<li><p>You have the right to choose your preferred style. If you are familiar with the previous page format and don&rsquo;t want to switch, use the <code>*.txt</code> extension. The content will be the same:</p><pre><code>---
title: Page Title
description: Page description.
type: Markdown
...

Page content goes here.</code></pre><p>You won&rsquo;t get the nice YAML syntax highlighting provided by your preferred source code editor, but at least it won&rsquo;t warn you about invalid YAML syntax after the <code>...</code> marker.</p><p>If you wish to enjoy the benefits of proper syntax highlighting and formatting features natively from your preferred source code editor, it is best to use the <code>*.yaml</code> extension, but the formatting will be a bit different. There are two options:</p><ol><li><p>Simple key-value pairs that produce a single object. No special processing is needed. What you write is what the object will become:</p><pre><code>title: Page Title
description: Page description.
type: Markdown
content: |

  Page content goes here.</code></pre></li><li><p>The two-document style, in which the second document will become the page content:</p><pre><code>---
title: Page Title
description: Page description.
type: Markdown
--- |

  Page content goes here.</code></pre></li></ol><p>Please note that both styles require the page content to be indented, even by just one space. This is probably the part you&rsquo;ll find most inconvenient. However, it is necessary for a valid YAML syntax. If you use a source code editor that supports YAML syntax, it should indent your page content automatically.</p></li>';
$r .= '</ol>';
if (count($files)) {
    $r .= '<ul>';
    foreach ($files as $k => $v) {
        $route = strtr($k, [LOT . D => '/', D => '/']);
        if (0 === strpos($route, '/x/') || 0 === strpos($route, '/y/')) {
            continue;
        }
        $r .= '<li>';
        $r .= '<label>';
        $r .= '<input checked name="file[]" type="checkbox" value="' . $route . '">';
        $r .= ' ';
        $r .= $route;
        $r .= '</label>';
        $r .= '</li>';
    }
    $r .= '</ul>';
}
$r .= '<p>';
$r .= '<label>';
$r .= '<input name="keep" type="checkbox" value="1">';
$r .= ' ';
$r .= 'Keep the old page and page&rsquo;s data files in place. I will get rid of them manually.';
$r .= '</label>';
$r .= '</p>';
$r .= '<p role="group">';
$r .= '<button disabled name="x[page]" type="submit" value="1">Accept and convert all marked files!</button>';
$r .= ' ';
$r .= '<button disabled name="x[page]" type="submit" value="0">Do nothing and let me convert the marked files one by one manually!</button>';
$r .= '</p>';
$r .= '</form>';
$r .= '</main>';

echo $r;

exit;
<?php

if (version_compare(VERSION, '4.0.0', '<')) {
    return;
}

if ('POST' === $_SERVER['REQUEST_METHOD'] && is_array($r = $_POST['x']['page'] ?? 0)) {
    $style = $r['state']['x'] ?? 'txt';
    if ($keep = !empty($r['state']['keep'])) {
        foreach ($r['files'][0] as $k => $v) {
            if (!is_file($f = path(LOT . $k))) {
                throw new RuntimeException('Not a file: `' . $f . '`');
            }
            $ff = LOT . D . 'trash' . D . date('Y-m-d') . D . 'lot' . strtr($k, ['/' => D]);
            if (!is_dir($d = dirname($ff)) && !mkdir($d, 0700, true) && !is_dir($d)) {
                throw new RuntimeException('Failed to create folder: `' . $d . '`');
            }
            if (!copy($f, $ff)) {
                throw new RuntimeException('Failed to copy file: `' . $f . '`');
            }
            chmod($ff, 0600);
        }
    }
    foreach ($r['files'][1] as $k => $v) {
        $x = pathinfo($f = path(LOT . $k), PATHINFO_EXTENSION);
        // Convert page’s data
        if ('data' === $x) {
            if (!is_dir($d = dirname($f) . D . '+') && !mkdir($d, 0700, true) && !is_dir($d)) {
                throw new RuntimeException('Failed to create folder: `' . $d . '`');
            }
            $ff = $d . D . basename($f, '.' . $x) . '.';
            if (0 === filesize($f)) {
                if (!rename($f, $fff = $ff . 'txt')) {
                    throw new RuntimeException('Failed to move file: `' . $f . '`');
                }
                chmod($fff, 0600);
                continue;
            }
            $v = trim(file_get_contents($f));
            if ('null' === $v || function_exists('json_validate') && json_validate($v) || null !== json_decode($v)) {
                if (!rename($f, $fff = $ff . 'json')) {
                    throw new RuntimeException('Failed to move file: `' . $f . '`');
                }
                chmod($fff, 0600);
                continue;
            }
            if (!rename($f, $fff = $ff . 'txt')) {
                throw new RuntimeException('Failed to move file: `' . $f . '`');
            }
            chmod($fff, 0600);
            continue;
        }
        // Convert page
        $ff = dirname($f) . D . ('archive' === $x ? "'" : ('draft' === $x ? '~' : "")) . basename($k, '.' . $x) . '.';
        if (0 === filesize($f)) {
            if (!rename($f, $fff = $ff . 'txt')) {
                throw new RuntimeException('Failed to move file: `' . $f . '`');
            }
            chmod($fff, 0600);
            continue;
        }
        $data = From::page(file_get_contents($f), true);
        $data_content = $data['content'] ?? "";
        unset($data['content']);
        if ('json' === $style) {
            $data['content'] = $data_content;
            $fff = $ff . 'json';
            $s = json_encode($data);
        } else if ('md' === $style) {
            unset($data['type']);
            $fff = $ff . 'md';
            $s = trim("---\n" . To::YAML($data, 2) . "\n...\n\n" . $data_content, "\n");
        } else if ('md+yaml' === $style || 'txt+yaml' === $style) {
            if ('md+yaml' === $style) {
                unset($data['type']);
            }
            $fff = $ff . strstr($style, '+', true);
            $s = trim("---\n" . To::YAML($data, 2) . "\n---\n\n" . $data_content, "\n");
        } else if ('yaml' === $style) {
            $fff = $ff . 'yaml';
            $s = To::YAML($data, 2) ?? "";
            if ("" !== $data_content && "" !== $s) {
                if ('{' === $s[0] && '}' === substr($s, -1)) {
                    $s = trim(substr($s, 0, -1)) . ', content: ' . json_encode($data_content) . ' }';
                } else {
                    $s .= "\ncontent: |\n\n" . implode("\n", array_map(function ($v) {
                        return "" !== trim($v) ? '  ' . $v : "";
                    }, explode("\n", $data_content)));
                }
            }
        } else if ('yaml+' === $style) {
            $fff = $ff . 'yaml';
            $s = "---\n" . To::YAML($data, 2);
            if ("" !== $data_content) {
                $s .= "\n--- |\n\n" . implode("\n", array_map(function ($v) {
                    return "" !== trim($v) ? '  ' . $v : "";
                }, explode("\n", $data_content)));
            }
        } else {
            $fff = $ff . 'txt';
            $s = trim("---\n" . To::YAML($data, 2) . "\n...\n\n" . $data_content, "\n");
        }
        if (!file_put_contents($fff, $s)) {
            throw new RuntimeException('Failed to convert file: `' . $f . '`');
        }
        chmod($fff, 0600);
        if (!unlink($f)) {
            throw new RuntimeException('Failed to delete file: `' . $f . '`');
        }
    }
    kick(To::query([
        'keep' => $keep ? 1 : 0,
        'next' => 'true',
        'x' => $r['state']['x'] ?? 'txt'
    ]));
}

$chunk = 100; // Limit to 100 file(s) for each conversion to avoid reaching PHP’s default `max_input_vars` value

$lot = [];
foreach (['comment', 'page', 'tag', 'user'] as $k) {
    $lot = array_merge($lot, y(g(LOT . D . $k, 'archive,data,draft,page', true)));
}

if (!($count = count($lot))) {
    // Self-delete after one day…
    if (filemtime(__FILE__) < strtotime('-1 day')) {
        unlink(__FILE__);
    }
    if (!empty($_GET)) {
        kick('/');
    }
    return;
}

$r = "";

$r .= '<!DOCTYPE html>';
$r .= '<html dir="ltr">';
$r .= '<head>';
$r .= '<meta content="width=device-width" name="viewport">';
$r .= '<title>.\\lot\\x\\page\\*</title>';
$r .= '<style>';
$r .= <<<'CSS'
:root {
  margin: 0;
  padding: 2em;
}
body {
  margin: 0;
  padding: 0;
}
main {
  margin-left: auto;
  margin-right: auto;
  max-width: 800px;
}
pre .key {
  color: #900;
}
pre .mta {
  color: #009;
}
pre .val {
  color: #060;
}
button, select, summary {
  cursor: pointer;
}
CSS;
$r .= '</style>';
$r .= '</head>';
$r .= '<body>';
$r .= '<main>';
if (!array_key_exists('next', $_GET)) {
    $r .= '<h1>Page Extension Update</h1>';
    $r .= '<p>Since the release of Mecha version 4.0.0, our page file specifications have evolved, leaving your existing page files obsolete. This tool will help you to convert them all at once.</p>';
    $r .= '<p>The rules are as follows:</p>';
    $r .= '<ol>';
    $r .= '<li><p>The <code>*.archive</code>, <code>*.data</code>, <code>*.draft</code>, and <code>*.page</code> extensions are now deprecated in favor of the <code>*.json</code>, <code>*.md</code>, <code>*.txt</code>, and <code>*.yaml</code> extensions. Use the <code>*.txt</code> extension if you want to preserve the content of the existing page files as it is. It is the default extension for the former page format. You can assume that any page files with an extension other than <code>*.json</code>, <code>*.md</code>, or <code>*.yaml</code> will be treated as if they had the <code>*.txt</code> extension. Thus, Mecha will parse their content as if they were written in the former page format.</p><p>The <code>*.md</code> extension is generally treated like the <code>*.txt</code> extension, but with a special case for its <code>type</code> data, that I will explain later. Alternatives to the <code>*.md</code> extension are <code>*.markdown</code> and <code>*.mkd</code>. Alternative to the <code>*.yaml</code> extension is <code>*.yml</code>. Alternatives to the <code>*.txt</code> extension are any extensions not featured in the list. Actually, the <code>*.txt</code> extension is not featured either. It is mentioned here only to explain how it will process unknown extensions.</p></li>';
    $r .= '<li><p>You have the right to choose your preferred style. If you are familiar with the previous page format and don&rsquo;t want to switch, use the <code>*.txt</code> extension. The content will be the same:</p><pre><code>---
title: Page Title
description: Page description.
type: Markdown
...

Page content goes here.</code></pre><p>You won&rsquo;t get the nice YAML syntax highlighting provided by your preferred source code editor, but at least it won&rsquo;t warn you about invalid YAML syntax after the <a href="https://yaml.org/spec/1.2.2#22-structures" rel="nofollow" target="_blank"><code>...</code></a> marker.</p><p>If you wish to enjoy the benefits of proper syntax highlighting and formatting features natively from your preferred source code editor, it is best to use the <code>*.yaml</code> extension, but the formatting will be a bit different. There are two options:</p><ol><li><p>Simple key-value pairs that produce a single object. No special processing is needed. What you write is what the object will become:</p><pre><code><span class="key">title</span><span class="pun">:</span> <span class="val">Page Title</span>
<span class="key">description</span><span class="pun">:</span> <span class="val">Page description.</span>
<span class="key">type</span><span class="pun">:</span> <span class="val">Markdown</span>
<span class="key">content</span><span class="pun">:</span> <span class="pun">|</span>

  <span class="val">Page content goes here.</span></code></pre></li><li><p>The two-document style, in which the second document will become the page content:</p><pre><code><span class="mta">---</span>
<span class="key">title</span><span class="pun">:</span> <span class="val">Page Title</span>
<span class="key">description</span><span class="pun">:</span> <span class="val">Page description.</span>
<span class="key">type</span><span class="pun">:</span> <span class="val">Markdown</span>
<span class="mta">---</span> <span class="pun">|</span>

  <span class="val">Page content goes here.</span></code></pre></li></ol><p>Please note that both styles require the page content to be indented, even by just one space. This is probably the part you&rsquo;ll find most inconvenient. However, it is necessary for a valid YAML syntax. If your source code editor supports YAML syntax, it should automatically indent your page content.</p></li>';
    $r .= '<li><p>I&rsquo;m trying to force myself to embrace the YAML front-matter style. I&rsquo;ve done some research and haven&rsquo;t found any formal standards for this syntax that are as robust as <a href="https://spec.commonmark.org" rel="nofollow" target="_blank">CommonMark</a>. They exist because <a href="https://jekyllrb.com/docs/front-matter" rel="nofollow" target="_blank">Jekyll</a> popularized them. But, to be honest, YAML front-matter syntax is actually <em>flawed</em>. A plain text file containing YAML front-matter doesn&rsquo;t have a clear file type. It can&rsquo;t be considered YAML because the text after the second <code>---</code> marker can be arbitrary and doesn&rsquo;t have to be a valid YAML syntax. It also can&rsquo;t be considered Markdown because when parsed with a strict Markdown parser, the YAML front-matter part will simply result in a <code>&lt;hr&gt;</code> element followed by a <code>&lt;h2&gt;&hellip;&lt;/h2&gt;</code> element containing the raw YAML blocks as the element&rsquo;s content. Most source code editors that offer syntax highlighting based on file extensions also don&rsquo;t seem to support this kind of formatting natively, which means you&rsquo;ll likely need a third-party extension to provide the feature.</p><p>For these reasons, and to ease the transition from static site generator tools, I have decided to support this style. However, I will limit it only to files with the <code>*.md</code> and <code>*.txt</code> extensions. Some source code editors might give you an accurate syntax highlighting result for <code>*.md</code> files mixed with YAML front-matter, but this is usually because the source code editor silently supports it via third-party extensions that are likely pre-installed:</p><pre><code>---
title: Page Title
description: Page description.
---

Page content goes here.</code></pre><p>There is a special case for <code>*.md</code> files, in which the <code>type</code> data will be silently ignored and the value will always be assumed to be <code>\'Markdown\'</code> or <code>\'text/markdown\'</code>. The file extension itself already describes its content type clearly.</p></li>';
    $r .= '<li><p>The other supported page file extension is <code>*.json</code>. I decided to support it because it is a sub-set of YAML, and most programming languages have a native parser for it. So, if you have a client application that wants to build a page file, it doesn&rsquo;t require a YAML parser to be compiled into the system. Using <a href="https://javascript.info/json#json-stringify" rel="nofollow" target="_blank"><code>JSON.stringify()</code></a> to generate the page file content is sufficient. Of course, the result is not convenient to edit by hand, especially the <code>content</code> part. Consider using this style if you plan to manage page files exclusively from a third-party graphical user interface that lacks YAML features:</p><pre><code><span class="pun">{</span>
  <span class="key">"title"</span><span class="pun">:</span> <span class="val">"Page Title"</span><span class="pun">,</span>
  <span class="key">"description"</span><span class="pun">:</span> <span class="val">"Page description."</span><span class="pun">,</span>
  <span class="key">"type"</span><span class="pun">:</span> <span class="val">"Markdown"</span><span class="pun">,</span>
  <span class="key">"content"</span><span class="pun">:</span> <span class="val">"Page content goes here."</span>
}</code></pre></li>';
    $r .= '<li><p>You no longer mark &ldquo;archive&rdquo; and &ldquo;draft&rdquo; pages by their page file extensions. You simply store &ldquo;archive&rdquo; pages in the <code>.\lot\page\.archive\</code> folder and &ldquo;draft&rdquo; pages in the <code>.\lot\page\.draft\</code> folder.</p></li>';
    $r .= '<li><p>Page&rsquo;s data files will now use the <code>*.json</code>, <code>*.txt</code>, and <code>*.yaml</code> extensions. The <code>*.md</code> extension is intentionally not supported for page data since it has always been difficult to determine whether the file content should be parsed as a Markdown block or a span. For page data files with a <code>*.txt</code> extension, the content will be guaranteed to be returned as a string (or <code>null</code> if empty), even if it contains a boolean or numeric value, for example.</p><p>To differentiate it from other page files, data files for each page will now be stored in a <code>+\</code> folder within the related page folder &mdash;which should only be used to store the page&rsquo;s children:</p><pre><code>.\
└── lot\
    └── page\
        ├── posts\
        │   ├── +\
        │   │   └── time.txt ✔
        │   ├── post-1.yaml
        │   ├── post-2.yaml
        │   ├── post-3.yaml
        │   └── …
        └── posts.yaml</code></pre></li>';
    $r .= '</ol>';
    $r .= '<p>That&rsquo;s all. You can either continue to convert your page files using the tool below or click the &ldquo;Cancel&rdquo; button to convert them manually.</p>';
    $r .= '<hr>';
}
$r .= '<details' . (array_key_exists('next', $_GET) ? ' open' : "") . '>';
$r .= '<summary>The Converter</summary>';
$r .= '<form method="post">';
if (array_key_exists('x', $_GET)) {
    $r .= '<input name="x[page][state][x]" type="hidden" value="' . ($_GET['x'] ?? 'txt') . '">';
} else {
    $r .= '<p>Select your preferred page formatting style: <select name="x[page][state][x]"><option selected value="txt">Default</option><option value="md">Default as Markdown</option><option value="json">JSON</option><optgroup label="YAML"><option value="yaml">YAML Default Style</option><option value="yaml+">YAML Two-Document Style</option></optgroup><option value="txt+yaml">YAML Front-Matter Style</option><option value="md+yaml">YAML Front-Matter Style as Markdown</option></select></p>';
    $r .= '<pre><code style="background: #ffa; border: 1px solid #000; color: #000; display: block; padding: 0.5em 0.75em;">---
title: Page Title
description: Page description.
type: Markdown
...

Page content goes here.</code></pre>';
}
if (($rest = $count - $chunk) > 0) {
    $r .= '<p>This is a list of the ' . ($rest > $chunk ? 'first ' . $chunk : 'last ' . $rest) . ' file' . (1 === $rest ? "" : 's') . ' found to be converted' . ($rest > $chunk ? '. The remaining <b style="color: #900;">' . $rest . '</b> file' . (1 === $rest ? "" : 's') . ' will be converted in the next batch. As this tool converts your pages, you have time to relax. You can make a coffee, watch TV, etc' : "") . '.</p>';
}
$r .= '<p><label><input id="toggle-check-all" type="checkbox" checked> Select all</label></p>';
$r .= '<fieldset style="padding: 1em;">';
$r .= '<ol style="list-style: none; margin: 0; padding: 0;">';
foreach ($lot as $k => $v) {
    if ($chunk <= 0) {
        break; // Re-convert later
    }
    $route = '/' . strtr(substr($k, strlen(LOT . D)), [D => '/']);
    $r .= '<li>';
    $r .= '<input name="x[page][files][0][' . htmlspecialchars($route) . ']" type="hidden" value="1">';
    $r .= '<label>';
    $r .= '<input checked name="x[page][files][1][' . htmlspecialchars($route) . ']" type="checkbox" value="1">';
    $r .= ' ';
    $r .= '<code>.' . htmlspecialchars(strtr($route, ['/' => "\\"])) . '</code>';
    $r .= '</label>';
    $r .= '</li>';
    --$chunk;
}
if ($rest > 0) {
    $r .= '<li><label><input style="visibility: hidden;" type="checkbox"> <code>&hellip;</code></label></li>';
}
$r .= '</ol>';
$r .= '</fieldset>';
if (array_key_exists('keep', $_GET)) {
    $r .= '<input name="x[page][state][keep]" type="hidden" value="' . (empty($_GET['keep']) ? '0' : '1') . '">';
} else {
    $r .= '<p>';
    $r .= '<label>';
    $r .= '<input' . (!array_key_exists('keep', $_GET) || !empty($_GET['keep']) ? ' checked' : "") . ' name="x[page][state][keep]" type="checkbox" value="1">';
    $r .= ' ';
    $r .= 'Keep the old page and page&rsquo;s data files in <code>.\lot\trash\</code> folder. I will get rid of them manually.';
    $r .= '</label>';
    $r .= '</p>';
}
$r .= '<p role="group">';
$r .= '<button name="x[page][task]" type="submit" value="1">Submit</button>';
$r .= ' ';
$r .= '<button name="x[page][task]" type="submit" value="0">Cancel</button>';
$r .= '</p>';
$r .= '</form>';
$r .= '</details>';
$r .= '</main>';
$r .= '<script>';
$r .= <<<'JS'
const form = document.forms[0];
const formChecks = form.querySelectorAll('[name^="x[page][files][1]["]');
const formChecksAll = form.querySelector('#toggle-check-all');
form.elements['x[page][state][x]'].addEventListener('change', function () {
    let code = this.parentNode.nextElementSibling.firstChild,
        v = this.value;
    if ('json' === v) {
        code.textContent = '{\n  "title":"Page Title",\n  "description":"Page description.",\n  "type":"Markdown",\n  "content":"Page content goes here."\n}';
    } else if ('md' === v) {
        code.textContent = '---\ntitle: Page Title\ndescription: Page description.\n...\n\nPage content goes here.';
    } else if ('md+yaml' === v) {
        code.textContent = '---\ntitle: Page Title\ndescription: Page description.\n---\n\nPage content goes here.';
    } else if ('txt+yaml' === v) {
        code.textContent = '---\ntitle: Page Title\ndescription: Page description.\ntype: Markdown\n---\n\nPage content goes here.';
    } else if ('yaml' === v) {
        code.textContent = 'title: Page Title\ndescription: Page description.\ntype: Markdown\ncontent: |\n\n  Page content goes here.';
    } else if ('yaml+' === v) {
        code.textContent = '---\ntitle: Page Title\ndescription: Page description.\ntype: Markdown\n--- |\n\n  Page content goes here.';
    } else {
        code.textContent = '---\ntitle: Page Title\ndescription: Page description.\ntype: Markdown\n...\n\nPage content goes here.';
    }
});
const formChecksTotal = formChecks.length;
formChecks.forEach(formCheck => {
    formCheck.addEventListener('change', function () {
        let formChecksCheckedTotal = [].slice.call(formChecks).filter(v => v.checked).length;
        console.log({formChecksTotal, formChecksCheckedTotal});
        if (0 === formChecksCheckedTotal) {
            formChecksAll.checked = false;
            formChecksAll.indeterminate = false;
        } else if (formChecksCheckedTotal === formChecksTotal) {
            formChecksAll.checked = true;
            formChecksAll.indeterminate = false;
        } else {
            formChecksAll.checked = false;
            formChecksAll.indeterminate = true;
        }
    });
});
formChecksAll.addEventListener('change', function () {
    formChecks.forEach(formCheck => formCheck.checked = this.checked);
});
JS;
if (array_key_exists('next', $_GET)) {
    $r .= <<<'JS'
let timer;
window.addEventListener('load', function () {
    timer = window.setTimeout(function () {
        document.forms[0].querySelector('[name="x[page][task]"][value="1"]').click();
    }, 1000);
});
window.addEventListener('scroll', function () {
    timer && clearTimeout(timer);
});
JS;
}
$r .= '</script>';
$r .= '</body>';
$r .= '</html>';

http_response_code(200);

echo $r;

exit;
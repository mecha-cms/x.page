<?php

class Page extends File {

    protected $c;
    protected $h;
    protected $lot;

    protected function _e(string $v) {
        if ('null' === ($v = trim($v))) {
            return null;
        }
        if ("" !== ($v = json_decode($v, true) ?? e($v))) {
            if (is_string($v) && $this->_s($v)) {
                return is_object($r = From::YAML($v)) && $r instanceof DateTimeInterface ? new Time($r->format('Y-m-d H:i:s')) : $r;
            }
        }
        return "" !== $v ? $v : null;
    }

    // Verify that the YAML value is so “simple” (one line) that it can be parsed right away!
    protected function _s(string $v) {
        // Empty value is not considered “simple”. Having an empty value after `:` is most likely an indication that
        // there may be a child collection coming up on the next stream. Mark it as “not simple”!
        if ("" === ($v = trim($v))) {
            return false;
        }
        if (strlen($v) > 1) {
            if ("'" === $v[0] && "'" === substr($v, -1) || '"' === $v[0] && '"' === substr($v, -1)) {
                return true;
            }
            if ('[' === $v[0] && ']' === substr($v, -1) || '{' === $v[0] && '}' === substr($v, -1)) {
                // Either a collection that contains a child collection, or a collection that contains a key or a value
                // enclosed in quote(s) that contains a `[` or `{` character. Just mark it as “not simple”!
                if (strspn($r = substr($v, 1, -1), '[{') !== strlen($r)) {
                    return false;
                }
                return true;
            }
        }
        // Anchored value, tagged value, block scalar value (prefix), and a comment is not considered “simple”. A value
        // that starts with an `@` or `` ` `` character is also considered “not simple”, since those two character(s)
        // are currently reserved in YAML. In the future, they may have a special meaning, so it’s best to just mark
        // them as “not simple” for now.
        //
        // <https://yaml.org/spec/1.2.2#example-invalid-use-of-reserved-indicators>
        if ("" !== $v && false !== strpos('!#&*>@`|', $v[0])) {
            return false;
        }
        // A value followed by a comment is not considered “simple”.
        if (($n = strpos($v, '#')) > 0 && false !== strpos(" \n\t", substr($v, $n - 1, 1))) {
            return false;
        }
        // A “simple” value should not contain a `:` character followed by a white-space.
        return false === (strpos($v, ":\n") ?: strpos($v, ":\t") ?: strpos($v, ': '));
    }

    public function __call(string $kin, array $lot = []) {
        if (parent::_($kin)) {
            return parent::__call($kin, $lot);
        }
        if (parent::_($kin = p2f($kin))) {
            return parent::__call($kin, $lot);
        }
        $hash = $lot ? '#' . md5(z($lot)) : "";
        if (array_key_exists($k = $kin . $hash, $this->c)) {
            return $this->c[$k];
        }
        $v = Hook::fire(map($this->h, static function ($v) use ($kin) {
            return $v .= '.' . $kin;
        }), [$this->offsetGet($kin), $lot], $this);
        if ($lot && is_callable($v) && !is_string($v)) {
            $v = call_user_func($v, ...$lot);
        }
        return ($this->c[$k] = $v);
    }

    public function __construct($path = null, array $lot = []) {
        // `new Page([ … ])`
        if (is_iterable($path)) {
            $lot = y($path);
            $path = $lot['path'] ?? null;
        }
        parent::__construct($path);
        $this->c = [];
        foreach (array_merge([$n = static::class], array_slice(class_parents($n), 0, -1, false)) as $v) {
            $this->h[] = $v = c2f($v);
            $this->lot = array_replace_recursive($this->lot ?? [], (array) State::get('x.' . $v . '.page', true), $lot);
        }
        unset($this->lot['path']);
    }

    public function __set(string $key, $value): void {
        $this->offsetSet(p2f($key), $value);
    }

    public function __serialize(): array {
        $lot = parent::__serialize();
        if (!empty($lot['lot'])) {
            foreach ($lot['lot'] as &$v) {
                if (!is_string($v) || 0 !== strpos($v, PATH . D)) {
                    continue;
                }
                $v = strtr($v, [PATH . D => ".\\", D => "\\"]);
            }
            unset($v);
        }
        unset($lot['c'], $lot['h']);
        return $lot;
    }

    public function __toString(): string {
        return To::page(y($this->getIterator()));
    }

    public function __unserialize(array $lot): void {
        if (!empty($lot['lot'])) {
            foreach ($lot['lot'] as &$v) {
                if (!is_string($v) || 0 !== strpos($v, ".\\")) {
                    continue;
                }
                $v = PATH . D . strtr(substr($v, 2), ["\\" => D]);
            }
            unset($v);
        }
        parent::__unserialize($lot);
    }

    public function __unset(string $key): void {
        $this->offsetUnset(p2f($key));
    }

    public function _exist(...$lot) {
        return parent::exist(...$lot);
    }

    public function _name(...$lot) {
        return parent::name(...$lot);
    }

    public function _x(...$lot) {
        return parent::x(...$lot);
    }

    public function ID(...$lot) {
        $t = $this->time()->format('U');
        $id = $this->__call('id', $lot) ?? ($t ? sprintf('%u', $t) : null);
        return is_numeric($id) ? (int) $id : $id;
    }

    public function URL(...$lot) {
        if ($url = $this->__call('url', $lot)) {
            return $url;
        }
        if ($route = $this->route(...$lot)) {
            return long($route);
        }
        return null;
    }

    public function children($x = 'page', $deep = 0) {
        if (!$this->_exist()) {
            return null;
        }
        if ($v = $this->offsetGet('children')) {
            if (is_array($v) || (is_string($v) && is_dir($v))) {
                return Pages::from($v, $x, $deep);
            }
            return null;
        }
        if (is_dir($folder = dirname($v = $this->path) . D . pathinfo($v, PATHINFO_FILENAME))) {
            return Pages::from($folder, $x, $deep);
        }
        return null;
    }

    public function content(...$lot) {
        return $this->__call('content', $lot);
    }

    public function exist(...$lot) {
        return $this->lot['exist'] ?? $this->_exist(...$lot);
    }

    public function getIterator(): Traversable {
        $yield = [];
        if ($this->_exist()) {
            // Read page data from content…
            foreach ((array) From::page(file_get_contents($path = $this->path), true) as $k => $v) {
                $yield[$k] = 1;
                // Prioritize data from a file…
                if (is_file($f = dirname($path) . D . pathinfo($path, PATHINFO_FILENAME) . D . $k . '.data')) {
                    yield $k => $this->_e(trim(file_get_contents($f)));
                } else {
                    yield $k => $v;
                }
            }
            // Read the rest of page data from a file…
            foreach (g(dirname($path) . D . pathinfo($path, PATHINFO_FILENAME), 'data') as $k => $v) {
                if (isset($yield[$n = basename($k, '.data')])) {
                    continue; // Has been read, skip!
                }
                $yield[$n] = 1;
                yield $n => $this->_e(trim(file_get_contents($k)));
            }
        }
        // Read page data from the default value(s)…
        foreach ((array) ($this->lot ?? []) as $k => $v) {
            if (isset($yield[$k])) {
                continue; // Has been read, skip!
            }
            yield $k => $v;
        }
    }

    public function jsonSerialize() {
        return y($this->getIterator());
    }

    public function name(...$lot) {
        return $this->lot['name'] ?? $this->_name(...$lot);
    }

    public function offsetGet($key) {
        if ($this->_exist()) {
            // Prioritize data from a file…
            $folder = dirname($path = $this->path) . D . pathinfo($path, PATHINFO_FILENAME);
            if (is_file($f = $folder . D . $key . '.data')) {
                return 0 !== filesize($f) ? ($this->lot[$key] = $this->_e(trim(file_get_contents($f)))) : null;
            }
            if (0 === filesize($path)) {
                return null;
            }
            if ('content' === $key) {
                $content = n(file_get_contents($path));
                if (3 === strspn($content, '-')) {
                    $content = trim(explode("\n...\n", $content . "\n", 2)[1] ?? "", "\n");
                } else {
                    $content = trim($content, "\n");
                }
                return ($this->lot[$key] = "" !== $content ? $content : null);
            }
            // Stream the file content one line at a time for optimization, check if the line contains a simple
            // key-value pair. If so, stop the stream immediately, parse the value and then return it to save memory!
            $exist = false;
            foreach (stream($path) as $k => $v) {
                // No `---\n` part at the start of the stream means no page header at all.
                if (0 === $k && "---\n" !== $v && 3 !== strspn($v, '-')) {
                    break;
                }
                // Has reached the `...\n` part in the stream means the end of the page header.
                if ('...' === $v || "...\n" === $v) {
                    break;
                }
                // Skip comment part…
                if (0 === strpos($v, '#')) {
                    continue;
                }
                // Test for `{ asdf: asdf }` part in the stream…
                if ($v && '{' === $v[0]) {
                    $flow = true;
                    $v = trim(substr(trim($v), 1, -1));
                }
                // Test for `"asdf": asdf` part in the stream…
                if ($v && '"' === $v[0] && 0 === strpos($v, $k = '"' . strtr($key, ['"' => '\"']) . '"')) {
                    $v = trim(substr($v, strlen($k)));
                    if (':' === ($v[0] ?? 0) && false !== strpos(" \n\t", substr($v, 1, 1))) {
                        if ($this->_s($value = substr($v, 2))) {
                            return $this->_e($value);
                        }
                        $exist = true;
                        break;
                    }
                }
                // Test for `'asdf': asdf` part in the stream…
                if ($v && "'" === $v[0] && 0 === strpos($v, $k = "'" . strtr($key, ["'" => "''"]) . "'")) {
                    $v = trim(substr($v, strlen($k)));
                    if (':' === ($v[0] ?? 0) && false !== strpos(" \n\t", substr($v, 1, 1))) {
                        if ($this->_s($value = substr($v, 2))) {
                            return $this->_e($value);
                        }
                        $exist = true;
                        break;
                    }
                }
                // Test for `asdf: asdf` part in the stream…
                if ($v && false !== ($n = strpos($v, ":\n") ?: strpos($v, ":\t") ?: strpos($v, ': '))) {
                    if ($key === trim(substr($v, 0, $n))) {
                        if ($this->_s($value = substr($v, $n + 2))) {
                            return $this->_e($value);
                        }
                        $exist = true;
                        break;
                    }
                }
                if (isset($flow) && false !== strpos($v, ',')) {
                    if (false !== ($n = strpos($v, $k = '"' . strtr($key, ['"' => '\"']) . '"'))) {
                        $v = trim(substr($v, $n + strlen($k)));
                        if (':' === ($v[0] ?? 0) && false !== strpos(" \n\t", substr($v, 1, 1))) {
                            $exist = true;
                            break;
                        }
                    }
                    if (false !== ($n = strpos($v, $k = "'" . strtr($key, ["'" => "''"]) . "'"))) {
                        $v = trim(substr($v, $n + strlen($k)));
                        if (':' === ($v[0] ?? 0) && false !== strpos(" \n\t", substr($v, 1, 1))) {
                            $exist = true;
                            break;
                        }
                    }
                    if (false !== ($n = strpos($v, $key))) {
                        $v = trim(substr($v, $n + strlen($key)));
                        if (':' === ($v[0] ?? 0) && false !== strpos(" \n\t", substr($v, 1, 1))) {
                            $exist = true;
                            break;
                        }
                    }
                }
            }
            // The key does exist, but the line is not a simple key-value pair, which means that the optimization could
            // not be performed. And there we go, parsing the entire page content to get the accurate data.
            if ($exist) {
                $lot = From::page(file_get_contents($path));
                $this->lot = array_replace_recursive($this->lot ?? [], $lot);
            }
        }
        return $this->lot[$key] ?? null;
    }

    public function offsetSet($key, $value): void {
        if (isset($key)) {
            $this->lot[$key] = $value;
            // Clear cache so that hook(s) can be executed again!
            unset($this->c[$key]);
        } else {
            $this->lot[] = $value;
        }
    }

    public function offsetUnset($key): void {
        unset($this->c[$key], $this->lot[$key]);
    }

    public function parent(array $lot = []) {
        if (!$this->_exist()) {
            return null;
        }
        if ($v = $this->offsetGet('parent')) {
            if (is_string($v) && is_file($v)) {
                return new static($v, $lot);
            }
            return null;
        }
        $folder = dirname($this->path);
        if ($v = exist([
            $folder . '.archive',
            $folder . '.page'
        ], 1)) {
            return new static($v, $lot);
        }
        return null;
    }

    public function route(...$lot) {
        if ($route = $this->__call('route', $lot)) {
            return '/' . trim(strtr($route, [D => '/']), '/');
        }
        if ($path = $this->_exist()) {
            $folder = dirname($path) . D . pathinfo($path, PATHINFO_FILENAME);
            return '/' . trim(strtr($folder, [LOT . D . 'page' . D => '/', D => '/']), '/');
        }
        return null;
    }

    public function time(?string $format = null) {
        $name = (string) $this->_name();
        // Set `time` value from the page’s file name.
        if (($name = (string) $this->_name()) && '-' !== $name[0] && strspn($name, '-0123456789') === strlen($name)) {
            $count = count($a = explode('-', $name));
            if (3 === $count || 6 === $count) {
                // Year
                if (($n = (int) $a[0]) > 1969) {
                    // Month
                    if (($n = (int) $a[1]) > 0 && $n < 13) {
                        // Day
                        if (($n = (int) $a[2]) > 0 && $n < 32) {
                            // Hour
                            if (6 === $count && ($n = (int) $a[3]) > 0 && $n < 25) {
                                // Minute
                                if (($n = (int) $a[4]) > 0 && $n < 61) {
                                    // Second
                                    if (($n = (int) $a[5]) > 0 && $n < 61) {
                                        $t = new Time($name);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if (!isset($t)) {
            $t = $this->offsetGet('time') ?? parent::time();
            if (is_object($t) && $t instanceof DateTimeInterface) {
                $t = $t->format('Y-m-d H:i:s');
            }
            $t = new Time($t);
        }
        return $format ? $t($format) : $t;
    }

    public function type(...$lot) {
        return $this->__call('type', $lot) ?? 'HTML';
    }

    public function x(...$lot) {
        return $this->lot['x'] ?? $this->_x(...$lot);
    }

    public static function from(...$lot) {
        return new static(...$lot);
    }

}
<?php

class Page extends File {

    protected $c;
    protected $h;
    protected $lot;

    protected function _e($v) {
        if ('null' === $v) {
            return null;
        }
        return "" !== ($v = json_decode($v, true) ?? e($v)) ? $v : null;
    }

    public function __call(string $kin, array $lot = []) {
        if (parent::_($kin)) {
            return parent::__call($kin, $lot);
        }
        if (parent::_($kin = p2f($kin))) {
            return parent::__call($kin, $lot);
        }
        $hash = $lot ? z($lot) : "";
        if (array_key_exists($kin . $hash, $this->c)) {
            return $this->c[$kin . $hash];
        }
        $v = Hook::fire(map($this->h, static function ($v) use ($kin) {
            return $v .= '.' . $kin;
        }), [$this->offsetGet($kin), $lot], $this);
        if ($lot && is_callable($v) && !is_string($v)) {
            $v = call_user_func($v, ...$lot);
        }
        return ($this->c[$kin . $hash] = $v);
    }

    public function __construct(?string $path = null, array $lot = []) {
        parent::__construct($path = $lot['path'] ?? $path);
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
            $path = $this->path;
            // Prioritize data from a file…
            $folder = dirname($path) . D . pathinfo($path, PATHINFO_FILENAME);
            if (is_file($f = $folder . D . $key . '.data')) {
                return ($this->lot[$key] = $this->_e(trim(file_get_contents($f))));
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
            // Stream page file content and make sure that the property exists before parsing
            $exist = false;
            foreach (stream($path) as $k => $v) {
                // No `---\n` part at the start of the stream means no page header at all
                if (0 === $k && "---\n" !== $v && 3 !== strspn($v, '-')) {
                    break;
                }
                // Has reached the `...\n` part in the stream means the end of the page header
                if ("...\n" === $v) {
                    break;
                }
                // Test for `{ asdf: asdf }` part in the stream
                if ($v && '{' === $v[0]) {
                    $flow = true;
                    $v = trim(substr(trim(strstr($v, '#', true) ?: $v), 1, -1));
                }
                // Test for `"asdf": asdf` part in the stream
                if ($v && '"' === $v[0] && preg_match('/^"' . x(strtr($key, ['"' => '\"'])) . '"\s*:/', $v)) {
                    $exist = true;
                    break;
                }
                // Test for `'asdf': asdf` part in the stream
                if ($v && "'" === $v[0] && preg_match("/^'" . x(strtr($key, ["'" => "''"])) . "'\\s*:/", $v)) {
                    $exist = true;
                    break;
                }
                // Test for `asdf: asdf` part in the stream
                if ($v && $key === strtok($v, " :\n\t")) {
                    $exist = true;
                    break;
                }
                if (isset($flow) && false !== strpos($v, ',')) {
                    $v = ' ' . strtr($v, ["\t" => ' ']) . ' ';
                    if (
                        false !== strpos($v, ' ' . $key . ':') ||
                        preg_match('/\s' . x($key) . '\s*:/', $v) ||
                        preg_match('/\s"' . x(strtr($key, ['"' => '\"'])) . '"\s*:/', $v) ||
                        preg_match("/\\s'" . x(strtr($key, ["'" => "''"])) . "'\\s*:/", $v)
                    ) {
                        $exist = true;
                        break;
                    }
                }
            }
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
            // Clear cache so that hook(s) can be executed again
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
        if ($path = $this['parent']) {
            if (!is_string($path) || !is_file($path)) {
                return null;
            }
            return new static($path, $lot);
        }
        $folder = dirname($this->path);
        if (!$path = exist([
            $folder . '.archive',
            $folder . '.page'
        ], 1)) {
            return null;
        }
        return new static($path, $lot);
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
        // Set `time` value from the page’s file name
        if ($name && (
            // `2017-04-21.page`
            10 === strspn($name, '-0123456789') && 2 === substr_count($name, '-') ||
            // `2017-04-21-14-25-00.page`
            19 === strspn($name, '-0123456789') && 5 === substr_count($name, '-')
        ) && preg_match('/^[1-9]\d{3,}-(0\d|1[0-2])-(0\d|[1-2]\d|3[0-1])(-([0-1]\d|2[0-4])(-([0-5]\d|60)){2})?$/', $name)) {
            $time = new Time($name);
        // Else…
        } else {
            $time = $this->offsetGet('time') ?? parent::time();
            if (\is_object($time) && $time instanceof \DateTimeInterface) {
                $time = $time->format('Y-m-d H:i:s');
            }
            $time = new Time($time);
        }
        return $format ? $time($format) : $time;
    }

    public function type(...$lot) {
        return $this->__call('type', $lot) ?? 'HTML';
    }

    public function x(...$lot) {
        return $this->lot['x'] ?? $this->_x(...$lot);
    }

    public static function from(...$lot) {
        if (is_iterable($v = reset($lot))) {
            return new static(null, y($v));
        }
        return new static(...$lot);
    }

}
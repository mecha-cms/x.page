<?php

class Page extends File {

    protected $c;
    protected $cache;
    protected $lot;

    // Load all page data
    protected function _all(): array {
        $lot = $this->lot ?? [];
        if ($this->_exist()) {
            $lot = array_replace_recursive($lot, (array) (From::page(file_get_contents($path = $this->path), true)));
            foreach (g(dirname($path) . D . pathinfo($path, PATHINFO_FILENAME), 'data') as $k => $v) {
                $lot[basename($k, '.data')] = $this->_e(trim(file_get_contents($k)));
            }
        }
        return $lot;
    }

    protected function _e($v) {
        $v = Is::JSON($v) ? json_decode($v, true) : e($v);
        return "" !== $v ? $v : null;
    }

    public function __call(string $kin, array $lot = []) {
        if (parent::_($kin = p2f($kin))) {
            return parent::__call($kin, $lot);
        }
        $hash = $lot ? z($lot) : "";
        if (array_key_exists($kin . $hash, $this->cache)) {
            return $this->cache[$kin . $hash];
        }
        $v = Hook::fire(map($this->c, static function ($v) use ($kin) {
            return $v .= '.' . $kin;
        }), [$this->offsetGet($kin), $lot], $this);
        if ($lot && is_callable($v) && !is_string($v)) {
            $v = call_user_func($v, ...$lot);
        }
        return ($this->cache[$kin . $hash] = $v);
    }

    public function __construct(string $path = null, array $lot = []) {
        parent::__construct($path = $lot['path'] ?? $path);
        $this->cache = [];
        foreach (array_merge([$n = static::class], array_slice(class_parents($n), 0, -1, false)) as $v) {
            $this->c[] = $v = c2f($v);
            $this->lot = array_replace_recursive($this->lot ?? [], (array) State::get('x.' . $v . '.page', true), $lot);
        }
        unset($this->lot['path']);
    }

    public function __get(string $key) {
        return parent::__get($key) ?? $this->__call($key);
    }

    public function __set(string $key, $value) {
        $this->offsetSet(p2f($key), $value);
    }

    public function __toString(): string {
        return To::page($this->_all());
    }

    public function __unset(string $key) {
        $this->offsetUnset(p2f($key));
    }

    public function _exist() {
        return parent::exist();
    }

    public function ID(...$lot) {
        $t = $this->time()->format('U');
        $id = $this->__call('id', $lot) ?? ($t ? sprintf('%u', $t) : null);
        return is_string($id) && is_numeric($id) ? (int) $id : $id;
    }

    public function URL(...$lot) {
        if ($path = $this->_exist()) {
            $folder = dirname($path) . D . pathinfo($path, PATHINFO_FILENAME);
            return $this->__call('url', $lot) ?? long(strtr(strtr($folder, [LOT . D . 'page' . D => '/']), D, '/'));
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
        $out = [];
        if ($this->_exist()) {
            $out = From::page(file_get_contents($path = $this->path), true);
            $folder = dirname($path) . D . pathinfo($path, PATHINFO_FILENAME);
            foreach (g($folder, 'data') as $k => $v) {
                $v = e(file_get_contents($k));
                if (is_string($v) && Is::JSON($v)) {
                    $v = json_decode($v, true);
                }
                $out[basename($k, '.data')] = $v;
            }
        }
        return new ArrayIterator($out);
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize() {
        return $this->_all();
    }

    public function name(...$lot) {
        return $this->lot['name'] ?? parent::name(...$lot);
    }

    #[ReturnTypeWillChange]
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
                if (YAML\SOH === strtok($content, " \n\t#")) {
                    $content = trim(explode("\n" . YAML\EOT . "\n", $content . "\n", 2)[1] ?? "", "\n");
                } else {
                    $content = trim($content, "\n");
                }
                return ($this->lot[$key] = "" !== $content ? $content : null);
            }
            // Stream page file content and make sure that the property exists before parsing
            $exist = false;
            foreach (stream($path) as $k => $v) {
                // No `---\n` part at the start of the stream means no page header at all
                if (0 === $k && (YAML\SOH . "\n" !== $v || YAML\SOH !== strtok($v, " \n\t#"))) {
                    break;
                }
                // Has reached the `...\n` part in the stream means the end of the page header
                if (YAML\EOT . "\n" === $v) {
                    break;
                }
                // Test for `{ asdf: asdf }` part in the stream
                if ($v && '{' === $v[0]) {
                    $v = trim(substr(trim(strtok($v, '#')), 1, -1));
                }
                // Test for `"asdf": asdf` part in the stream
                if ($v && '"' === $v[0] && preg_match('/^"' . x(strtr($key, ['"' => '\"'])) . '"\s*:/', $v)) {
                    $exist = true;
                    break;
                }
                // Test for `'asdf': asdf` part in the stream
                if ($v && "'" === $v[0] && preg_match("/^'" . x(strtr($key, ["'" => "\\'"])) . "'\\s*:/", $v)) {
                    $exist = true;
                    break;
                }
                // Test for `asdf: asdf` part in the stream
                if ($v && $key === strtok($v, " \n\t:")) {
                    $exist = true;
                    break;
                }
            }
            if ($exist) {
                $lot = From::page(file_get_contents($path), true);
                $this->lot = array_replace_recursive($this->lot ?? [], $lot);
            }
        }
        return $this->lot[$key] ?? null;
    }

    public function offsetSet($key, $value): void {
        if (isset($key)) {
            $this->lot[$key] = $value;
            // Clear cache so that hook(s) can be executed again
            unset($this->cache[$key]);
        } else {
            $this->lot[] = $value;
        }
    }

    public function offsetUnset($key): void {
        unset($this->cache[$key], $this->lot[$key]);
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

    public function time(string $format = null) {
        $name = parent::name();
        // Set `time` value from the page’s file name
        if (
            is_string($name) && (
                // `2017-04-21.page`
                2 === substr_count($name, '-') ||
                // `2017-04-21-14-25-00.page`
                5 === substr_count($name, '-')
            ) &&
            is_numeric(strtr($name, ['-' => ""])) &&
            preg_match('/^[1-9]\d{3,}-(0\d|1[0-2])-(0\d|[1-2]\d|3[0-1])(-([0-1]\d|2[0-4])(-([0-5]\d|60)){2})?$/', $name)
        ) {
            $time = new Time($name);
        // Else…
        } else {
            $time = new Time($this->offsetGet('time') ?? parent::time());
        }
        return $format ? $time($format) : $time;
    }

    public function type(...$lot) {
        return $this->__call('type', $lot) ?? 'HTML';
    }

    public static function from(...$lot) {
        if (is_iterable($v = reset($lot))) {
            return new static(null, y($v));
        }
        return new static(...$lot);
    }

}
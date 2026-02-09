<?php

class Page extends File {

    protected $c;
    protected $h;
    protected $lot;

    public function __call(string $kin, array $lot = []) {
        if (parent::_($kin)) {
            return parent::__call($kin, $lot);
        }
        if (parent::_($kin = p2f($kin))) {
            return parent::__call($kin, $lot);
        }
        if (array_key_exists($k = $kin . ($lot ? D . md5(z($lot)) : ""), $this->c)) {
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
            $this->lot = array_replace_recursive($this->lot ?? [], (array) State::get('x.' . $v . '.lot', true), $lot);
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
        if (function_exists($task = "x\\page\\to\\x\\" . ($this->_x() ?? 'txt'))) {
            return call_user_func($task, y($this->getIterator()));
        }
        return To::page(y($this->getIterator()), 2);
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
        return $this->__call('id', $lot) ?? ((int) $this->time()->format('U'));
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

    public function children($x = null, $deep = 0) {
        if (!$this->_exist()) {
            return null;
        }
        $x ??= x\page\x();
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
        $c = [];
        if ($this->_exist()) {
            $prefix = "x\\page\\from\\x\\";
            if (function_exists($task = $prefix . ($this->_x() ?? 'txt'))) {
                // Read page data from content…
                foreach (call_user_func($task, file_get_contents($path = $this->path)) as $k => $v) {
                    $c[$k] = 1;
                    // Prioritize data from a file…
                    if ($f = exist(dirname($path) . D . pathinfo($path, PATHINFO_FILENAME) . D . '+' . D . $k . '.{' . x\page\x() . '}', 1)) {
                        $v = 0 === filesize($f) ? null : ("" !== ($v = rtrim(file_get_contents($v))) ? $v : null);
                        if (function_exists($task = $prefix . ($x = pathinfo($f, PATHINFO_EXTENSION)))) {
                            yield $k => call_user_func($task, $v) ?? $v;
                        } else {
                            yield $k => $v;
                        }
                    }
                }
                // Read the rest of page data from a file…
                foreach (g(dirname($path) . D . pathinfo($path, PATHINFO_FILENAME) . D . '+', x\page\x()) as $k => $v) {
                    if (isset($c[$kk = pathinfo($k, PATHINFO_FILENAME)])) {
                        continue; // Has been read, skip!
                    }
                    $c[$kk] = 1;
                    $v = 0 === filesize($k) ? null : ("" !== ($v = rtrim(file_get_contents($k))) ? $v : null);
                    if (function_exists($task = $prefix . ($x = pathinfo($k, PATHINFO_EXTENSION)))) {
                        yield $kk => call_user_func($task, $v) ?? $v;
                    } else {
                        yield $kk => $v;
                    }
                }
            }
        }
        // Read page data from the default value(s)…
        foreach ((array) ($this->lot ?? []) as $k => $v) {
            if (isset($c[$k])) {
                continue; // Has been read, skip!
            }
            yield $k => $v;
        }
    }

    public function jsonSerialize(): mixed {
        return y($this->getIterator());
    }

    public function name(...$lot) {
        return $this->lot['name'] ?? $this->_name(...$lot);
    }

    public function offsetGet($key): mixed {
        if ($this->_exist()) {
            $prefix = "x\\page\\from\\x\\";
            // Prioritize data from a file…
            if ($f = exist(dirname($path = $this->path) . D . pathinfo($path, PATHINFO_FILENAME) . D . '+' . D . $key . '.{' . x\page\x() . '}', 1)) {
                if (0 === filesize($f)) {
                    return null;
                }
                $v = ("" !== ($v = rtrim(file_get_contents($f)))) ? $v : null;
                if (function_exists($task = $prefix . ($x = pathinfo($f, PATHINFO_EXTENSION)))) {
                    return ($this->lot[$key] = call_user_func($task, $v) ?? $v);
                }
                return $v;
            }
            if (0 === filesize($path)) {
                return null;
            }
            $content = ("" !== ($content = rtrim(file_get_contents($path))) ? $content : null);
            if (null === ($lot = function_exists($task = $prefix . pathinfo($path, PATHINFO_EXTENSION)) ? call_user_func($task, $content) : From::page($content, true))) {
                $lot = ['content' => $content];
            }
            $this->lot = array_replace_recursive($this->lot ?? [], is_string($lot) ? ['content' => $lot] : (array) $lot);
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
        if ($v = exist($folder . '.{' . x\page\x() . '}', 1)) {
            return new static($v, $lot);
        }
        return null;
    }

    public function route(...$lot) {
        if ($route = $this->__call('route', $lot)) {
            return '/' . trim(strtr($route, [D => '/']), '/');
        }
        if ($path = $this->_exist()) {
            $n = pathinfo($path, PATHINFO_FILENAME);
            $route = trim(strtr(substr(dirname($path) . D, strlen(LOT . D . 'page' . D)), [D => '/']), '/');
            if ('~' === ($n[0] ?? 0)) {
                return null;
            }
            return '/' . ("" !== $route ? $route . '/' : "") . ("'" === ($n[0] ?? 0) ? substr($n, 1) : $n);
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
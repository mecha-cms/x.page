<?php

class Page extends File {

    protected $c;
    protected $h;
    protected $lot;
    protected $r;

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
        $hooks = [];
        foreach ($this->h as $h) {
            $hooks[] = $h . '.' . $kin;
        }
        $v = Hook::fire($hooks, [$this->offsetGet($kin), $lot], $this);
        if ($lot && is_callable($v) && !is_string($v)) {
            $v = $v(...$lot);
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
        $this->c = $this->r = [];
        foreach (array_merge([$n = static::class], array_slice(class_parents($n), 0, -2, false)) as $v) {
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
        unset($lot['c'], $lot['h'], $lot['r']);
        if (!empty($lot['lot'])) {
            foreach ($lot['lot'] as &$v) {
                if (!is_string($v) || 0 !== strpos($v, PATH . D)) {
                    continue;
                }
                $v = '.' . strtr(substr($v, strlen(PATH)), [D => "\\"]);
            }
            unset($v);
        }
        return $lot;
    }

    public function __toString(): string {
        if (function_exists($task = "x\\page\\to\\x\\" . ($this->_x() ?? 'txt'))) {
            return $task(y($this->getIterator()));
        }
        return To::page(y($this->getIterator()), 2);
    }

    public function __unserialize(array $lot): void {
        if (!empty($lot['lot'])) {
            foreach ($lot['lot'] as &$v) {
                if (!is_string($v) || 0 !== strpos($v, ".\\")) {
                    continue;
                }
                $v = PATH . strtr(substr($v, 1), ["\\" => D]);
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
        return $this->__call($k = __FUNCTION__, $lot) ?? $this->__call(strtolower($k), $lot) ?? parent::ID(...$lot);
    }

    public function children($x = null, $deep = 0) {
        if (!$this->_exist()) {
            return null;
        }
        $x ??= x\page\x();
        if ($v = $this->offsetGet(__FUNCTION__)) {
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
        return $this->__call(__FUNCTION__, $lot);
    }

    public function exist(...$lot) {
        return $this->lot[__FUNCTION__] ?? $this->_exist(...$lot);
    }

    public function getIterator(): Traversable {
        if ($this->_exist()) {
            $this->offsetGet("");
            foreach (g(dirname($path = $this->path) . D . pathinfo($path, PATHINFO_FILENAME) . D . '+', x\page\x()) as $k => $v) {
                $this->offsetGet($k);
            }
        }
        return new ArrayIterator($this->r);
    }

    public function jsonSerialize(): mixed {
        return y($this->getIterator());
    }

    public function link(...$lot) {
        if ($link = $this->__call(__FUNCTION__, $lot)) {
            return $link;
        }
        if ($route = $this->route(...$lot)) {
            return new Link(long($route));
        }
        return null;
    }

    public function links(...$lot) {
        if ($links = $this->__call(__FUNCTION__, $lot)) {
            return $links;
        }
        if (is_array($links)) {
            $r = [];
            foreach ($links as $link) {
                $r[] = new Link($link);
            }
            return $r;
        }
        return null;
    }

    public function name(...$lot) {
        if ($name = $this->__call(__FUNCTION__, $lot)) {
            return $name;
        }
        $name = (string) $this->_name(...$lot);
        if ('~' === ($name[0] ?? 0)) {
            return null;
        }
        return '#' === ($name[0] ?? 0) ? substr($name, 1) : $name;
    }

    public function offsetGet($key): mixed {
        if (array_key_exists($key, $this->r)) {
            return $this->r[$key];
        }
        if ($this->_exist()) {
            $prefix = "x\\page\\from\\x\\";
            // Prioritize data from a file…
            if ($k = exist(dirname($path = $this->path) . D . pathinfo($path, PATHINFO_FILENAME) . D . '+' . D . $key . '.{' . x\page\x() . '}', 1)) {
                if (0 === filesize($k)) {
                    return null;
                }
                $v = ("" !== ($v = rtrim(file_get_contents($k)))) ? $v : null;
                if (function_exists($task = $prefix . pathinfo($k, PATHINFO_EXTENSION))) {
                    $v = $task($v) ?? $v;
                }
                return ($this->lot[$key] = $this->r[$key] = $v);
            }
            if (0 === filesize($path)) {
                return null;
            }
            $content = "" !== ($content = rtrim(file_get_contents($path))) ? $content : null;
            if (null === ($lot = function_exists($task = $prefix . $this->_x()) ? $task($content) : From::page($content, true))) {
                $lot = ['content' => $content];
            }
            $this->r = ($this->lot = array_replace_recursive($this->lot ?? [], is_string($lot) ? ['content' => $lot] : (array) $lot));
        }
        return $this->lot[$key] ?? null;
    }

    public function offsetSet($key, $value): void {
        if (isset($key)) {
            $this->lot[$key] = $this->r[$key] = $value;
            // Clear cache so that hook(s) can be executed again!
            unset($this->c[$key]);
            foreach ($this->c as $k => $v) {
                if (0 === strpos($k, $key . D)) {
                    unset($this->c[$k]);
                }
            }
        } else {
            $this->lot[] = $this->r[] = $value;
        }
    }

    public function offsetUnset($key): void {
        unset($this->c[$key], $this->lot[$key], $this->r[$key]);
        foreach ($this->c as $k => $v) {
            if (0 === strpos($k, $key . D)) {
                unset($this->c[$k]);
            }
        }
    }

    public function parent(array $lot = []) {
        if (!$this->_exist()) {
            return null;
        }
        if ($v = $this->offsetGet(__FUNCTION__)) {
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
        if ($route = $this->__call(__FUNCTION__, $lot)) {
            return '/' . trim(strtr($route, [D => '/']), '/');
        }
        if ($path = $this->_exist()) {
            if (!is_string($name = $this->name())) {
                return null;
            }
            $route = trim(strtr(substr(dirname($path) . D, strlen(LOT . D . 'page' . D)), [D => '/']), '/');
            return '/' . ("" !== $route ? $route . '/' : "") . $name;
        }
        return null;
    }

    public function time(?string $pattern = null) {
        $name = (string) $this->name();
        if ($name && '-' !== $name[0] && strspn($name, '-0123456789') === strlen($name)) {
            $d = DateTime::createFromFormat('Y-m-d-H-i-s', $name);
            if ($d && $name === $d->format('Y-m-d-H-i-s')) {
                $t = new Time($d->format('Y-m-d H:i:s'));
            } else {
                $d = DateTime::createFromFormat('Y-m-d', $name);
                if ($d && $name === $d->format('Y-m-d')) {
                    $t = new Time($d->format('Y-m-d H:i:s'));
                }
            }
        }
        if (!isset($t)) {
            $t = $this->offsetGet(__FUNCTION__) ?? parent::time();
            if (is_object($t) && $t instanceof DateTimeInterface) {
                $t = $t->format('Y-m-d H:i:s');
            }
            $t = new Time($t);
        }
        return $pattern ? $t($pattern) : $t;
    }

    public function type(...$lot) {
        return $this->__call(__FUNCTION__, $lot) ?? 'HTML';
    }

    public function x(...$lot) {
        return $this->__call(__FUNCTION__, $lot) ?? $this->_x(...$lot);
    }

    public static function from(...$lot) {
        return new static(...$lot);
    }

}
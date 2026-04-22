<?php

class Pages extends Batch {

    protected $data = [];

    public function __get(string $key): mixed {
        if ($this->__fire__($key)) {
            return $this->{$key}();
        }
        if (parent::_($key)) {
            return $this->__call($key);
        }
        return $this->data[$key] ?? null;
    }

    public function __isset(string $key): bool {
        return null !== $this->__get($key);
    }

    public function __set(string $key, $value): void {
        if ($this->__has__($key) || parent::_($key)) {
            return;
        }
        $this->data[$key] = $value;
    }

    public function __serialize(): array {
        $lot = parent::__serialize();
        if ($values = $lot['lot'] ?? 0) {
            foreach ($values as $k => $v) {
                if (is_array($v = $v['path'] ?? $v)) {
                    foreach ($v as $kk => $vv) {
                        if (!is_string($vv) || 0 !== strpos($vv, PATH . D)) {
                            continue;
                        }
                        $values[$k][$kk] = '.' . strtr(substr($vv, strlen(PATH)), [D => "\\"]);
                    }
                    continue;
                }
                if (!is_string($v) || 0 !== strpos($v, PATH . D)) {
                    continue;
                }
                $values[$k] = '.' . strtr(substr($v, strlen(PATH)), [D => "\\"]);
            }
            $lot['lot'] = $values;
        }
        return $lot;
    }

    public function __toString(): string {
        return serialize($this->__serialize()['lot'] ?? []);
    }

    public function __unserialize(array $lot): void {
        if ($values = $lot['lot'] ?? 0) {
            foreach ($values as $k => $v) {
                if (is_array($v = $v['path'] ?? $v)) {
                    foreach ($v as $kk => $vv) {
                        if (!is_string($vv) || 0 !== strpos($vv, ".\\")) {
                            continue;
                        }
                        $values[$k][$kk] = PATH . strtr(substr($vv, 1), ["\\" => D]);
                    }
                    continue;
                }
                if (!is_string($v) || 0 !== strpos($v, ".\\")) {
                    continue;
                }
                $values[$k] = PATH . strtr(substr($v, 1), ["\\" => D]);
            }
            $lot['lot'] = $values;
        }
        parent::__unserialize($lot);
    }

    public function __unset(string $key): void {
        unset($this->data[$key]);
    }

    public function find($valid) {
        return parent::find(cue(function ($v, $k) use ($valid) {
            return fire($valid, [$this->page($v), $k], $this);
        }, $this));
    }

    public function first() {
        return null !== ($v = parent::first()) ? $this->page($v) : $v;
    }

    public function getIterator(): Traversable {
        foreach ($this->lot as $k => $v) {
            yield $k => $this->page($v);
        }
    }

    public function is($valid, $keys = false) {
        return parent::is(cue(function ($v, $k) use ($valid) {
            return fire($valid, [$this->page($v), $k], $this);
        }, $this), $keys);
    }

    public function jsonSerialize(): mixed {
        return $this->__serialize()['lot'] ?? [];
    }

    public function last() {
        return null !== ($v = parent::last()) ? $this->page($v) : $v;
    }

    public function map(callable $at) {
        return parent::map(cue(function ($v, $k) use ($at) {
            return fire($at, [$this->page($v), $k], $this);
        }, $this));
    }

    public function not($valid, $keys = false) {
        return parent::not(cue(function ($v, $k) use ($valid) {
            return fire($valid, [$this->page($v), $k], $this);
        }, $this), $keys);
    }

    public function offsetGet($key): mixed {
        if (null !== ($v = parent::offsetGet($key))) {
            return $this->page($v);
        }
        return $v;
    }

    public function page(...$lot) {
        static $c = [];
        if (isset($c[$k = json_encode($lot)])) {
            return $c[$k];
        }
        return ($c[$k] = new Page(...$lot));
    }

    public function pluck(string $key, $value = null) {
        $deep = false !== strpos(strtr($key, ["\\." => P]), '.');
        return $this->map(function ($v) use ($deep, $key, $value) {
            return $deep && is_array($v) ? (get($v, $key) ?? $value) : ($v->{f2p($key)} ?? $v->{$key} ?? $v[$key] ?? $value);
        });
    }

    public function pop($keys = false) {
        return null !== ($v = parent::pop($keys)) ? $this->page($v) : $v;
    }

    public function shift($keys = false) {
        return null !== ($v = parent::shift($keys)) ? $this->page($v) : $v;
    }

    public function sort($sort = 1, $keys = false) {
        if (count($lot = $this->lot) > 1) {
            $sort = is_array($sort) ? array_replace([1, 'path', null], $sort) : (is_callable($sort) ? $sort : [$sort, 'path', null]);
            if ($deep = is_string($sort[1]) && false !== strpos($v = strtr($sort[1], ["\\." => P]), '.')) {
                $key = explode('.', $v, 2);
                $key[0] = strtr($key[0], [P => "\\."]);
                $key[1] = strtr($key[1], [P => "\\."]);
            } else {
                // Don’t let user(s) put in key(s) like `__construct`
                if (0 === strpos($key[0] = $sort[1], '__')) {
                    return $this;
                }
            }
            foreach ($lot as $k => $v) {
                $r = is_string($v) && is_file($v) ? ['path' => $v] : (array) $v;
                if ('path' !== $key[0]) {
                    $page = $this->page($v);
                    $v = $page->{f2p($key[0])} ?? $page->{$key[0]} ?? $page[$key[0]] ?? null;
                    if ($deep && (is_array($v) || is_object($v))) {
                        $v = get(a($v), $key[1]);
                    }
                    if (is_object($v)) {
                        if ($v instanceof DateTimeInterface) {
                            $v = $v->getTimestamp();
                        } else if ($v instanceof Time) {
                            $v = strtotime($v . "");
                        } else if (method_exists($v, '__toString')) {
                            $v = $v->__toString();
                        }
                    }
                    $r[$key[0]] = is_string($v) ? strip_tags($v) : $v;
                }
                $lot[$k] = $r;
            }
            $this->lot = $lot;
        }
        return parent::sort($sort, $keys);
    }

    public function with($key, $value = null) {
        if (!$this->lot) {
            return $this;
        }
        $deep = is_string($key) && false !== strpos(strtr($key, ["\\." => P]), '.');
        if (!is_callable($value) && null !== $value) {
            $value = function () use ($value) {
                return $value;
            };
        }
        $c = cue($value, $this);
        $lot =& $this->lot;
        foreach ($lot as $k => &$v) {
            $v = is_string($v) && is_file($v) ? ['path' => $v] : (array) $v;
            $deep ? set($v, $key, $c($this->page($v), $k)) : ($v[$key] = $c($this->page($v), $k));
        }
        unset($lot, $v);
        return $this;
    }

    public static function from(...$lot) {
        if (is_iterable($v = reset($lot))) {
            return new static($v);
        }
        if (!is_string($v) || !is_dir($v)) {
            return new static;
        }
        $lot = array_replace([LOT . D . 'page', x\page\x(), 0], $lot);
        $lot[0] = path($lot[0]);
        $lot[3] = false;
        $pages = [];
        foreach (g(...$lot) as $v) {
            $n = pathinfo($v, PATHINFO_FILENAME);
            // Ignore dot file(s) such as `.txt` file(s), and file(s) with `#` (archive) and `~` (draft) prefix
            if ("" === $n || '#' === $n[0] || '~' === $n[0]) {
                continue;
            }
            $pages[] = $v;
        }
        return new static($pages);
    }

}
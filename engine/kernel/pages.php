<?php

class Pages extends Anemone {

    protected $data = [];

    public function __get(string $key) {
        if (method_exists($this, $key) && (new ReflectionMethod($this, $key))->isPublic()) {
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
        if (method_exists($this, $key) && (new ReflectionMethod($this, $key))->isPublic()) {
            // Skip!
        } else if (parent::_($key)) {
            // Skip!
        } else {
            $this->data[$key] = $value;
        }
    }

    public function __serialize(): array {
        $lot = parent::__serialize();
        unset($lot['parent']);
        if ($it = $lot['lot'] ?? 0) {
            if (is_object($it) && !($it instanceof ArrayAccess)) {
                $it = y($it);
            }
            foreach ($it as $k => $v) {
                $v = $v['path'] ?? $v;
                if (is_array($v)) {
                    foreach ($v as $kk => $vv) {
                        if (!is_string($vv) || 0 !== strpos($vv, PATH . D)) {
                            continue;
                        }
                        $it[$k][$kk] = strtr($vv, [PATH . D => ".\\", D => "\\"]);
                    }
                    continue;
                }
                if (!is_string($v) || 0 !== strpos($v, PATH . D)) {
                    continue;
                }
                $it[$k] = strtr($v, [PATH . D => ".\\", D => "\\"]);
            }
            $lot['lot'] = $it;
        }
        return $lot;
    }

    public function __toString(): string {
        return serialize($this->__serialize()['lot'] ?? []);
    }

    public function __unserialize(array $lot): void {
        if ($it = $lot['lot'] ?? 0) {
            if (is_object($it) && !($it instanceof ArrayAccess)) {
                $it = y($it);
            }
            foreach ($it as $k => $v) {
                $v = $v['path'] ?? $v;
                if (is_array($v)) {
                    foreach ($v as $kk => $vv) {
                        if (!is_string($vv) || 0 !== strpos($vv, ".\\")) {
                            continue;
                        }
                        $it[$k][$kk] = PATH . D . strtr(substr($vv, 2), ["\\" => D]);
                    }
                    continue;
                }
                if (!is_string($v) || 0 !== strpos($v, ".\\")) {
                    continue;
                }
                $it[$k] = PATH . D . strtr(substr($v, 2), ["\\" => D]);
            }
            $lot['lot'] = $it;
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

    public function first($take = false) {
        if (null !== ($v = parent::first($take))) {
            return $this->page($v);
        }
        return $v;
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

    public function jsonSerialize() {
        return $this->__serialize()['lot'] ?? [];
    }

    public function last($take = false) {
        if (null !== ($v = parent::last($take))) {
            return $this->page($v);
        }
        return $v;
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

    public function offsetGet($key) {
        if (null !== ($v = parent::offsetGet($key))) {
            return $this->page($v);
        }
        return $v;
    }

    public function page(...$lot) {
        if ($lot && ($v = reset($lot)) instanceof Page) {
            return $v;
        }
        return new Page(...$lot);
    }

    public function pluck(string $key, $value = null) {
        return $this->map(function ($v) use ($key, $value) {
            return $v->{f2p($key)} ?? $v[$key] ?? $value;
        });
    }

    public function rank(callable $at, $keys = false) {
        return parent::rank(cue(function ($v, $k) use ($at) {
            return fire($at, [$this->page($v), $k], $this);
        }, $this), $keys);
    }

    public function sort($sort = 1, $keys = false) {
        $k = -1;
        $lot = new SplFixedArray($this->count());
        $sort = is_array($sort) ? array_replace([1, 'path', null], $sort) : (is_callable($sort) ? $sort : [$sort, 'path', null]);
        foreach ($this->lot as $v) {
            $v = $this->page($v);
            $value = ['path' => $v->path];
            if ('path' !== $sort[1]) {
                $r = $v->{f2p($sort[1])} ?? $v[$sort[1]] ?? $sort[2];
                $value[$sort[1]] = is_object($r) && method_exists($r, '__toString') ? $r->__toString() : $r;
            }
            $lot[++$k] = $value;
            unset($v);
        }
        $this->lot = $lot;
        return parent::sort($sort, $keys);
    }

    public function vote(callable $at, $keys = false) {
        return parent::vote(cue(function ($v, $k) use ($at) {
            return fire($at, [$this->page($v), $k], $this);
        }, $this), $keys);
    }

    public static function from(...$lot) {
        if (is_iterable($v = reset($lot))) {
            return new static($v);
        }
        if (!is_string($v) || !is_dir($v)) {
            return new static;
        }
        $lot = array_replace([LOT . D . 'page', 'page', 0], $lot);
        $lot[0] = path($lot[0]);
        $lot[3] = false;
        $that = new static;
        $that->lot = new CallbackFilterIterator(g(...$lot), static function ($v) {
            return "" !== pathinfo($v, PATHINFO_FILENAME);
        });
        return $that;
    }

}
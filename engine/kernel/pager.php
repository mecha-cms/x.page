<?php

class Pager extends Pages {

    public $chunk;
    public $link;
    public $part;

    public function __construct(iterable $value = [], string $join = ', ') {
        $out = [];
        $page = $this->page();
        foreach ($value as $k => $v) {
            if (is_object($v)) {
                if (!is_a($v, get_class($page))) {
                    continue;
                }
                $out[$k] = $v->path;
            } else if ($v && is_string($v) && is_file($v)) {
                $out[$k] = $v;
            } else if (is_array($v)) {
                $out[$k] = $v;
            }
        }
        unset($value);
        $this->chunk = 5;
        $this->link = new URL($GLOBALS['url'] ?? '/');
        $this->part = 0;
        parent::__construct($out, $join);
    }

    public function __get(string $key) {
        if (method_exists($this, $key) && (new ReflectionMethod($this, $key))->isPublic()) {
            return $this->{$key}();
        }
        return parent::_($key) ? $this->__call($key) : $this->link->{$key};
    }

    public function __isset(string $key): bool {
        return null !== $this->__get($key);
    }

    public function __set(string $key, $value): void {
        if (false !== strpos(',hash,host,path,port,protocol,query,', ',' . $key . ',')) {
            $this->link->{$key} = $value;
        } else {
            $this->{$key} = $value;
        }
    }

    public function chunk(int $chunk = 5, int $part = -1, $keys = false) {
        $that = parent::chunk($chunk, $part, $keys);
        $that->chunk = $chunk;
        $that->part = $part;
        return $that;
    }

    public function current($take = false) {
        if (!$this->value) {
            return null;
        }
        $chunk = $this->chunk;
        $part = $this->part;
        if ($take) {
            $lot = $this->lot;
            if (!$this->lot = array_slice($lot, 0, $chunk * $part, true) + array_slice($lot, $chunk * ($part + 1), null, true)) {
                return null;
            }
        }
        return $this->page(null, [
            'current' => true,
            'description' => i('You are here'),
            'link' => $this->to($part + 1),
            'part' => $part + 1,
            'title' => i('Current')
        ]);
    }

    public function data(): Traversable {
        $chunk = $this->chunk;
        $part = $this->part;
        if ($last = ceil(count($this->lot) / $chunk)) {
            for ($i = 0; $i < $last; ++$i) {
                yield $i => $this->page(null, [
                    'current' => $i === $part,
                    'description' => i('Go to page %d', $i + 1),
                    'link' => $this->to($i + 1),
                    'part' => $i + 1,
                    'title' => i('Page %d', $i + 1)
                ]);
            }
        } else {
            yield from [];
        }
    }

    public function first($take = false) {
        if (!$this->value) {
            return null;
        }
        $chunk = $this->chunk;
        $lot = $this->lot;
        $part = $this->part;
        if ($take) {
            if (!$this->lot = array_slice($lot, $chunk, null, true)) {
                return null;
            }
        }
        return $this->page(null, [
            'current' => ($first = 1) === $part + 1,
            'description' => i('Go to the %s page', 'first'),
            'link' => $this->to($first),
            'part' => $first,
            'title' => i('First')
        ]);
    }

    public function last($take = false) {
        if (!$this->value) {
            return null;
        }
        $chunk = $this->chunk;
        $lot = $this->lot;
        $part = $this->part;
        if ($take) {
            if (!$this->lot = array_slice($lot, 0, -(count($lot) % $chunk), true)) {
                return null;
            }
        }
        return $this->page(null, [
            'current' => ($last = ceil(count($lot) / $chunk)) === $part + 1,
            'description' => i('Go to the %s page', 'last'),
            'link' => $this->to($last),
            'part' => $last,
            'title' => i('Last')
        ]);
    }

    public function mitose() {
        $that = parent::mitose();
        foreach (['chunk', 'link', 'part'] as $k) {
            $that->{$k} = $this->{$k};
        }
        return $that;
    }

    public function next($take = false) {
        $chunk = $this->chunk;
        $lot = $this->lot;
        $part = $this->part;
        if ($part >= ceil(count($lot) / $chunk) - 1) {
            return null;
        }
        if ($take) {
            if (!$this->lot = array_slice($lot, 0, $chunk * ($part + 1), true) + array_slice($lot, $chunk * ($part + 2), null, true)) {
                return null;
            }
        }
        return $this->page(null, [
            'current' => false,
            'description' => i('Go to the %s page', 'next'),
            'link' => $this->to($part + 2),
            'part' => $part + 2,
            'title' => i('Next')
        ]);
    }

    public function prev($take = false) {
        $chunk = $this->chunk;
        $part = $this->part;
        if ($part <= 0) {
            return null;
        }
        if ($take) {
            $lot = $this->lot;
            if (!$this->lot = array_slice($lot, 0, $chunk * ($part - 1), true) + array_slice($lot, $chunk * $part, null, true)) {
                return null;
            }
        }
        return $this->page(null, [
            'current' => false,
            'description' => i('Go to the %s page', 'previous'),
            'link' => $this->to($part),
            'part' => $part,
            'title' => i('Previous')
        ]);
    }

    public function previous(...$lot) {
        return $this->prev(...$lot);
    }

    public function to(int $part) {
        if ($part < 1) {
            return null;
        }
        $hash = $this->hash ?? "";
        $link = $this->link ?? "";
        $path = $this->path ?? "";
        $query = $this->query ?? "";
        return $link . $path . ($part > 0 ? '/' . $part : "") . $query . $hash;
    }

}
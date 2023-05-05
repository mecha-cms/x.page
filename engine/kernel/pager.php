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
            }
        }
        unset($value);
        $this->chunk = 0;
        $this->link = new URL($GLOBALS['url'] ?? '/');
        $this->part = 0;
        parent::__construct($out, $join);
    }

    public function __get(string $key) {
        if (method_exists($this, $key) && (new ReflectionMethod($this, $key))->isPublic()) {
            return $this->{$key}();
        }
        return $this->link->{$key};
    }

    public function __isset(string $key) {
        return null !== $this->__get($key);
    }

    public function __set(string $key, $value) {
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
            'description' => i('You are here.'),
            'link' => $this->to($part + 1),
            'part' => $part + 1,
            'title' => i('Current')
        ]);
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
            'description' => i('Go to the first page.'),
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
            'description' => i('Go to the last page.'),
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
            'description' => i('Go to the next page.'),
            'link' => $this->to($part + 2),
            'part' => $part + 2,
            'title' => i('Next')
        ]);
    }

    public function parts(): Traversable {
        if (!$lot = $this->lot) {
            yield from [];
        } else {
            $chunk = $this->chunk;
            $part = $this->part;
            foreach (array_chunk($lot, $chunk, false) as $k => $v) {
                $page = $this->page(null, [
                    'current' => $k === $part,
                    'description' => i('Go to page %d.', $k + 1),
                    'link' => $this->to($k + 1),
                    'part' => $k + 1,
                    'title' => i('Page %d', $k + 1)
                ]);
                yield $k => $page;
            }
        }
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
            'description' => i('Go to the previous page.'),
            'link' => $this->to($part),
            'part' => $part,
            'title' => i('Previous')
        ]);
    }

    public function previous(...$lot) {
        return $this->prev(...$lot);
    }

    public function to(int $part) {
        $hash = $this->hash ?? "";
        $link = $this->link ?? "";
        $path = $this->path ?? "";
        $query = $this->query ?? "";
        return $link . $path . ($part > 0 ? '/' . $part : "") . $query . $hash;
    }

    public static function from(...$lot) {
        return new static(...$lot);
    }

}
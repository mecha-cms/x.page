<?php

class Pager extends Pages {

    public $chunk;
    public $link;
    public $part;

    public function __construct(iterable $lot = [], string $join = ', ') {
        $i = 0;
        $page = $this->page();
        $r = new SplFixedArray(q($lot));
        foreach ($lot as $v) {
            if (is_object($v) && is_a($v, get_class($page))) {
                $r[$i++] = $v->path;
            } else if ($v && is_string($v) && is_file($v)) {
                $r[$i++] = stream_resolve_include_path($v);
            } else if (is_array($v)) {
                $r[$i++] = $v['path'] ?? $v;
            }
        }
        unset($lot);
        $this->chunk = 5;
        $this->link = new URL(lot('url') ?? '/');
        $this->part = 0;
        parent::__construct($r, $join);
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
        if (!$this->count()) {
            return null;
        }
        $chunk = $this->chunk;
        $parent = $this->parent;
        $part = $this->part;
        if ($take) {
            $lot = ($parent ? $parent->lot : $this->lot)->toArray();
            $lot = array_merge(array_slice($lot, 0, $chunk * $part), array_slice($lot, $chunk * ($part + 1)));
            $this->lot = SplFixedArray::fromArray($lot);
            if (!$lot) {
                return null;
            }
        }
        return $this->page([
            'current' => true,
            'description' => i('You are here'),
            'link' => $this->to($part + 1),
            'part' => $part + 1,
            'title' => i('Current')
        ]);
    }

    public function data(): Traversable {
        $chunk = $this->chunk;
        $parent = $this->parent;
        $part = $this->part;
        if ($count = ceil(count($parent ? $parent->lot : $this->lot) / $chunk)) {
            $r = new SplFixedArray($count);
            for ($i = 0; $i < $count; ++$i) {
                $r[$i] = $this->page([
                    'current' => $i === $part,
                    'description' => i('Go to page %d', $i + 1),
                    'link' => $this->to($i + 1),
                    'part' => $i + 1,
                    'title' => i('Page %d', $i + 1)
                ]);
            }
            return $r;
        }
        return new SplFixedArray(0);
    }

    public function first($take = false) {
        if (!$this->count()) {
            return null;
        }
        $chunk = $this->chunk;
        $parent = $this->parent;
        $part = $this->part;
        if ($take) {
            $lot = ($parent ? $parent->lot : $this->lot)->toArray();
            $lot = array_slice($lot, $chunk);
            $this->lot = SplFixedArray::fromArray($lot);
            if (!$lot) {
                return null;
            }
        }
        return $this->page([
            'current' => ($first = 1) === $part + 1,
            'description' => i('Go to the %s page', 'first'),
            'link' => $this->to($first),
            'part' => $first,
            'title' => i('First')
        ]);
    }

    public function last($take = false) {
        if (!$this->lot) {
            return null;
        }
        $chunk = $this->chunk;
        $parent = $this->parent;
        $part = $this->part;
        $count = count($lot = $parent ? $parent->lot : $this->lot);
        if ($take) {
            $lot = array_slice($lot->toArray(), 0, -($count % $chunk));
            $this->lot = SplFixedArray::fromArray($lot);
            if (!$lot) {
                return null;
            }
        }
        return $this->page([
            'current' => $part + 1 === ($last = ceil($count / $chunk)),
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
        $parent = $this->parent;
        $part = $this->part;
        $count = count($lot = $parent ? $parent->lot : $this->lot);
        if ($part >= ceil($count / $chunk) - 1) {
            return null;
        }
        if ($take) {
            $lot = $lot->toArray();
            $lot = array_merge(array_slice($lot, 0, $chunk * ($part + 1)), array_slice($lot, $chunk * ($part + 2)));
            $this->lot = SplFixedArray::fromArray($lot);
            if (!$lot) {
                return null;
            }
        }
        return $this->page([
            'current' => false,
            'description' => i('Go to the %s page', 'next'),
            'link' => $this->to($part + 2),
            'part' => $part + 2,
            'title' => i('Next')
        ]);
    }

    public function prev($take = false) {
        $chunk = $this->chunk;
        $parent = $this->parent;
        $part = $this->part;
        if ($part < 1) {
            return null;
        }
        if ($take) {
            $lot = ($parent ? $parent->lot : $this->lot)->toArray();
            $lot = array_merge(array_slice($lot, 0, $chunk * ($part - 1)), array_slice($lot, $chunk * $part));
            $this->lot = SplFixedArray::fromArray($lot);
            if (!$lot) {
                return null;
            }
        }
        return $this->page([
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
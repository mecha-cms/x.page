<?php

class Pager extends Pages {

    private $at;
    private $link;

    public function __construct(iterable $lot = [], string $join = ', ') {
        $i = 0;
        $page = get_class($this->page());
        $r = [];
        foreach ($lot as $v) {
            if (is_array($v)) {
                $r[] = $v['path'] ?? $v;
            } else if (is_string($v) && is_file($v)) {
                $r[] = stream_resolve_include_path($v);
            } else if (is_object($v) && is_a($v, $page)) {
                $r[] = $v->path;
            }
        }
        $this->at = 0;
        $this->link = new Link(long('/'));
        parent::__construct($r, $join);
    }

    public function __get(string $key): mixed {
        if ($this->__fire__($key)) {
            return $this->{$key}();
        }
        if (false !== strpos(',base,hash,host,path,port,query,scheme,', ',' . $key . ',')) {
            return $this->link->{$key};
        }
        return $this->__call($key);
    }

    public function __isset(string $key): bool {
        return null !== $this->__get($key);
    }

    public function __set(string $key, $value): void {
        if (false !== strpos(',base,hash,host,path,port,query,scheme,', ',' . $key . ',')) {
            $this->link->{$key} = $value;
        } else {
            $this->{$key} = $value;
        }
    }

    public function chunk(int $chunk = 5, int $at = -1, $keys = false) {
        $that = parent::chunk($chunk, $at, $keys);
        $that->at = $at;
        $that->link = $this->link;
        return $that;
    }

    public function current() {
        if (!$this->count()) {
            return null;
        }
        $at = $this->at;
        return $this->page([
            'current' => true,
            'description' => i('You are here'),
            'link' => $this->to($current = $at + 1),
            'part' => $current,
            'title' => i('Current')
        ]);
    }

    public function data() {
        $at = $this->at;
        $max = $this->max;
        $step = $this->step;
        $r = [];
        if ($max = ceil($max / $step)) {
            for ($i = 0; $i < $max; ++$i) {
                $r[$i] = $this->page([
                    'current' => $i === $at,
                    'description' => i('Go to page %d', $i + 1),
                    'link' => $this->to($i + 1),
                    'part' => $i + 1,
                    'title' => i('Page %d', $i + 1)
                ]);
            }
        }
        return new Pages($r);
    }

    public function first() {
        if (!$this->count()) {
            return null;
        }
        $at = $this->at;
        return $this->page([
            'current' => $at + 1 === ($first = 1),
            'description' => i('Go to the %s page', 'first'),
            'link' => $this->to($first),
            'part' => $first,
            'title' => i('First')
        ]);
    }

    public function last() {
        if (!$this->count()) {
            return null;
        }
        $at = $this->at;
        $max = $this->max;
        $step = $this->step;
        return $this->page([
            'current' => $at + 1 === ($last = ceil($max / $step)),
            'description' => i('Go to the %s page', 'last'),
            'link' => $this->to($last),
            'part' => $last,
            'title' => i('Last')
        ]);
    }

    public function next() {
        if (!$this->count()) {
            return null;
        }
        $at = $this->at;
        $max = $this->max;
        $step = $this->step;
        if ($at >= ceil($max / $step) - 1) {
            return null;
        }
        return $this->page([
            'current' => false,
            'description' => i('Go to the %s page', 'next'),
            'link' => $this->to($next = $at + 2),
            'part' => $next,
            'title' => i('Next')
        ]);
    }

    public function prev() {
        if (!$this->count()) {
            return null;
        }
        $at = $this->at;
        if ($at < 1) {
            return null;
        }
        return $this->page([
            'current' => false,
            'description' => i('Go to the %s page', 'previous'),
            'link' => $this->to($prev = $at),
            'part' => $prev,
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
        $base = $this->base ?? "";
        $hash = $this->hash ?? "";
        $path = $this->path ?? "";
        $query = $this->query ?? "";
        return $base . $path . ($part > 0 ? '/' . $part : "") . $query . $hash;
    }

}
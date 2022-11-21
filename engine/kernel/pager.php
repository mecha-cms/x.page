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
        $this->link->{$key} = $value;
    }

    public function chunk(int $chunk = 5, int $part = -1, $keys = false) {
        $that = parent::chunk($chunk, $part, $keys);
        $that->chunk = $chunk;
        $that->part = $part;
        return $that;
    }

    public function current() {
        if ($this->value) {
            return $this->page(null, [
                'description' => i('You are here.'),
                'link' => $this->to($this->part + 1),
                'title' => i('Current')
            ]);
        }
        return null;
    }

    public function mitose() {
        $that = parent::mitose();
        foreach (['chunk', 'link', 'part'] as $k) {
            $that->{$k} = $this->{$k};
        }
        return $that;
    }

    public function next() {
        $chunk = $this->chunk;
        $part = $this->part;
        if ($part >= ceil(count($this->lot) / $chunk) - 1) {
            return null;
        }
        return $this->page(null, [
            'description' => i('Go to the next page.'),
            'link' => $this->to($part + 2),
            'title' => i('Next')
        ]);
    }

    public function prev() {
        $chunk = $this->chunk;
        $part = $this->part;
        if ($part <= 0) {
            return null;
        }
        return $this->page(null, [
            'description' => i('Go to the previous page.'),
            'link' => $this->to($part),
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
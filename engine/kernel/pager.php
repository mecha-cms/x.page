<?php

class Pager extends Anemone {

    public $chunk;
    public $link;
    public $part; // 1–based index

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
        $this->chunk = 5;
        $this->link = new URL($GLOBALS['url'] ?? '/');
        $this->part = 0;
        parent::__construct(array_filter($out), $join);
    }

    public function __get(string $key) {
        if (method_exists($this, $key) && (new \ReflectionMethod($this, $key))->isPublic()) {
            return $this->{$key}();
        }
        return $this->link->{$key} ?? null;
    }

    public function __isset(string $key) {
        return null !== $this->__get($key);
    }

    public function __set(string $key, $value) {
        $this->link->{$key} = $value;
    }

    // Unlike the parent class, `Anemone`, this class uses 1–based index
    public function chunk(int $chunk = 5, int $part = 0, $keys = false) {
        $that = parent::chunk($chunk, $part - 1, $keys);
        $that->chunk = $chunk;
        $that->part = $part;
        return $that;
    }

    public function current() {
        if ($this->value) {
            return $this->page(null, [
                'description' => i('This is the current page.'),
                'link' => $this->to($this->part),
                'title' => i('Current')
            ]);
        }
        return null;
    }

    public function mitose() {
        $that = parent::mitose();
        foreach (['base', 'chunk', 'hash', 'part', 'path', 'query'] as $k) {
            $that->{$k} = $this->{$k};
        }
        return $that;
    }

    public function next() {
        $chunk = $this->chunk;
        $part = $this->part;
        if ($part >= ceil(count($this->lot) / $chunk)) {
            return null;
        }
        return $this->page(null, [
            'description' => i('Go to the next page!'),
            'link' => $this->to($part + 1),
            'title' => i('Next')
        ]);
    }

    public function page(...$lot) {
        return Page::from(...$lot);
    }

    public function prev() {
        $chunk = $this->chunk;
        $part = $this->part;
        if ($part <= 1) {
            return null;
        }
        return $this->page(null, [
            'description' => i('Go to the previous page!'),
            'link' => $this->to($part - 1),
            'title' => i('Previous')
        ]);
    }

    public function to(int $part) {
        $base = trim($this->base ?? "", '/');
        $hash = trim($this->hash ?? "", '#');
        $path = trim($this->path ?? "", '/');
        $query = trim($this->query ?? "", '?');
        return $base . ("" !== $path ? '/' . $path : "") . ($part > 0 ? '/' . $part : "") . ("" !== $query ? '?' . $query : "") . ("" !== $hash ? '#' . $hash : "");
    }

}
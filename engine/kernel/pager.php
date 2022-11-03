<?php

class Pager extends Anemone {

    protected $base;
    protected $chunk;
    protected $part;

    protected function _link(int $part) {
        return $this->base . '/' . $part;
    }

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
        $this->part = -1;
        parent::__construct(array_filter($out), $join);
    }

    public function chunk(int $chunk = 5, int $part = -1, $keys = false) {
        $that = parent::chunk($chunk, $part, $keys);
        $that->chunk = $chunk;
        $that->part = $part;
        return $that;
    }

    public function current() {
        if ($this->value) {
            return $this->page(null, ['link' => $this->_link($this->part + 1)]);
        }
        return null;
    }

    public function mitose() {
        $that = parent::mitose();
        $that->base = $this->base;
        return $that;
    }

    public function next() {
        $chunk = $this->chunk;
        $part = $this->part;
        if ($part >= ceil(count($this->lot) / $chunk) - 1) {
            return null;
        }
        return $this->page(null, ['link' => $this->_link($part + 2)]);
    }

    public function page(...$lot) {
        return Page::from(...$lot);
    }

    public function prev() {
        $chunk = $this->chunk;
        $part = $this->part;
        if ($part <= 0) {
            return null;
        }
        return $this->page(null, ['link' => $this->_link($part)]);
    }

    public static function from(...$lot) {
        $value = array_shift($lot) ?? [];
        $base = array_shift($lot) ?? null;
        $pager = new static($value);
        $pager->base = $base;
        return $pager;
    }

}
<?php

class Pager extends File {

    protected $lot;

    public function __construct(string $path = null, array $lot = []) {
        $this->lot = $lot;
        parent::__construct($path = $this->lot['path'] ?? $path);
        if ($path && is_string($path) && 0 === strpos($path, PATH)) {
            if (is_dir($path)) {
                extract($GLOBALS, EXTR_SKIP);
                $route = trim(strtr($this->lot['route'] ?? strtr($path, [LOT . D . 'page' . D => ""]), D, '/'), '/');
                $route = "" !== $route ? '/' . $route : "";
                if ($file = exist([
                    $path . '.archive',
                    $path . '.page'
                ], 1)) {
                    $page = $this->page($file, $lot);
                    $pages = $this->pages($path, 'page', $page->deep);
                    if ($sort = $page->sort) {
                        $pages->sort($sort);
                    }
                    if ($chunk = $page->chunk) {
                        $pages = $pages->chunk($chunk);
                    }
                    $part = $this->lot['part'] ?? 0;
                    if ($part > 0) {
                        if ($pages->get($part - 1)) {
                            $this->lot['current'] = $this->page(null, ['link' => $url . $route . '/' . $part]);
                        }
                        if ($pages->get($part - 2)) {
                            $this->lot['prev'] = $this->page(null, ['link' => $url . $route . '/' . ($part - 1)]);
                        }
                        if ($pages->get($part)) {
                            $this->lot['next'] = $this->page(null, ['link' => $url . $route . '/' . ($part + 1)]);
                        }
                    }
                }
            } else if (is_file($path)) {
                $folder = dirname($path);
                if ($file = exist([
                    $folder . '.archive',
                    $folder . '.page'
                ], 1)) {
                    $page = $this->page($file);
                    $pages = $this->pages($folder, 'page', $page->deep);
                    if ($sort = $page->sort) {
                        $pages->sort($sort);
                    }
                    $current = $pages->index($path);
                    if (null !== $current) {
                        $this->lot['current'] = $this->page($path);
                        if ($next = $pages->get($current + 1)) {
                            $this->lot['next'] = $this->page($next);
                        }
                        if ($prev = $pages->get($current - 1)) {
                            $this->lot['prev'] = $this->page($prev);
                        }
                    }
                }
            }
        }
        unset($this->lot['path']);
    }

    public function __get(string $key) {
        return $this->lot[$key] ?? null;
    }

    public function __set(string $key, $value) {
        $this->lot[$key] = $value;
    }

    public function page(...$lot) {
        return Page::from(...$lot);
    }

    public function pages(...$lot) {
        return Pages::from(...$lot);
    }

}
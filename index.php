<?php

namespace {
    function page(...$lot) {
        return new \Page(...$lot);
    }
    function pages(...$lot) {
        return new \Pages(...$lot);
    }
    // Initialize layout variable(s)
    \lot('page', new \Page);
    \lot('pager', new \Pager);
    \lot('pages', new \Pages);
    // Set page’s condition data as early as possible, so that other
    // extension(s) can use it without having to enter the `route` hook
    $n = \x\page\n($path = \trim($url->path ?? $state->route ?? "", '/'));
    $route = \trim($state->route ?? 'index', '/');
    $folder = \LOT . \D . 'page' . \D . (($n ? \substr($path, 0, -\strlen('/' . $n)) : $path) ?: $route);
    $parent = \dirname($folder);
    $has_pages = \q(\g($folder, 'page'));
    $has_parent = \exist([
        $parent . '.archive',
        $parent . '.page',
        $parent . \D . '.archive',
        $parent . \D . '.page'
    ], 1);
    $is_home = "" === $path || $route === $path ? \exist([
        $folder . '.archive',
        $folder . '.page'
    ], 1) : false;
    $is_page = \exist([
        $folder . '.archive',
        $folder . '.page'
    ], 1);
    // Check if “pages” mode is disabled by a dot file such as `.\lot\page\about\.page`
    $not_pages = \exist([
        $folder . \D . '.archive',
        $folder . \D . '.page'
    ], 1);
    \State::set([
        'has' => [
            'page' => $is_home || $is_page,
            'pages' => !!$has_pages,
            'parent' => $has_parent && false !== \strpos($path, '/'),
            'part' => !!\x\page\n($path)
        ],
        'is' => [
            'error' => $is_error = ("" === $path && !$is_home || "" !== $path && !$is_page) ? 404 : false,
            'home' => !!$is_home,
            'page' => $is_home || ($is_page && ($not_pages || !$has_pages)),
            'pages' => $has_pages && !$not_pages
        ]
    ]);
}

namespace x\page {
    // Returns the number string at the end of the `$path` as an integer if present, else returns `null`
    function n($path) {
        $n = \trim(\strrchr($path, '/') ?: $path, '/');
        if ("" !== $n && '0' !== $n[0] && \strspn($n, '0123456789') === \strlen($n) && ($n = (int) $n) > 0) {
            return $n;
        }
        return null;
    }
    function route($content, $path, $query, $hash) {
        return \Hook::fire('route.page', [$content, $path, $query, $hash]);
    }
    function route__page($content, $path, $query, $hash) {
        // This conditional statement is not mandatory, but it is a good practice to be stated given that hook(s) are
        // executed in order based on the `$stack` value. You could add a new route hook to overwrite the output of this
        // hook which will be executed after this hook execution. But that is not efficient because this hook need(s) to
        // be executed first, while the process inside it will not be used as it will be overwritten by your route hook
        // later. To speed up the process, you need to set a higher `$stack` value to your route hook, so that it can be
        // executed before this hook. Without this conditional statement, the output data of your previously executed
        // hook will be overwritten by this hook. This conditional statement passes the value immediately if the
        // previous hook value already contains the required response data (probably comes from the hook you added
        // before this hook), so that all process(es) after this statement will not be executed.
        if (null !== $content) {
            return $content;
        }
        \extract(\lot(), \EXTR_SKIP);
        $path = \trim($path ?? "", '/');
        $route = \trim($state->route ?? 'index', '/');
        $folder = \LOT . \D . 'page' . \D . \strtr($path ?: $route, '/', \D);
        if ($part = n($path ?: $route)) {
            $path = \substr($path, 0, -\strlen('/' . $part));
            $route = \substr($route, 0, -\strlen('/' . $part));
            if (\exist([
                $folder . '.archive',
                $folder . '.page'
            ], 1)) {
                $path .= '/' . $part;
                $route .= '/' . $part;
                unset($part);
            } else {
                $folder = \dirname($folder);
            }
        }
        $part = ($part ?? 0) - 1;
        if ($part <= 0 && $route === $path && !$query) {
            \kick('/'); // Redirect to home page
        }
        if ($file = \exist([
            $folder . '.archive',
            $folder . '.page'
        ], 1)) {
            $page = new \Page($file);
            $chunk = $page->chunk ?? 5;
            $deep = $page->deep ?? 0;
            $sort = \array_replace([1, 'path'], (array) ($page->sort ?? []));
            \lot('page', $page);
            \lot('t')[] = $page->title;
            \State::set([
                'chunk' => $chunk, // Inherit current page’s `chunk` property
                'deep' => $deep, // Inherit current page’s `deep` property
                'part' => $part + 1,
                'sort' => $sort // Inherit current page’s `sort` property
            ]);
            if ($part >= 0) {
                // The “pages” mode was disabled by an `.archive` or `.page` file
                if (\is_file($folder . \D . '.' . $page->x)) {
                    \kick('/' . $path);
                }
                if ($pages = $page->children('page', $deep)) {
                    $pages = $pages->sort($sort);
                } else {
                    $pages = new \Pages;
                }
                // A page “part” query was passed to the URL path, but this page has no sub-page(s). Treat it as a
                // single page with error state because requesting sub-page(s) on a single page is not allowed.
                if (0 === ($count = $pages->count)) { // Total number of page(s) before chunk
                    \State::set([
                        'has' => [
                            'next' => false,
                            'page' => false,
                            'pages' => false,
                            'parent' => true,
                            'prev' => false
                        ],
                        'is' => [
                            'error' => 404,
                            'page' => true,
                            'pages' => false
                        ]
                    ]);
                    \lot('page', new \Page);
                    \lot('t')[] = \i('Error');
                    return ['page', [], 404];
                }
                \State::set('count', $count);
                $pager = \Pager::from($pages);
                $pager->hash = $hash;
                $pager->path = $path ?: $route;
                $pager->query = $query;
                \lot('pager', $pager = $pager->chunk($chunk, $part));
                \lot('pages', $pages = $pages->chunk($chunk, $part));
                if (0 === ($count = $pages->count)) { // Total number of page(s) after chunk
                    \State::set([
                        'has' => [
                            'next' => false,
                            'page' => true,
                            'pages' => false,
                            'parent' => !!$page->parent,
                            'part' => $part >= 0,
                            'prev' => false
                        ],
                        'is' => [
                            'error' => 404,
                            'page' => false,
                            'pages' => true
                        ]
                    ]);
                    \lot('t')[] = \i('Error');
                    return ['pages', [], 404];
                }
                \State::set([
                    'has' => [
                        'next' => !!$pager->next,
                        'page' => true,
                        'pages' => true,
                        'parent' => !!$page->parent,
                        'part' => $part >= 0,
                        'prev' => !!$pager->prev
                    ],
                    'is' => [
                        'error' => false,
                        'page' => false,
                        'pages' => true
                    ]
                ]);
                return ['pages', [], 200];
            }
            \State::set([
                'has' => [
                    'next' => false,
                    'page' => true,
                    'pages' => true,
                    'parent' => !!$page->parent,
                    'prev' => false
                ],
                'is' => [
                    'error' => false,
                    'page' => true,
                    'pages' => false
                ]
            ]);
            return ['page', [], 200];
        }
        \State::set([
            'has' => [
                'next' => false,
                'page' => false,
                'pages' => false,
                'parent' => false,
                'prev' => false
            ],
            'is' => [
                'error' => 404,
                'page' => true,
                'pages' => false
            ]
        ]);
        \lot('t')[] = \i('Error');
        return ['page', [], 404];
    }
    \Hook::set('route', __NAMESPACE__ . "\\route", 100);
    \Hook::set('route.page', __NAMESPACE__ . "\\route__page", 100);
}
<?php

namespace {
    require __DIR__ . \D . 'engine' . \D . 'plug' . \D . 'to.php';
    // Set page’s condition data as early as possible, so that other
    // extension(s) can use it without having to enter the `route` hook
    $path = \trim($url->path ?? "", '/');
    $route = \trim($state->route ?? "", '/');
    $folder = \LOT . \D . 'page' . \D . (\preg_replace('/\/[1-9]\d*$/', "", $path) ?: $route);
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
    // Check if `pages` mode is disabled by a file like `.\lot\page\about\.page`
    $not_pages = \exist([
        $folder . \D . '.archive',
        $folder . \D . '.page'
    ], 1);
    \State::set([
        'has' => [
            'page' => $is_home || $is_page,
            'pages' => !!$has_pages,
            'parent' => $has_parent && false !== \strpos($path, '/'),
            'part' => !!\preg_match('/\/[1-9]\d*$/', $path)
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
    // Initialize response variable(s)
    $GLOBALS['page'] = new \Page;
    $GLOBALS['pager'] = new \Pager;
    $GLOBALS['pages'] = new \Pages;
    function route($content, $path, $query, $hash) {
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
        \extract($GLOBALS, \EXTR_SKIP);
        $path = \trim($path ?? "", '/');
        $route = \trim($state->route ?? "", '/');
        $folder = \LOT . \D . 'page' . \D . \strtr($path ?: $route, '/', \D);
        if ($path && \preg_match('/^(.*?)\/([1-9]\d*)$/', $path, $m)) {
            [$any, $path, $part] = $m;
            if (\exist([
                $folder . '.archive',
                $folder . '.page'
            ], 1)) {
                $path .= '/' . $part;
                unset($part);
            } else {
                $folder = \dirname($folder);
            }
        }
        $part = ((int) ($part ?? 1)) - 1;
        if ($part < 1 && $route === $path && !$query) {
            \kick('/'); // Redirect to home page
        }
        if ($file = \exist([
            $folder . '.archive',
            $folder . '.page'
        ], 1)) {
            $page = new \Page($file);
            $chunk = $page->chunk ?? 5;
            $deep = $page->deep ?? 0;
            $sort = $page->sort ?? [1, 'path'];
            $pages = \Pages::from($folder, 'page', $deep)->sort($sort);
            $GLOBALS['page'] = $page;
            $GLOBALS['t'][] = $page->title;
            \State::set([
                'chunk' => $chunk, // Inherit current page’s `chunk` property
                'count' => $count = $pages->count, // Return the total number of page(s)
                'deep' => $deep, // Inherit current page’s `deep` property
                'part' => $part + 1,
                'sort' => $sort // Inherit current page’s `sort` property
            ]);
            // No page(s) means “page” mode
            if (0 === $count || \is_file($folder . \D . '.' . $page->x)) {
                return ['page', [], 200];
            }
            $pager = \Pager::from($pages);
            $pager->hash = $hash;
            $pager->path = $path;
            $pager->query = $query;
            $GLOBALS['pager'] = $pager = $pager->chunk($chunk, $part);
            $GLOBALS['pages'] = $pages = $pages->chunk($chunk, $part);
            $count = $pages->count; // Total number of page(s) after chunk
            \State::set([
                'has' => [
                    'next' => !!$pager->next,
                    'page' => true,
                    'pages' => true,
                    'parent' => !!$page->parent,
                    'part' => !!($part + 1),
                    'prev' => !!$pager->prev
                ],
                'is' => [
                    'error' => $count ? false : 404,
                    'page' => false,
                    'pages' => true
                ]
            ]);
            return ['pages', [], $count ? 200 : 404];
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
        $GLOBALS['t'][] = i('Error');
        return ['page', [], 404];
    }
    \Hook::set('route', function (...$lot) {
        return \Hook::fire('route.page', $lot);
    }, 100);
    \Hook::set('route.page', __NAMESPACE__ . "\\route", 100);
}
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
    $route = \trim($state->route ?? 'index', '/');
    if ($part = \x\page\part($path = \trim($url->path ?? $route, '/'))) {
        $path = \substr($path, 0, -\strlen('/' . $part));
        $r = [];
        foreach (['json', 'markdown', 'md', 'mkd', 'txt', 'yaml', 'yml'] as $x) {
            $r[] = \LOT . \D . 'page' . \D . $path . \D . $part . '.' . $x;
            $r[] = \LOT . \D . 'page' . \D . '.archive' . \D . $path . \D . $part . '.' . $x;
        }
        if (\exist($r, 1)) {
            $path .= '/' . $part;
            unset($part);
        }
    }
    \State::set([
        'has' => ['part' => isset($part)],
        'is' => [
            'home' => "" === $path || $route === $path,
            'item' => !isset($part),
            'items' => isset($part)
        ]
    ]);
}

namespace x\page {
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
        $support = 'json,markdown,md,mkd,txt,yaml,yml';
        if ($part = part($path ?: $route)) {
            $path = \substr($path, 0, -\strlen('/' . $part));
            $route = \substr($route, 0, -\strlen('/' . $part));
            $r = [];
            foreach (\explode(',', $support) as $x) {
                $r[] = \LOT . \D . 'page' . \D . ($path ?: $route) . \D . $part . '.' . $x;
                $r[] = \LOT . \D . 'page' . \D . '.archive' . \D . ($path ?: $route) . \D . $part . '.' . $x;
            }
            if (\exist($r, 1)) {
                $path .= '/' . $part;
                $route .= '/' . $part;
                unset($part);
            }
        }
        $part = ($part ?? 0) - 1;
        if ($part <= 0 && $route === $path) {
            \kick('/' . $query . $hash); // Redirect to home page
        }
        $r = [];
        foreach (\explode(',', $support) as $x) {
            $r[] = \LOT . \D . 'page' . \D . ($path ?: $route) . '.' . $x;
            $r[] = \LOT . \D . 'page' . \D . '.archive' . \D . ($path ?: $route) . '.' . $x;
        }
        $y = "" !== $path ? '/' . $path : "";
        if ($file = \exist($r, 1)) {
            $page = new \Page($file, ['part' => $part + 1]);
            $chunk = $page->chunk ?? 5;
            $deep = $page->deep ?? 0;
            $sort = \array_replace([1, 'path'], (array) ($page->sort ?? []));
            \lot('page', $page);
            \lot('t')[] = $page->title;
            \State::set('has', [
                'items' => $part < 0 && \q($page->children($support)) > 0,
                'parent' => !!$page->parent
            ]);
            if ($part >= 0) {
                // The “items” view was disabled by a dot file
                if (\is_file(\dirname($file) . \D . \pathinfo($file, \PATHINFO_FILENAME) . \D . '.' . $page->x)) {
                    \kick('/' . $path);
                }
                if ($pages = $page->children($support, $deep)) {
                    $pages = $pages->sort($sort);
                } else {
                    $pages = new \Pages;
                }
                $pager = \Pager::from($pages);
                $pager->hash = $hash;
                $pager->path = $path ?: $route;
                $pager->query = $query;
                \lot('pager', $pager = $pager->chunk($chunk, $part));
                \lot('pages', $pages = $pages->chunk($chunk, $part));
                if (0 === ($count = \q($pages))) {
                    \lot('t')[] = \i('Error');
                }
                \State::set([
                    'has' => [
                        'next' => !!$pager->next,
                        'prev' => !!$pager->prev
                    ],
                    'is' => ['error' => 0 === $count ? 404 : false],
                    'with' => ['items' => $count > 0]
                ]);
                return ['pages' . $y, [], 0 === $count ? 404 : 200];
            }
            return ['page' . $y, [], 200];
        }
        \lot('t')[] = \i('Error');
        \State::set('is.error', 404);
        return ['page' . $y, [], 404];
    }
    \Hook::set('route', __NAMESPACE__ . "\\route", 100);
    \Hook::set('route.page', __NAMESPACE__ . "\\route__page", 100);
}
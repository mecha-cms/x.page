<?php namespace x\page;

// This file defines function(s) that need to be available immediately. For instance, function `x\page\part()` is often
// used in Page-related extension(s). For this reason, it has to be made available immediately in all extension(s).
// If this function is declared in an extension’s `.\index.php` file, it generally becomes available rather late. By
// that time, related extension(s) that load before the Page extension may already be trying to use it. Declaring
// function(s) in the `.\engine\plug\hook.php` file loads them automatically when the `Hook` class is used, which occurs
// even before extension file(s) start coming in.

// Returns the number string at the end of the `$path` as an integer if present, else returns `null`
function part($path) {
    $part = \trim(\strrchr($path, '/') ?: $path, '/');
    if ("" !== $part && '0' !== $part[0] && \strspn($part, '0123456789') === \strlen($part) && ($part = (int) $part) > 0) {
        return $part;
    }
    return null;
}
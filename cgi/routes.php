<?php

use Symfony\Component\Routing\Route;

$langs = explode(',', config('global.langs'));
$prefix = '/' . implode('|/', $langs) . '|';

return array(
    'home' => (
        new Route(
            '{lang}/',
            array(
                '_invoker' => 'globalInvoker',
                '_settings' => [
                    'cache' => [
                        'key' => [
                            'route' => 'home',
                            'lang' => '{config:global.lang}',
                            'url' => '{url}',
                            'page' => '{get:page:1}',
                        ],
                        'expire' => 3600,
                    ],
                ],
                '_callstack' => [['Ejz\Cache', 'check'], 'homeController'],
            ),
            array(
                'lang' => $prefix
            ),
            array(),
            '',
            array(),
            array('GET')
        )
    ),
    'post' => (
        new Route(
            '{lang}/{post}',
            array(
                '_invoker' => 'globalInvoker',
                '_settings' => [],
                '_callstack' => 'postController'
            ),
            array(
                'lang' => $prefix,
                'post' => '\d+(/[^/]*)?'
            ),
            array(),
            '',
            array(),
            array('GET')
        )
    ),
);

<?php

use Ejz\Cache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

function homeController(& $vars) {
    $request = $vars['request'];
    $session = $vars['session'];
    $settings = $vars['settings'];
    $page = $request->query->get('page', 1);
    $limit = ($page - 1) * PAGINATOR_LIMIT . ', ' . PAGINATOR_LIMIT;
    $count = SQL("SELECT COUNT(*) AS c FROM post WHERE stamp <= %s", curdate());
    $count = $count[0]['c'];
    $posts = SQL("SELECT * FROM post WHERE stamp <= %s ORDER BY stamp DESC LIMIT {$limit}", curdate());
    $paginator = paginator(array($page, ceil($count / PAGINATOR_LIMIT)), $request);
    $vars['paginator'] = $paginator['echo'];
    $vars['title'] = _T('site-title');
    $vars['description'] = _T('site-description');
    $vars['keywords'] = _T('site-keywords');
    if (!empty($paginator['prev'])) {
        $scheme = $request->getScheme();
        $host = $request->getHost();
        $vars['linkprev'] = "{$scheme}://{$host}{$paginator['prev']}";
    }
    if (!empty($paginator['next'])) {
        $scheme = $request->getScheme();
        $host = $request->getHost();
        $vars['linknext'] = "{$scheme}://{$host}{$paginator['next']}";
    }
    $vars['posts'] = $posts;
}

function postController(& $vars) {
    $request = $vars['request'];
    $session = $vars['session'];
    $id = intval($vars['post']);
    $post = SQL("SELECT * FROM post WHERE post_id = %s", $id);
    // if (!$post) return new Response('NOT FOUND!', 404);
    $post = $post[0];
    $vars['title'] = $post['name'] . ' / Ejz';
    $url = $request->getPathInfo();
    if (($_ = "/{$id}/{$post['url']}") != $url) {
        $requestUri = $request->getRequestUri();
        $url = str_replace($url, $_, $requestUri);
        return new RedirectResponse($url, 302);
    }
    $vars['post'] = $post;
}

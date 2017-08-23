<?php

require_once(__DIR__ . '/bootstrap.php');

use Ejz\Cache;
use Symfony\Component\Routing;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;

function main(Request $request) {
    $global_default_lang = config('global.default_lang');
    config('global.lang', $global_default_lang);
    $langs = config('global.langs');
    $langs = explode(',', $langs);

    // ----------- //
    //  Redirects  //
    // ----------- //

    $pathInfo = $request->getPathInfo();
    $requestUri = $request->getRequestUri();
    if ($pathInfo != ($url = preg_replace('~/+~', '/', $pathInfo))) {
        $url = str_replace_once($pathInfo, $url, $requestUri);
        return new RedirectResponse($url, 301);
    }
    if (stripos($pathInfo, "/{$global_default_lang}/") === 0 or $pathInfo === "/{$global_default_lang}") {
        $url = substr($pathInfo, strlen("/{$global_default_lang}"));
        $url = str_replace_once($pathInfo, $url ?: '/', $requestUri);
        return new RedirectResponse($url, 301);
    }
    if (in_array(substr($pathInfo, 1), $langs)) {
        $url = str_replace_once($pathInfo, $pathInfo . '/', $requestUri);
        return new RedirectResponse($url, 301);
    }

    // ----- //
    //  401  //
    // ----- //

    $ip = $request->getClientIp();
    if (($_401 = config("401")) and !defined('STDIN') and $request->getMethod() == "GET" and is_ip($ip, $allow_private = false)) {
        $user = $request->server->get('PHP_AUTH_USER', '');
        $pass = $request->server->get('PHP_AUTH_PW', '');
        if (!$user or !$pass or $user != $_401['user'] or $pass != $_401['pass']) {
            $response = new Response("<h3>Forbidden!</h3>", 401);
            $response->headers->set(
                'WWW-Authenticate', sprintf('Basic realm="%s"', $_401['realm'])
            );
            return $response;
        }
    }

    // -------- //
    //  Routes  //
    // -------- //

    $context = new Routing\RequestContext();
    $context->fromRequest($request);
    $route = new Routing\RouteCollection();
    foreach (getRoutes() as $n => $r) $route->add($n, $r);

    // ------------- //
    //  Match route  //
    // ------------- //

    $url = $request->getPathInfo();
    try {
        url:
        $matcher = new Routing\Matcher\UrlMatcher($route, $context);
        $request->attributes->add($matcher->match($url));
        $invoker = $request->attributes->get('_invoker', '');
        $settings = $request->attributes->get('_settings', array());
        $callstack = $request->attributes->get('_callstack', '');
        $response = call_user_func_array($invoker, array($request, $settings, $callstack));
        if ($response instanceof Response) ; else return;
        if (!empty($_403)) $response->setStatusCode(403);
        if (!empty($_404)) $response->setStatusCode(404);
        return $response;
    } catch (ResourceNotFoundException $e) {
        $_404 = true;
        preg_match('~^/(\w{2})(/|$)~', $url, $match);
        $url = ($match ? '/' . $match[1] : '') . '/system/404.html';
        goto url;
    } catch (NotFoundHttpException $e) {
        $_404 = true;
        preg_match('~^/(\w{2})(/|$)~', $url, $match);
        $url = ($match ? '/' . $match[1] : '') . '/system/404.html';
        goto url;
    } catch (AccessDeniedHttpException $e) {
        $_403 = true;
        preg_match('~^/(\w{2})(/|$)~', $url, $match);
        $url = ($match ? '/' . $match[1] : '') . '/system/403.html';
        goto url;
    } catch (MethodNotAllowedException $e) {
        return new Response($e->getMessage() ?: "MethodNotAllowedException", 405);
    }
}

$request = getRequest();
$response = main($request);
if ($response instanceof Response) {
    if (!defined('STDIN')) {
        $ip = $request->getClientIp();
        if (is_ip($ip, $allow_private = false) and config('global.stat')) {
            $session = $request->getSession();
            $me = $session->get('me', array());
            $uid = !empty($me['user_id']) ? $me['user_id'] : '';
            $ts = time();
            $loop = 3600;
            $one = intval($ts / $loop) * $loop;
            $two = $one + $loop - 1;
            R('LPUSH', sprintf('stat_%s_%s', $one, $two), json_encode([
                'ts' => $ts,
                'ip' => $ip,
                'type' => $request->getMethod(),
                'ref' => $request->headers->get('Referer') ?: '',
                'ua' => $request->headers->get('User-Agent') ?: '',
                'host' => $request->getHost(),
                'uri' => $request->getRequestUri(),
                'sess' => $session->getId(),
                'uid' => $uid,
                'code' => $response->getStatusCode(),
                'tt' => round(microtime(true) - START_TIME, 2),
            ]));
        }
        $response->send();
    } else {
        ob_start();
        $response->send();
        $ob = ob_get_clean();
        echo trim($ob), "\n";
    }
}

<?php

use BrizyDeploy\Modal\DeployRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use BrizyDeploy\Utils\HttpUtils;
use BrizyDeploy\Deploy;
use BrizyDeploy\Modal\AppRepository;

$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    echo "The 'vendor' folder is missing. You must run 'composer update' to resolve application dependencies.\nPlease see the README for more information.\n";
    exit;
}

require $composerAutoload;

require_once __DIR__ . '/app/Kernel.php';
require_once 'app/utils.php';

$request = Request::createFromGlobals();

$appRepository = new AppRepository();
$app = $appRepository->get();
if (!$app || !$app->getInstalled() || !Kernel::isInstalled()) {
    $response = new RedirectResponse(HttpUtils::getBaseUrl(
        $request,
        '',
        '/install_step_1.php'
    ));

    $response->setPrivate();
    $response->setMaxAge(0);
    $response->headers->addCacheControlDirective('must-revalidate', true);
    $response->headers->addCacheControlDirective('no-store', true);
    $response->send();
    exit;
}

$baseUrl = HttpUtils::getBaseUrl(
    $request,
    '',
    ''
);

if ($app->getBaseUrl() != $baseUrl) {
    (new JsonResponse(['message' => 'Invalid base url'], 400))->send();
    exit;
}

$deployRepository = new DeployRepository();
$deploy = $deployRepository->get();
if ($deploy && $deploy->getExecute()) {
    if ($deploy->getZipInfoTimestamp() !== null && (time() - Deploy::ZIP_INFO_INTERVAL) < $deploy->getZipInfoTimestamp()) {
        (new Response('Synchronization in processing. It can take some time...', 400))->send();
        exit;
    }
    $deployService = new Deploy($app->getDeployUrl(), $app->getAppId());
    $zipInfo = $deployService->getZipInfo();
    $deploy->setZipInfoTimestamp(time());
    $deployRepository->update($deploy);
    if (!$zipInfo) {
        (new Response('Synchronization in processing. It can take some time.', 400))->send();
        exit;
    }

    try {
        $deployed = $deployService->execute();
    } catch (\Exception $e) {
        (new Response($e->getMessage() . " in " . $e->getFile(), 400))->send();
        exit;
    }

    if (!$deployed) {
        (new JsonResponse($deployService->getErrors(), 400))->send();
        exit;
    }

    $deploy->setExecute(false);
    $deploy->setDeployTimestamp(time());
    $deployRepository->update($deploy);
}

if (!$page = $request->query->get('page')) {
    $page = 'index';
}

if ($deploy && $deploy->getUpdate()) {
    $deployService = new Deploy($app->getDeployUrl(), $app->getAppId());
    if ((time() - Deploy::ZIP_INFO_INTERVAL) > $deploy->getZipInfoTimestamp()) {
        $zipInfo = $deployService->getZipInfo();
        $deploy->setZipInfoTimestamp(time());
        $deployRepository->update($deploy);
        if ($zipInfo) {
            $deploy->setExecute(true);
            $deploy->setUpdate(false);
            $deploy->setZipInfoTimestamp(null);
            $deployRepository->update($deploy);
            $page != 'index' ? $route_to = '/' . $page : $route_to = '/';
            $response = new RedirectResponse(HttpUtils::getBaseUrl(
                $request,
                '',
                $route_to
            ));

            $response->setPrivate();
            $response->setMaxAge(0);
            $response->headers->addCacheControlDirective('must-revalidate', true);
            $response->headers->addCacheControlDirective('no-store', true);
            $response->send();
            exit;
        }
    }
}
$file_name = __DIR__ . '/cache/' . $page . '.html';
if ($page != 'index' && !file_exists($file_name)) {
    $response = new RedirectResponse(HttpUtils::getBaseUrl(
        $request,
        '',
        ''
    ));

    $response->setPrivate();
    $response->setMaxAge(0);
    $response->headers->addCacheControlDirective('must-revalidate', true);
    $response->headers->addCacheControlDirective('no-store', true);
    $response->send();
    exit;
}

$html = file_get_contents($file_name);
if (!$html) {
    (new Response("Page was not found", 404))->send();
    exit;
}

$url = $request->getUri();
$html = str_replace(
    [
        '{{ brizy_dc_page_language }}',
        '{{ brizy_dc_current_page_unique_url }}',
        '{{ brizy_dc_group_language }}',
        '{{ site_url }}'
    ],
    [
        'en',
        $url,
        'en',
        $url
    ],
    $html
);

(new Response($html, 200))->send();
exit;

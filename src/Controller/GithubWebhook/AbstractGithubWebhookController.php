<?php
declare(strict_types=1);

namespace App\Controller\GithubWebhook;

use App\Controller\AbstractController;
use App\Github\AbstractWebhookRequestValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractGithubWebhookController extends AbstractController
{
    public function __invoke(Request $request): Response
    {
        return $this->handleApiResponse(function () use ($request): ?Response {
            $payload = $this->getRequestValidator()->validateRequest($request);

            return $this->doHandleWebhook($request, $payload);
        });
    }

    abstract protected function doHandleWebhook(Request $request, array $payload): ?Response;

    abstract protected function getRequestValidator(): AbstractWebhookRequestValidator;
}
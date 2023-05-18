<?php
declare(strict_types=1);

namespace App\Github;

use App\Helper\NonEmptyStringHelper;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractWebhookRequestValidator
{
    private const REQUIRED_HEADERS = [
        'x-github-delivery',
        'x-github-event',
        'x-github-hook-id',
        'x-github-hook-installation-target-id',
        'x-github-hook-installation-target-type',
    ];

    public function validateRequest(Request $request): array
    {
        foreach (self::REQUIRED_HEADERS as $header) {
            $headerValue = $request->headers->get($header);

            if (NonEmptyStringHelper::valid($headerValue) === false) {
                throw new \RuntimeException('Missing required header');
            }
        }

        return $this->doValidateRequest($request);
    }

    abstract protected function doValidateRequest(Request $request): array;
}
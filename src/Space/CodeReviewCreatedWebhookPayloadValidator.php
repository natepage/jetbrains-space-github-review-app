<?php
declare(strict_types=1);

namespace App\Space;

use App\Helper\NonEmptyStringHelper;

final class CodeReviewCreatedWebhookPayloadValidator
{
    public function __construct(private readonly string $spaceClientId)
    {
    }

    public function validate(array $payload): array
    {
        if (($payload['clientId'] ?? null) !== $this->spaceClientId) {
            throw new \RuntimeException('Invalid clientId');
        }

        if (($payload['payload']['className'] ?? null) !== 'CodeReviewWebhookEvent') {
            throw new \RuntimeException('Invalid payload class');
        }

        if (($payload['payload']['meta']['method'] ?? null) !== 'Created') {
            throw new \RuntimeException('Invalid method');
        }

        if (NonEmptyStringHelper::valid($payload['payload']['review']['id'] ?? null) === false) {
            throw new \RuntimeException('Invalid review id');
        }

        return [
            'reviewId' => $payload['payload']['review']['id'],
        ];
    }
}
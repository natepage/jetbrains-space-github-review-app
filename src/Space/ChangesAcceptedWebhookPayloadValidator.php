<?php
declare(strict_types=1);

namespace App\Space;

use App\Helper\NonEmptyStringHelper;

final class ChangesAcceptedWebhookPayloadValidator
{
    public function __construct(private readonly string $spaceClientId)
    {
    }

    public function validate(array $payload): array
    {
        if (($payload['clientId'] ?? null) !== $this->spaceClientId) {
            throw new \RuntimeException('Invalid clientId');
        }

        if (($payload['payload']['className'] ?? null) !== 'CodeReviewParticipantWebhookEvent') {
            throw new \RuntimeException('Invalid payload class');
        }

        if (NonEmptyStringHelper::valid($payload['payload']['review']['id'] ?? null) === false) {
            throw new \RuntimeException('Invalid review id');
        }

        if (NonEmptyStringHelper::valid($payload['payload']['participant']['id'] ?? null) === false) {
            throw new \RuntimeException('Invalid review id');
        }

        if (($payload['payload']['reviewerState']['new'] ?? null) !== 'Accepted') {
            throw new \RuntimeException('Invalid reviewer state');
        }

        return [
            'reviewId' => $payload['payload']['review']['id'],
            'participantId' => $payload['payload']['participant']['id'],
        ];
    }
}
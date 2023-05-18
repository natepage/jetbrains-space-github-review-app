<?php
declare(strict_types=1);

namespace App\Github;

use App\Helper\NonEmptyStringHelper;
use Symfony\Component\HttpFoundation\Request;

final class PullRequestReviewRequestValidator extends AbstractWebhookRequestValidator
{
    private const EVENT_NAME = 'pull_request_review';

    private const SUPPORTED_ACTIONS = [
        'edited',
        'submitted',
    ];

    protected function doValidateRequest(Request $request): array
    {
        if ($request->headers->get('x-github-event') !== self::EVENT_NAME) {
            throw new \RuntimeException('Invalid event name');
        }

        $payload = $request->toArray();

        if (\in_array(($payload['action'] ?? ''), self::SUPPORTED_ACTIONS, true) === false) {
            throw new \RuntimeException('Invalid action');
        }

        if (NonEmptyStringHelper::valid((string)($payload['review']['id'] ?? '')) === false) {
            throw new \RuntimeException('Invalid review id');
        }

        if (NonEmptyStringHelper::valid((string)($payload['pull_request']['url'] ?? '')) === false) {
            throw new \RuntimeException('Invalid pull request url');
        }

        $explodedPrUrl = \explode('/', $payload['pull_request']['url']);
        $prId = \array_pop($explodedPrUrl);

        if (NonEmptyStringHelper::valid($prId ?? '') === false) {
            throw new \RuntimeException('Invalid pull request id');
        }

        if (NonEmptyStringHelper::valid((string)($payload['repository']['name'] ?? '')) === false) {
            throw new \RuntimeException('Invalid repository name');
        }

        $userLogin = $payload['review']['user']['login'] ?? $payload['sender']['login'] ?? '';

        if (NonEmptyStringHelper::valid((string)$userLogin) === false) {
            throw new \RuntimeException('Invalid user login');
        }

        return [
            'body' => $payload['review']['body'] ?? null,
            'pullRequestAuthor' => $payload['pull_request']['user']['login'] ?? null,
            'pullRequestId' => $prId,
            'repoName' => $payload['repository']['name'],
            'reviewId' => $payload['review']['id'],
            'reviewState' => $payload['review']['state'] ?? null,
            'userLogin' => $userLogin,
        ];
    }
}
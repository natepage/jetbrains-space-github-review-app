<?php
declare(strict_types=1);

namespace App\Controller\GithubWebhook;

use App\Github\AbstractWebhookRequestValidator;
use App\Github\PullRequestReviewBodyUpdater;
use App\Github\PullRequestReviewRequestValidator;
use App\Github\UserAccessTokenFinder as GithubUserAccessTokenFinder;
use App\Helper\NonEmptyStringHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function Symfony\Component\String\u;

#[Route(path: '/webhook/github', name: 'incoming_github_webhook', methods: ['POST'])]
final class PullRequestReviewCreatedGithubWebhookController extends AbstractGithubWebhookController
{
    private const BODY_PLACEHOLDER = '__eonx_placeholder__';

    private const REVIEW_STATE = 'approved';

    public function __construct(
        private readonly PullRequestReviewBodyUpdater $pullRequestReviewBodyUpdater,
        private readonly GithubUserAccessTokenFinder $userAccessTokenFinder
    ) {
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    protected function doHandleWebhook(Request $request, array $payload): ?Response
    {
        // If review state isn't approved, then ignore it
        if (NonEmptyStringHelper::valid((string)($payload['reviewState'] ?? '')) === false
            || \strtolower($payload['reviewState']) !== self::REVIEW_STATE) {
            return null;
        }

        // If review body isn't placeholder, then ignore it
        if (NonEmptyStringHelper::valid((string)($payload['body'] ?? '')) === false
            || u($payload['body'])->ignoreCase()->containsAny(self::BODY_PLACEHOLDER) === false) {
            return null;
        }

        // Find user access token
        $accessToken = $this->userAccessTokenFinder->findByUsername($payload['userLogin']);

        // Update review body with generated content
        $this->pullRequestReviewBodyUpdater->updateReview($payload, $accessToken);

        return null;
    }

    protected function getRequestValidator(): AbstractWebhookRequestValidator
    {
        return new PullRequestReviewRequestValidator();
    }
}
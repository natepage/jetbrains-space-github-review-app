<?php
declare(strict_types=1);

namespace App\Controller\SpaceWebhook;

use App\Github\AppInstallationAccessTokenProvider as GithubAppInstallationAccessTokenProvider;
use App\Github\IssueCommentFactory as GithubIssueCommentFactory;
use App\Space\CodeReviewDetailsFinder as SpaceCodeReviewDetailsFinder;
use App\Space\CodeReviewCreatedWebhookPayloadValidator as SpaceWebhookPayloadValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/webhook/space/code-review-created', name: 'space_code_review_created_webhook', methods: ['POST'])]
final class CodeReviewCreatedSpaceWebhookController extends AbstractSpaceWebhookController
{
    public function __construct(
        private readonly SpaceCodeReviewDetailsFinder $codeReviewDetailsFinder,
        private readonly SpaceWebhookPayloadValidator $webhookPayloadValidator,
        private readonly GithubIssueCommentFactory $issueCommentFactory,
        private readonly GithubAppInstallationAccessTokenProvider $appInstallationAccessTokenProvider
    ) {
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    protected function doHandleWebhook(Request $request): ?Response
    {
        // Resolve review ID from payload
        $payload = $this->webhookPayloadValidator->validate($request->toArray());

        // Resolve review details from Space API
        $review = $this->codeReviewDetailsFinder->findById($payload['reviewId']);

        // Get AccessToken for the app installation
        $accessToken = $this->appInstallationAccessTokenProvider->create();

        // Create comment in GitHub on the PR
        $this->issueCommentFactory->createComment($review, $accessToken);

        return null;
    }
}
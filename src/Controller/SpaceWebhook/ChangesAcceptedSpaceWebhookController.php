<?php
declare(strict_types=1);

namespace App\Controller\SpaceWebhook;

use App\Github\PullRequestReviewFactory as GithubPullRequestReviewFactory;
use App\Github\UserAccessTokenFinder as GithubUserAccessTokenFinder;
use App\Github\UsernameFinder as GithubUsernameFinder;
use App\Space\CodeReviewDetailsFinder as SpaceCodeReviewDetailsFinder;
use App\Space\UserEmailAddressFinder as SpaceUserEmailAddressFinder;
use App\Space\ChangesAcceptedWebhookPayloadValidator as SpaceWebhookPayloadValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/webhook/space', name: 'incoming_space_webhook', methods: ['POST'])]
final class ChangesAcceptedSpaceWebhookController extends AbstractSpaceWebhookController
{
    public function __construct(
        private readonly SpaceCodeReviewDetailsFinder $codeReviewDetailsFinder,
        private readonly SpaceWebhookPayloadValidator $webhookPayloadValidator,
        private readonly SpaceUserEmailAddressFinder $emailAddressFinder,
        private readonly GithubUsernameFinder $usernameFinder,
        private readonly GithubPullRequestReviewFactory $pullRequestReviewFactory,
        private readonly GithubUserAccessTokenFinder $userAccessTokenFinder,
    ) {
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     */
    protected function doHandleWebhook(Request $request): ?Response
    {
        // Resolve review and participant IDs from payload
        $payload = $this->webhookPayloadValidator->validate($request->toArray());

        // Resolve participant email address from Space API
        $email = $this->emailAddressFinder->findById($payload['participantId']);

        // Resolve review details from Space API
        $review = $this->codeReviewDetailsFinder->findById($payload['reviewId']);

        // Resolve participant GitHub username from GitHub API
        $review['github']['username'] = $this->usernameFinder->findByEmail($email);

        // Resolve GitHub access token for participant
        $accessToken = $this->userAccessTokenFinder->findByUsername($review['github']['username']);

        // Create review on GitHub
        $this->pullRequestReviewFactory->createReview($review, $accessToken);

        return null;
    }
}
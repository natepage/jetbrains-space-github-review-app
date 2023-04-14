<?php
declare(strict_types=1);

namespace App\Controller;

use App\Github\PullRequestReviewFactory as GithubPullRequestReviewFactory;
use App\Github\UsernameFinder as GithubUsernameFinder;
use App\Space\CodeReviewDetailsFinder as SpaceCodeReviewDetailsFinder;
use App\Space\PublicKeySignatureVerifier as SpacePublicKeySignatureVerifier;
use App\Space\UserEmailAddressFinder as SpaceUserEmailAddressFinder;
use App\Space\WebhookPayloadValidator as SpaceWebhookPayloadValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/webhook/space', name: 'incoming_space_webhook', methods: ['POST'])]
final class IncomingSpaceWebhookController extends AbstractController
{
    public function __construct(
        private readonly SpaceCodeReviewDetailsFinder $codeReviewDetailsFinder,
        private readonly SpacePublicKeySignatureVerifier $publicKeySignatureVerifier,
        private readonly SpaceWebhookPayloadValidator $webhookPayloadValidator,
        private readonly SpaceUserEmailAddressFinder $emailAddressFinder,
        private readonly GithubUsernameFinder $usernameFinder,
        private readonly GithubPullRequestReviewFactory $pullRequestReviewFactory,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        return $this->handleApiResponse(function () use ($request) {
            // Make sure request is coming from Space
            $this->publicKeySignatureVerifier->isSignatureValidForRequest($request);

            // Resolve review and participant IDs from payload
            $payload = $this->webhookPayloadValidator->validate($request->toArray());

            // Resolve participant email address from Space API
            $email = $this->emailAddressFinder->findById($payload['participantId']);

            // Resolve review details from Space API
            $review = $this->codeReviewDetailsFinder->findById($payload['reviewId']);

            // Resolve participant GitHub username from GitHub API
            $review['github']['username'] = $this->usernameFinder->findByEmail($email);

            // Create review on GitHub
            $this->pullRequestReviewFactory->createReview($review);
        });
    }

    private function handleApiResponse(callable $func): Response
    {
        try {
            $func();
        } catch (\Throwable $throwable) {
            return new JsonResponse(['message' => $throwable->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['message' => 'OK'], Response::HTTP_OK);
    }
}
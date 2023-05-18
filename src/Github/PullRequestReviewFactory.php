<?php
declare(strict_types=1);

namespace App\Github;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PullRequestReviewFactory
{
    private const CREATE_REVIEW_URL = 'https://api.github.com/repos/eonx-com/%s/pulls/%s/reviews';

    public function __construct(
        private readonly string $githubAccessToken,
        private readonly HttpClientInterface $githubClient,
        private readonly PullRequestReviewBodyFactory $pullRequestReviewBodyFactory
    ) {
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function createReview(array $data, GithubAccessToken $accessToken): void
    {
        $url = \sprintf(self::CREATE_REVIEW_URL, $data['github']['repository'], $data['github']['id']);

        try {
            $this->sendCreateReviewRequest($accessToken->getAccessToken(), $url, $this->getBody($data, $accessToken->isDefault()));
        } catch (\Throwable $throwable) {
            $this->sendCreateReviewRequest($this->githubAccessToken, $url, $this->getBody($data, true));
        }
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Exception
     */
    private function getBody(array $data, bool $isDefaultToken): string
    {
        return $this->pullRequestReviewBodyFactory->create($data, $isDefaultToken);
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function sendCreateReviewRequest(string $accessToken, string $url, string $body): void
    {
        // Call getHeaders() to trigger exception if request fails
        $this->githubClient->request('POST', $url, [
            'auth_bearer' => $accessToken,
            'json' => [
                'event' => 'APPROVE',
                'body' => $body,
            ],
        ])->getHeaders();
    }
}
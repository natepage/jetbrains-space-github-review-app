<?php
declare(strict_types=1);

namespace App\Github;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PullRequestReviewBodyUpdater
{
    private const UPDATE_REVIEW_URL = 'https://api.github.com/repos/eonx-com/%s/pulls/%s/reviews/%s';

    public function __construct(
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
    public function updateReview(array $data, GithubAccessToken $accessToken): void
    {
        $url = \sprintf(self::UPDATE_REVIEW_URL, $data['repoName'], $data['pullRequestId'], $data['reviewId']);

        // Format data to work with body factory
        $data['github'] = [
            'id' => $data['pullRequestId'],
            'repository' => $data['repoName'],
            'username' => $data['userLogin'],
        ];

        $body = $this->pullRequestReviewBodyFactory->create($data, $accessToken->isDefault());

        $this->sendUpdateReviewRequest($accessToken->getAccessToken(), $url, $body);
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function sendUpdateReviewRequest(string $accessToken, string $url, string $body): void
    {
        // Call getHeaders() to trigger exception if request fails
        $this->githubClient->request('PUT', $url, [
            'auth_bearer' => $accessToken,
            'json' => [
                'body' => $body,
            ],
        ])->getHeaders();
    }
}
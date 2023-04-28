<?php
declare(strict_types=1);

namespace App\Github;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PullRequestReviewFactory
{
    private const URL = 'https://api.github.com/repos/eonx-com/%s/pulls/%s/reviews';

    public function __construct(
        private readonly string $githubAccessToken,
        private readonly HttpClientInterface $githubClient
    ) {
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function createReview(array $data, GithubAccessToken $accessToken): void
    {
        $url = \sprintf(self::URL, $data['github']['repository'], $data['github']['id']);

        try {
            $this->sendRequest($accessToken->getAccessToken(), $url, $this->getBody($data, $accessToken->isDefault()));
        } catch (\Throwable $throwable) {
            $this->sendRequest($this->githubAccessToken, $url, $this->getBody($data, true));
        }
    }

    private function getBody(array $data, bool $isDefaultToken): string
    {
        $approved = $isDefaultToken ? 'Approved' : \sprintf('@%s approved', $data['github']['username']);

        return \sprintf(
            "%s 👌 | [Space - %s](%s).",
            $approved,
            $data['space']['number'],
            $data['space']['url'],
        );
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function sendRequest(string $accessToken, string $url, string $body): void
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
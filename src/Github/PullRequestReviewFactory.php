<?php
declare(strict_types=1);

namespace App\Github;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PullRequestReviewFactory
{
    private const CREATE_REVIEW_URL = 'https://api.github.com/repos/eonx-com/%s/pulls/%s/reviews';

    private const FETCH_PULL_REQUEST_URL = 'https://api.github.com/repos/eonx-com/%s/pulls/%s';

    public function __construct(
        private readonly string $githubAccessToken,
        private readonly HttpClientInterface $githubClient
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
        $approvalMessage = $isDefaultToken
            ? \sprintf('@%s approved this change', $data['github']['username'])
            : \array_rand(\array_flip(ApprovalMessagesInterface::MESSAGES));

        // Prevent create review for your own PR
        $pullRequest = $this->fetchPullRequest($data);
        if ($pullRequest['user']['login'] === $data['github']['username']) {
            throw new \Exception('You cannot approve your own PR');
        }

        // Randomly thank author of the pull request
        if (\random_int(0, 3) === 1) {
            $approvalMessage = \sprintf("Thanks @%s. %s", $pullRequest['user']['login'], $approvalMessage);
        }

        $lines = \array_map(static fn (string $line): string => \sprintf('- [X] %s', $line), [
            'Functionality and requirements',
            'Code organization and structure',
            'Code readability and maintainability',
            'Error handling and exception management',
            'Security considerations (POL-015)',
        ]);

        \array_unshift($lines, \sprintf("%s" . \PHP_EOL, $approvalMessage));

        return \implode(\PHP_EOL, $lines);
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function fetchPullRequest(array $data): array
    {
        $url = \sprintf(self::FETCH_PULL_REQUEST_URL, $data['github']['repository'], $data['github']['id']);

        return $this->githubClient->request('GET', $url, [
            'auth_bearer' => $this->githubAccessToken,
        ])->toArray();
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
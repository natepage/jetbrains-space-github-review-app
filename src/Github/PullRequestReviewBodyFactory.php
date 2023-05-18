<?php
declare(strict_types=1);

namespace App\Github;

use App\Helper\NonEmptyStringHelper;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PullRequestReviewBodyFactory
{
    private const CODE_REVIEW_CHECKLIST = [
        'Functionality and requirements',
        'Code organization and structure',
        'Code readability and maintainability',
        'Error handling and exception management',
        'Security considerations (POL-015)',
    ];

    private const FETCH_PULL_REQUEST_URL = 'https://api.github.com/repos/eonx-com/%s/pulls/%s';

    public function __construct(
        private readonly string $githubAccessToken,
        private readonly HttpClientInterface $githubClient
    ) {
    }

    /**
     * @throws \Exception
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function create(array $data, bool $isDefaultToken): string
    {
        $approvalMessage = $isDefaultToken
            ? \sprintf('@%s approved this change', $data['github']['username'])
            : \array_rand(\array_flip(ApprovalMessagesInterface::MESSAGES));

        // Resolve PR author
        $pullRequestAuthor = $data['pullRequestAuthor'] ?? null;
        if (NonEmptyStringHelper::valid($pullRequestAuthor) === false) {
            // Prevent requesting PR details if already provided
            $pullRequest = $this->fetchPullRequest($data);
            $pullRequestAuthor = $pullRequest['user']['login'];
        }

        // Prevent create review for your own PR
        if ($pullRequestAuthor === $data['github']['username']) {
            throw new \Exception('You cannot approve your own PR');
        }

        // Randomly thank author of the pull request
        if (\random_int(0, 3) === 1) {
            $approvalMessage = \sprintf("Thanks @%s. %s", $pullRequestAuthor, $approvalMessage);
        }

        $lines = \array_map(static fn (string $line): string => \sprintf('- [X] %s', $line), self::CODE_REVIEW_CHECKLIST);

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
}
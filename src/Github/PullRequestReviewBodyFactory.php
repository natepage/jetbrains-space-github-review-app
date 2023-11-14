<?php
declare(strict_types=1);

namespace App\Github;

use App\Github\Exceptions\CannotApproveOwnPrException;
use App\Helper\NonEmptyStringHelper;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PullRequestReviewBodyFactory
{
    private const CODE_REVIEW_CHECKLIST = [
        'Protect from Injection attacks',
        'Protect data with proper input validation, and protect against buffer overflows, pointers/shared data',
        'Protect with appropriate encryption and cryptography (E.g. Appropriate hashing, symmetric encryption used, ciphers) if applicable',
        'Protect against XSS and CSRF',
        'Ensure that pages, data access etc, are written with appropriate access control authorisation and authentication requirements',
        'Ensure all important errors and business logic cases are handled',
        'Ensure forwards and redirects are handled',
        'Ensure no sensitive data is exposed and appropriate logging in place as required',
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
            throw new CannotApproveOwnPrException('You cannot approve your own PR');
        }

        // Randomly thank author of the pull request
        if (\random_int(0, 3) === 1) {
            $approvalMessage = \sprintf("Thanks @%s. %s", $pullRequestAuthor, $approvalMessage);
        }

        $lines = [
            $approvalMessage,
            \PHP_EOL,
            'Secure code in this PR has been written to best practice standards and covers the following as a minimum. Please ticket if coded this way (also tick if not relevant to this code change):',
        ];

        foreach (self::CODE_REVIEW_CHECKLIST as $check) {
            $lines[] = \sprintf('- [X] %s', $check);
        }

        $lines[] = \PHP_EOL;
        $lines[] = 'See procedure for more details: [PROC-010 Secure Coding Practices](https://eonx.atlassian.net/wiki/spaces/IMS/pages/689341460/PROC-010+Secure+Coding+Practices)';

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
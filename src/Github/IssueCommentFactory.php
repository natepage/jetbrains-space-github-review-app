<?php
declare(strict_types=1);

namespace App\Github;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class IssueCommentFactory
{
    private const CREATE_COMMENT_URL = 'https://api.github.com/repos/eonx-com/%s/issues/%s/comments';

    public function __construct(private readonly HttpClientInterface $githubClient)
    {
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function createComment(array $data, GithubAccessToken $accessToken): void
    {
        $url = \sprintf(self::CREATE_COMMENT_URL, $data['github']['repository'], $data['github']['id']);
        $body = \sprintf('Code Review in Space: [%s](%s)', $data['space']['url'], $data['space']['url']);

        // Call getHeaders() to trigger exception if request fails
        $this->githubClient->request('POST', $url, [
            'auth_bearer' => $accessToken->getAccessToken(),
            'json' => [
                'body' => $body,
            ],
        ])->getHeaders();
    }
}
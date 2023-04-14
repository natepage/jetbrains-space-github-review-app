<?php
declare(strict_types=1);

namespace App\Github;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PullRequestReviewFactory
{
    private const URL = 'https://api.github.com/repos/eonx-com/%s/pulls/%s/reviews';

    public function __construct(private readonly HttpClientInterface $githubClient)
    {
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function createReview(array $data): void
    {
        $url = \sprintf(self::URL, $data['github']['repository'], $data['github']['id']);

        $this->githubClient->request('POST', $url, [
            'json' => [
                'event' => 'APPROVE',
                'body' => \sprintf(
                    "@%s approved 👌 | [Space - %s](%s).",
                    $data['github']['username'],
                    $data['space']['number'],
                    $data['space']['url'],
                ),
            ],
        ]);
    }
}
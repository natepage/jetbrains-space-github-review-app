<?php
declare(strict_types=1);

namespace App\Github;

final class GithubAccessToken
{
    public function __construct(
        private readonly string $accessToken,
        private readonly bool $default
    ) {
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function isDefault(): bool
    {
        return $this->default;
    }
}
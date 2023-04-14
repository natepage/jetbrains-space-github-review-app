<?php
declare(strict_types=1);

namespace App\Command;

use App\Github\AccessTokenProvider;
use App\Github\PullRequestReviewFactory;
use App\Github\UsernameFinder;
use App\Space\CodeReviewDetailsFinder;
use App\Space\UserEmailAddressFinder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:debug')]
final class DebugCommand extends Command
{
    public function __construct(
        private readonly AccessTokenProvider $githubAccessTokenProvider,
        private readonly UsernameFinder $usernameFinder,
        private readonly UserEmailAddressFinder $emailAddressFinder,
        private readonly CodeReviewDetailsFinder $codeReviewDetailsFinder,
        private readonly PullRequestReviewFactory $pullRequestReviewFactory
    ) {
        parent::__construct();
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        \dump($this->githubAccessTokenProvider->getToken());

        $email = $this->emailAddressFinder->findById('5PDr40e1vFN');
        $codeReview = $this->codeReviewDetailsFinder->findById('1fz7dQ1ymRnl');

        $codeReview['github']['username'] = $this->usernameFinder->findByEmail($email);

        $this->pullRequestReviewFactory->createReview($codeReview);

        return self::SUCCESS;
    }
}
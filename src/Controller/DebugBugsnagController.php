<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/debug/bugsnag', name: 'debug_bugsnag', methods: ['POST'])]
final class DebugBugsnagController extends AbstractController
{
    public function __invoke(Request $request): Response
    {
        return $this->handleApiResponse(static function () use ($request): void {
            if ($request->headers->has('x-debug-bugsnag')) {
                throw new \RuntimeException('Debugging Bugsnag');
            }
        });
    }
}
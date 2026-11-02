<?php
declare(strict_types=1);

namespace TestPlug\Controller;

use Cake\Controller\Controller;

/**
 * Controller of the test-only plugin used to exercise plugin controller scanning.
 */
class WidgetsController extends Controller
{
    public function index(): void
    {
    }

    public function archive(): void
    {
    }

    /**
     * Public magic method: the scanner must not offer it as an action.
     *
     * @return string
     */
    public function __toString(): string
    {
        return 'widgets';
    }

    protected function helper(): void
    {
    }
}

<?php

declare(strict_types=1);

namespace App\Controller;
use App\Plugin\Log\Log;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Coroutine;

class IndexController extends AbstractController
{

    public function index()
    {
        $user   = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        Log::get()->info('log');
        $logger = ApplicationContext::getContainer()->get(\Hyperf\Logger\LoggerFactory::class)->get('app','app');

        return [
            'method'  => $method,
            'message' => "Hello {$user}.",
            'coId'    => Coroutine::id(),
            'logger'  => $logger,
        ];
    }
}

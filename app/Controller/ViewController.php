<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\View\RenderInterface;

/**
 * @AutoController
 */
class ViewController
{
    public function index(RenderInterface $render)
    {
        $name = "rand:" . rand(0, 100);
        return $render->render('index', ['name' => $name]);
    }
}
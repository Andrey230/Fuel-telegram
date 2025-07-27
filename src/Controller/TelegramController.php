<?php

namespace App\Controller;

use App\service\telegram\TelegramBotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TelegramController extends AbstractController
{
    #[Route('/telegram', name: 'app_telegram')]
    public function index(Request $request, TelegramBotService $botService): Response
    {
        $update = $request->getContent();

        $botService->handleUpdate($update);

        return new Response('OK');
    }
}

<?php

namespace App\service\telegram;

use App\service\recordCreator\RecordCreator;
use App\service\recordFinder\RecordFinder;
use App\service\userStateService\UserStateService;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Telegram\Bot\Api;

class TelegramBotService
{
    private Api $telegram;
    private ?string $telegramId;
    private ?string $chatId;
    public function __construct(
        string $telegramToken,
        private readonly UserStateService $userStateService,
        private readonly RecordCreator $recordCreator,
        private readonly RecordFinder $recordFinder,
    )
    {
        $this->telegram = new Api($telegramToken);
    }

    public function handleUpdate(string $json): void
    {
        $update = $this->telegram->getWebhookUpdate($json);

        $message = $update->getMessage();
        $callback = $update->getCallbackQuery();

        if ($callback) {
            $this->handleCallback($callback);
            return;
        }

        if ($message) {
            $this->handleMessage($message);
        }
    }

    private function handleMessage($message): void
    {
        $telegramId = (string)$message->getFrom()->getId();
        $chatId = $message->getChat()->getId();

        $this->telegramId = $telegramId;
        $this->chatId = $chatId;

        $text = trim($message->getText());

        $state = $this->userStateService->getState($telegramId);
        $currentState = $state?->getState();

        if ($text === '/start') {
            $this->telegram->sendMessage($this->getMessageByState(UserStateService::START_STATE));
            return;
        }

        if ($text === '/set_fuel') {
            $this->userStateService->setState($telegramId, UserStateService::WAITING_TRUCK_NUMBER);
            $this->telegram->sendMessage($this->getMessageByState(UserStateService::WAITING_TRUCK_NUMBER));
            return;
        }

        switch ($currentState) {
            case UserStateService::WAITING_TRUCK_NUMBER:
                $this->userStateService->setState($telegramId, UserStateService::WAITING_TYPE, [
                    'truckNumber' => strtoupper($text),
                ]);

                $this->telegram->sendMessage($this->getMessageByState(UserStateService::WAITING_TYPE));
                break;

            case UserStateService::WAITING_AMOUNT:
                $amount = str_replace(' ', '', $text);

                if(!ctype_digit($amount)) {
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Васичка, введи нормально количество литров (только цифры): ',
                    ]);
                    return;
                }

                $this->userStateService->setState($telegramId, UserStateService::WAITING_MILEAGE, [
                    'amount' => $amount,
                ]);
                $this->telegram->sendMessage($this->getMessageByState(UserStateService::WAITING_MILEAGE));
                break;

            case UserStateService::WAITING_MILEAGE:
                $newMileage = str_replace(' ', '', $text);

                if(!ctype_digit($newMileage)) {
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Васичка, введи нормально пробег (только цифры): ',
                    ]);
                    return;
                }
                $truckMileage = $this->recordFinder->findTruckMileage($state->getData()['truckNumber']);

                if($newMileage <= $truckMileage) {
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => 'Ты кого кинуть решил, голова ??? Вихоту тоже кушать нужно: '.$truckMileage.$state->getData()['truckNumber'],
                    ]);
                    return;
                }

                $this->userStateService->setState($telegramId, UserStateService::END_STATE, [
                    'mileage' => $newMileage,
                ]);

                $fullData = $this->userStateService->getState($telegramId)?->getData();

                $this->recordCreator->create(
                    $telegramId,
                    $fullData['amount'] ?? null,
                    $fullData['type'] ?? null,
                    $fullData['truckNumber'] ?? null,
                    $fullData['mileage'] ?? null
                );

                $this->userStateService->refreshState($state);

                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => 'Спасибо! Заправка добавлена ✅',
                ]);
                break;
            case UserStateService::WAITING_TYPE:
                $this->telegram->sendMessage($this->getMessageByState(UserStateService::WAITING_TYPE));
                break;
            default:
                $this->telegram->sendMessage($this->getMessageByState(UserStateService::START_STATE));
                break;
        }
    }

    private function handleCallback($callback): void
    {
        $telegramId = (string)$callback->getFrom()->getId();
        $chatId = $callback->getMessage()->getChat()->getId();

        $this->telegramId = $telegramId;
        $this->chatId = $chatId;

        $data = $callback->getData();

        $state = $this->userStateService->getState($telegramId);
        $currentState = $state?->getState();

        if ($currentState === UserStateService::WAITING_TYPE) {
            $this->userStateService->setState($telegramId, UserStateService::WAITING_AMOUNT, [
                'type' => $data,
            ]);

            $this->telegram->sendMessage($this->getMessageByState(UserStateService::WAITING_AMOUNT));
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callback->getId(),
            ]);
        }elseif ($currentState === UserStateService::WAITING_TRUCK_NUMBER){
            $this->userStateService->setState($telegramId, UserStateService::WAITING_TYPE, [
                'truckNumber' => $data,
            ]);

            $this->telegram->sendMessage($this->getMessageByState(UserStateService::WAITING_TYPE));

            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callback->getId(),
            ]);
        }
    }

    private function getMessageByState(
        string $state
    ): array
    {
        switch ($state) {
            case UserStateService::START_STATE:
                return [
                    'chat_id' => $this->chatId,
                    'text' => 'Напиши /set_fuel, чтобы добавить новую заправку.',
                ];
            case UserStateService::WAITING_TYPE:

                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Diesel', 'callback_data' => 'diesel'],
                            ['text' => 'AdBlue', 'callback_data' => 'AdBlue'],
                        ],
                    ],
                ];

                return [
                    'chat_id' => $this->chatId,
                    'text' => 'Выберите тип топлива:',
                    'reply_markup' => json_encode($keyboard),
                ];
            case UserStateService::WAITING_AMOUNT:
                return [
                    'chat_id' => $this->chatId,
                    'text' => 'Введите количество литров:',
                ];
            case UserStateService::WAITING_MILEAGE:
                return [
                    'chat_id' => $this->chatId,
                    'text' => 'Введите пробег машины: ',
                ];
            default:
                $truckNumbers = $this->recordFinder->findTruckNumbersByUser($this->telegramId);

                $keyboard = ['inline_keyboard' => []];

                foreach ($truckNumbers as $truckNumber) {
                    $keyboard['inline_keyboard'][0][] = [
                        'text' => $truckNumber,
                        'callback_data' => $truckNumber,
                    ];
                }

                return [
                    'chat_id' => $this->chatId,
                    'text' => 'Введите новый номер машины или выбери ранее запралвеные:',
                    'reply_markup' => json_encode($keyboard),
                ];
        }
    }

}

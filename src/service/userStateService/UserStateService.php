<?php

namespace App\service\userStateService;

use App\Entity\UserState;
use App\Repository\UserStateRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserStateService
{
    const string START_STATE = 'start';
    const string WAITING_AMOUNT = 'waitingAmount';
    const string WAITING_TYPE = 'waitingType';
    const string WAITING_TRUCK_NUMBER = 'waitingTruckNumber';
    const string WAITING_MILEAGE = 'waitingMileage';
    const string END_STATE = 'end';


    public function __construct(
        private readonly UserStateRepository $stateRepository,
        private readonly EntityManagerInterface $entityManager,
    )
    {

    }

    public function setState(string $telegramId, string $newState, array $additionalData = []): UserState
    {
        $state = $this->getState($telegramId);

        if(!$state){
            $state = new UserState();
            $state->setTelegramId($telegramId);
        }

        $state->setData(array_merge($additionalData, $state->getData()));
        $state->setState($newState);
        $state->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($state);
        $this->entityManager->flush();

        return $state;
    }

    public function refreshState(UserState $state): void
    {
        $this->entityManager->remove($state);
        $this->entityManager->flush();
    }

    public function getState(string $telegramId): ?UserState
    {
        return $this->stateRepository->findOneBy([
            'telegram_id' => $telegramId,
        ]);
    }
}

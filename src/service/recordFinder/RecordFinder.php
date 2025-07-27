<?php

namespace App\service\recordFinder;

use App\Entity\Record;
use App\Repository\RecordRepository;

class RecordFinder
{
    public function __construct(
        private RecordRepository $recordRepository,
    )
    {
    }

    public function findAll(): array
    {

        return array_map(function (Record $record) {
            return $record->toArray();
        }, $this->recordRepository->findAll());
    }

    public function findByUser(
        string $telegramUser
    ): array
    {

        return array_map(function (Record $record) {
            return $record->toArray();
        }, $this->recordRepository->findBy([
            'telegram_user' => $telegramUser,
        ], [
            'date' => 'DESC'
        ]));
    }

    public function findLastRecordByUser(
        string $telegramUser,
        string $truckNumber,
    ): ?Record
    {
        return $this->recordRepository->findOneBy([
            'telegram_user' => $telegramUser,
            'truckNumber' => $truckNumber,
        ], [
            'date' => 'DESC',
        ]);
    }

    public function findTruckNumbersByUser(
        string $telegramUser,
    ): array
    {
        return $this->recordRepository->findTruckNumbersByUser($telegramUser);
    }

    public function findTruckMileage(
        string $truckNumber,
    ): int
    {
        $record = $this->recordRepository->findOneBy([
            'truckNumber' => $truckNumber,
        ], [
            'date' => 'DESC',
        ]);

        if(!$record){
            return 0;
        }

        return $record->getMileage();
    }
}

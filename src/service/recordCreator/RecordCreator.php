<?php

namespace App\service\recordCreator;

use App\Entity\Record;
use App\service\recordFinder\RecordFinder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RecordCreator
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RecordFinder $recordFinder,
    )
    {
    }

    public function create(
        string $telegramUser,
        int $amount,
        string $type,
        string $truckNumber,
        int $mileage,
    ): Record
    {
        $lastRecord = $this->recordFinder->findLastRecordByUser($telegramUser, $truckNumber);

        if($lastRecord && $lastRecord->getTruckNumber() === $truckNumber){
            if($mileage <= $lastRecord->getMileage()){
                throw new NotFoundHttpException('invalid mileage');
            }
        }

        $record = new Record();
        $record->setTelegramUser($telegramUser);
        $record->setAmount($amount);
        $record->setType($type);
        $record->setTruckNumber($truckNumber);
        $record->setMileage($mileage);

        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return $record;
    }
}

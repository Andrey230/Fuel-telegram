<?php

namespace App\Controller;

use App\service\recordCreator\RecordCreator;
use App\service\recordFinder\RecordFinder;
use PHPUnit\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Webmozart\Assert\Assert;

#[Route('/api')]
final class FuelController extends AbstractController
{
    #[Route('/fuels', name: 'app_fuel_list', methods: ['GET'])]
    public function list(RecordFinder $recordFinder): Response
    {
        return $this->json($recordFinder->findAll());
    }

    #[Route('/fuel', name: 'app_fuel_create', methods: ['POST'])]
    public function create(RecordCreator $creator, Request $request): Response
    {
        $data = $request->toArray();

        try {
            Assert::string($data['telegram_user']);
            Assert::integer($data['amount']);
            Assert::string($data['type']);
            Assert::string($data['truckNumber']);
            Assert::integer($data['mileage']);

            $record = $creator->create(
                $data['telegram_user'],
                $data['amount'],
                $data['type'],
                $data['truckNumber'],
                $data['mileage']
            );

            return $this->json($record->toArray());
        }catch (\Exception $exception){
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/fuel/{telegram}', name: 'app_fuel_by_user', methods: ['GET'])]
    public function findByUser(RecordFinder $finder, string $telegram): Response
    {
        return $this->json($finder->findByUser($telegram));
    }

    #[Route('/fuel/{telegram}/truck-numbers', name: 'app_fuel_truck_numbers_by_user', methods: ['GET'])]
    public function findTruckNumbersByUser(RecordFinder $finder, string $telegram): Response
    {
        return $this->json([
            'truckNumbers' => $finder->findTruckNumbersByUser($telegram),
        ]);
    }
}

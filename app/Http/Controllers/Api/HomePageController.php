<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Persons;
use App\Models\Coins;
use App\Models\Doctor;
use App\Models\SpecialitiesMaster;
use Illuminate\Support\Facades\Log;

class HomePageController extends Controller
{
    
    public function userCoins(Request $request){

        $user = $request->user();
        if(!$user){
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $person = Persons::where('hip_user_id', $user->id)->first();

        if(!$person){
            return response()->json([
                'status' => 404,
                'message' => 'Person not found',
            ], 404);
        }

        $coins = Coins::where('person_id', $person->parent_id ?? $person->id)->first();

        return response()->json([
            'status' => 200,
            'message' => 'Coins fetched successfully',
            'data' => [
                'coins' => $coins->coins,
            ],
        ], 200);

    }

    public function doctorSpecialities()
    {
        try {
            $countsByMasterId = [];

            Doctor::query()
                ->where('status', 'active')
                ->select(['id', 'speciality', 'assigned_speciality'])
                ->chunkById(200, function ($doctors) use (&$countsByMasterId) {
                    foreach ($doctors as $doctor) {
                        $masterIds = array_unique(array_merge(
                            array_map('intval', (array) ($doctor->speciality ?? [])),
                            array_map('intval', (array) ($doctor->assigned_speciality ?? []))
                        ));

                        foreach ($masterIds as $masterId) {
                            if ($masterId > 0) {
                                $countsByMasterId[$masterId] = ($countsByMasterId[$masterId] ?? 0) + 1;
                            }
                        }
                    }
                });

            if ($countsByMasterId === []) {
                return response()->json([
                    'status'  => 200,
                    'message' => 'No doctor specialities found',
                    'data'    => [],
                    'count'   => 0,
                ], 200);
            }

            $masters = SpecialitiesMaster::query()
                ->where('status', 'active')
                ->whereIn('id', array_keys($countsByMasterId))
                ->orderBy('name')
                ->get();

            $data = $masters->map(function (SpecialitiesMaster $master) use ($countsByMasterId) {
                $count = $countsByMasterId[$master->id] ?? 0;

                return [
                    'id'                 => $master->id,
                    'speciality_name'    => $master->name,
                    'description'        => $master->description,
                    'icon'               => $master->display_image
                        ? url('storage/speciality/' . basename($master->display_image))
                        : null,
                    'specialists_count'  => $count,
                ];
            })->filter(fn (array $row) => $row['specialists_count'] > 0)->values();

            return response()->json([
                'status'  => 200,
                'message' => 'Doctor specialities fetched successfully',
                'data'    => $data,
                'count'   => $data->count(),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error fetching doctor specialities', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => 500,
                'message' => 'Error fetching doctor specialities',
                'data'    => [],
                'count'   => 0,
            ], 500);
        }
    }

}

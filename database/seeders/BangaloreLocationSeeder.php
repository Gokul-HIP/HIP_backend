<?php

// database/seeders/BangaloreLocationSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LocationMaster;

class BangaloreLocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [

            [
                'state'     => 'Karnataka',
                'city'      => 'Bangalore',
                'area'      => 'Indiranagar',
                'zipcode'   => '560038',
                'area_code' => 'BLR-INR',
                'latitude'  => 12.9718915,
                'longitude' => 77.6411545,
            ],
            [
                'state'     => 'Karnataka',
                'city'      => 'Bangalore',
                'area'      => 'Whitefield',
                'zipcode'   => '560066',
                'area_code' => 'BLR-WHT',
                'latitude'  => 12.9698196,
                'longitude' => 77.7499721,
            ],
            [
                'state'     => 'Karnataka',
                'city'      => 'Bangalore',
                'area'      => 'BTM Layout',
                'zipcode'   => '560076',
                'area_code' => 'BLR-BTM',
                'latitude'  => 12.9165757,
                'longitude' => 77.6101163,
            ],
            [
                'state'     => 'Karnataka',
                'city'      => 'Bangalore',
                'area'      => 'Jayanagar',
                'zipcode'   => '560041',
                'area_code' => 'BLR-JYN',
                'latitude'  => 12.925007,
                'longitude' => 77.593803,
            ],
            [
                'state'     => 'Karnataka',
                'city'      => 'Bangalore',
                'area'      => 'Yelahanka',
                'zipcode'   => '560064',
                'area_code' => 'BLR-YLK',
                'latitude'  => 13.100698,
                'longitude' => 77.596345,
            ],
            [
                'state'     => 'Karnataka',
                'city'      => 'Bangalore',
                'area'      => 'Electronic City',
                'zipcode'   => '560100',
                'area_code' => 'BLR-ELC',
                'latitude'  => 12.8452145,
                'longitude' => 77.6601695,
            ],
            [
                'state'     => 'Karnataka',
                'city'      => 'Bangalore',
                'area'      => 'Malleshwaram',
                'zipcode'   => '560003',
                'area_code' => 'BLR-MLM',
                'latitude'  => 12.998294,
                'longitude' => 77.569941,
            ],
            [
                'state'     => 'Karnataka',
                'city'      => 'Bangalore',
                'area'      => 'Rajajinagar',
                'zipcode'   => '560010',
                'area_code' => 'BLR-RJN',
                'latitude'  => 12.991356,
                'longitude' => 77.554883,
            ],
            [
                'state'     => 'Karnataka',
                'city'      => 'Bangalore',
                'area'      => 'Hebbal',
                'zipcode'   => '560024',
                'area_code' => 'BLR-HBL',
                'latitude'  => 13.0358,
                'longitude' => 77.5970,
            ],
            [
                'state'     => 'Karnataka',
                'city'      => 'Bangalore',
                'area'      => 'KR Puram',
                'zipcode'   => '560036',
                'area_code' => 'BLR-KRP',
                'latitude'  => 12.9951,
                'longitude' => 77.6950,
            ],

            [
                'state'     => 'Karnataka',
                'city'      => 'Bangalore',
                'area'      => 'JP Nagar Phase 1',
                'zipcode'   => '560078',
                'area_code' => 'BLR-JP1',
                'latitude'  => 12.9078,
                'longitude' => 77.5855,
            ],
            [
                'state'     => 'Karnataka',
                'city'      => 'Bangalore',
                'area'      => 'JP Nagar Phase 2',
                'zipcode'   => '560078',
                'area_code' => 'BLR-JP2',
                'latitude'  => 12.9070,
                'longitude' => 77.5905,
            ],
            [
                'state'     => 'Karnataka',
                'city'      => 'Bangalore',
                'area'      => 'JP Nagar Phase 3',
                'zipcode'   => '560078',
                'area_code' => 'BLR-JP3',
                'latitude'  => 12.9055,
                'longitude' => 77.5958,
            ],
            [
                'state'     => 'Karnataka',
                'city'      => 'Bangalore',
                'area'      => 'JP Nagar Phase 4',
                'zipcode'   => '560078',
                'area_code' => 'BLR-JP4',
                'latitude'  => 12.9032,
                'longitude' => 77.5989,
            ],
            [
                'state'     => 'Karnataka',
                'city'      => 'Bangalore',
                'area'      => 'JP Nagar Phase 5',
                'zipcode'   => '560078',
                'area_code' => 'BLR-JP5',
                'latitude'  => 12.9005,
                'longitude' => 77.6008,
            ],
            [
                'state'     => 'Karnataka',
                'city'      => 'Bangalore',
                'area'      => 'JP Nagar Phase 6',
                'zipcode'   => '560078',
                'area_code' => 'BLR-JP6',
                'latitude'  => 12.8980,
                'longitude' => 77.6035,
            ],

        ];

        LocationMaster::insert($locations);
    }
}

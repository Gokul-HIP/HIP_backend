<?php

namespace Database\Seeders;

use App\Models\MedicineMaster;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MedicineMasterSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        $categories = [
            'Antibiotics', 'Pain Relief', 'Cardiovascular', 'Diabetes',
            'Respiratory', 'Gastrointestinal', 'Vitamins & Supplements',
            'Antihistamines', 'Antacids', 'Antifungal', 'Antiviral',
            'Dermatological', 'Eye Care', 'Ear Care', 'Hormonal',
        ];

        $brandNames = [
            'Cipla', 'Sun Pharma', 'Dr. Reddy\'s', 'Lupin', 'Zydus',
            'Torrent', 'Glenmark', 'Cadila', 'Mankind', 'Alkem',
            'Pfizer', 'GSK', 'Novartis', 'Abbott', 'Sanofi',
        ];

        $dosageForms = [
            'Tablet', 'Capsule', 'Syrup', 'Injection', 'Cream',
            'Ointment', 'Drops', 'Spray', 'Inhaler', 'Gel',
        ];

        $medicineNames = [
            'Paracetamol', 'Ibuprofen', 'Amoxicillin', 'Azithromycin',
            'Cetirizine', 'Omeprazole', 'Atorvastatin', 'Metformin',
            'Amlodipine', 'Losartan', 'Levothyroxine', 'Montelukast',
            'Pantoprazole', 'Diclofenac', 'Aspirin', 'Clopidogrel',
            'Metoprolol', 'Furosemide', 'Ramipril', 'Gliclazide',
            'Insulin', 'Salbutamol', 'Budesonide',
            'Calcium Carbonate', 'Vitamin D3', 'Iron Supplement', 'Folic Acid',
        ];

        $imageDirectory = 'pharmacy/products';
        $localImages = $this->pharmacyProductImageFilenames();

        if ($localImages !== []) {
            $this->command?->info('MedicineMasterSeeder: using random files from storage/app/public/pharmacy/products');
        } else {
            $this->command?->warn('MedicineMasterSeeder: no images in pharmacy/products — generating placeholder PNGs (add real images to that folder to use them instead).');
        }

        for ($i = 1; $i <= 50; $i++) {
            $medicineName = $faker->randomElement($medicineNames);
            $strength = $faker->randomElement(['250mg', '500mg', '100mg', '50mg', '10mg', '5mg']);
            $packSize = $faker->randomElement([
                '10 Tablets', '15 Tablets', '20 Tablets', '30 Tablets',
                '60 Tablets', '100ml', '200ml', '500ml',
            ]);

            $mrp = $faker->randomFloat(2, 50, 2000);
            $discount = $faker->randomFloat(2, 0, 30);
            $sellingPrice = round($mrp - ($mrp * $discount / 100), 2);

            $imageName = $localImages !== []
                ? $localImages[array_rand($localImages)]
                : $this->generateDummyImage($medicineName, $imageDirectory);

            MedicineMaster::create([
                'name' => $medicineName . ' ' . $strength,
                'code' => 'MED-' . strtoupper(Str::random(8)),
                'category' => $faker->randomElement($categories),
                'brand_name' => $faker->randomElement($brandNames),
                'dosage_form' => $faker->randomElement($dosageForms),
                'strength' => $strength,
                'pack_size' => $packSize,
                'mrp' => $mrp,
                'selling_price' => $sellingPrice,
                'discount' => $discount,
                'stock_quantity' => $faker->numberBetween(0, 500),
                'expiry_date' => $faker->dateTimeBetween('+6 months', '+3 years')->format('Y-m-d'),
                'batch_number' => 'BATCH-' . strtoupper(Str::random(8)),
                'prescription_required' => $faker->boolean(40),
                'image' => $imageName,
                'description' => $faker->sentence(15),
                'status' => $faker->randomElement(['active', 'active', 'active', 'inactive']),
            ]);
        }

        $this->command->info('Medicine Master seeded successfully (50 records)');
    }

    /**
     * Basenames under the public disk folder pharmacy/products (same as Filament uploads).
     *
     * @return list<string>
     */
    private function pharmacyProductImageFilenames(): array
    {
        $dir = storage_path('app/public/pharmacy/products');

        if (! is_dir($dir)) {
            return [];
        }

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $names = [];

        foreach (File::files($dir) as $file) {
            if ($file->isFile() && in_array(strtolower($file->getExtension()), $allowed, true)) {
                $names[] = $file->getFilename();
            }
        }

        return $names;
    }

    /**
     * Fallback when pharmacy/products has no image files (requires GD extension).
     */
    private function generateDummyImage(string $medicineName, string $directory): string
    {
        $fullDir = storage_path('app/public/' . $directory);
        if (! is_dir($fullDir)) {
            File::makeDirectory($fullDir, 0755, true);
        }

        $image = imagecreatetruecolor(400, 400);
        $bg = imagecolorallocate($image, 240, 240, 245);
        $text = imagecolorallocate($image, 90, 90, 120);

        imagefill($image, 0, 0, $bg);

        imagestring($image, 5, 40, 190, strtoupper(substr($medicineName, 0, 18)), $text);
        imagestring($image, 3, 150, 340, 'MEDICINE', $text);

        $imageName = Str::uuid() . '.png';
        imagepng($image, $fullDir . DIRECTORY_SEPARATOR . $imageName);
        imagedestroy($image);

        return $imageName;
    }
}

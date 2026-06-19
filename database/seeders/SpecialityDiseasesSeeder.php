<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\SpecialitiesMaster;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpecialityDiseasesSeeder extends Seeder
{
    /**
     * Seed diseases JSON on specialities_masters (by speciality code).
     * Every disease entry includes name, about, symptoms, and recommended_tests.
     * Legacy duplicate rows (code = null) are remapped and removed after seeding.
     *
     * Run: php artisan db:seed --class=SpecialityDiseasesSeeder
     */
    public function run(): void
    {
        $withDiseases = [
            'CARD' => [
                'department_name' => 'Heart & Vascular Care',
                'diseases'        => [
                    $this->entry('Heart Disease', 'Conditions affecting the heart and blood vessels.', ['Chest pain', 'Shortness of breath', 'Fatigue', 'Irregular heartbeat', 'Swelling in legs'], ['ECG', 'Echocardiogram', 'Lipid Profile', 'Troponin Test', 'Stress Test']),
                    $this->entry('Hypertension', 'Persistently elevated blood pressure in the arteries.', ['Headaches', 'Shortness of breath', 'Nosebleeds', 'Chest pain', 'Dizziness'], ['Blood Pressure Monitoring', 'ECG', 'Lipid Profile', 'Kidney Function Test']),
                    $this->entry('Coronary Artery Disease', 'Narrowed coronary arteries reducing blood flow to the heart.', ['Angina', 'Chest pressure', 'Breathlessness on exertion', 'Fatigue'], ['ECG', 'Treadmill Test', 'Coronary Angiography', 'Lipid Profile']),
                ],
            ],
            'ORTH' => [
                'department_name' => 'Bone & Joint Care',
                'diseases'        => [
                    $this->entry('Arthritis', 'Inflammation of joints causing pain and stiffness.', ['Joint pain', 'Joint stiffness', 'Swelling', 'Reduced range of motion'], ['X-Ray Joints', 'Rheumatoid Factor', 'ESR', 'CRP', 'Anti-CCP Antibody']),
                    $this->entry('Fracture', 'A break in bone continuity, usually from trauma or overuse.', ['Severe pain', 'Swelling', 'Bruising', 'Deformity', 'Limited movement'], ['X-Ray', 'CT Scan', 'MRI', 'Bone Density Test']),
                    $this->entry('Back Pain', 'Pain along the spine, commonly in the lower back.', ['Muscle ache', 'Stabbing pain', 'Limited flexibility', 'Pain radiating to leg'], ['X-Ray Spine', 'MRI Spine', 'ESR', 'CRP']),
                    $this->entry('Slip Disc', 'Soft inner portion of a spinal disc pushes through the outer ring.', ['Arm or leg pain', 'Numbness or tingling', 'Weakness', 'Back pain'], ['MRI Spine', 'X-Ray Spine', 'Nerve Conduction Study', 'EMG']),
                    $this->entry('Osteoporosis', 'Condition that weakens bones, making them fragile and more likely to break.', ['Back pain', 'Loss of height', 'Stooped posture', 'Bone fractures'], ['DEXA Scan', 'Calcium Level', 'Vitamin D Level', 'X-Ray']),
                ],
            ],
            'NEUR' => [
                'department_name' => 'Brain & Nerve Care',
                'diseases'        => [
                    $this->entry('Migraine', 'Recurrent moderate to severe headaches, often with other symptoms.', ['Throbbing headache', 'Nausea', 'Sensitivity to light', 'Visual disturbances'], ['MRI Brain', 'CT Brain', 'Neurological Examination']),
                    $this->entry('Epilepsy', 'Neurological disorder marked by recurrent unprovoked seizures.', ['Seizures', 'Temporary confusion', 'Staring spells', 'Uncontrolled jerking'], ['EEG', 'MRI Brain', 'Video EEG Monitoring']),
                    $this->entry('Stroke', 'Interrupted blood supply to part of the brain.', ['Sudden numbness', 'Confusion', 'Trouble speaking', 'Severe headache', 'Vision problems'], ['CT Brain', 'MRI Brain', 'Carotid Doppler', 'ECG']),
                    $this->entry('Parkinson Disease', 'Progressive nervous system disorder affecting movement.', ['Tremor', 'Slowed movement', 'Muscle stiffness', 'Impaired balance'], ['Neurological Examination', 'DaTscan', 'MRI Brain']),
                    $this->entry('Alzheimer Disease', 'Progressive brain disorder affecting memory, thinking, and behavior.', ['Memory loss', 'Confusion', 'Difficulty planning', 'Personality changes', 'Wandering'], ['Cognitive Assessment', 'MRI Brain', 'Blood Tests', 'Neurological Examination']),
                ],
            ],
            'PEDI' => [
                'department_name' => 'Child Health',
                'diseases'        => [
                    $this->entry('Chickenpox', 'Contagious viral infection common in children.', ['Itchy rash with blisters', 'Fever', 'Headache', 'Fatigue', 'Loss of appetite'], ['Clinical Examination', 'Varicella IgM', 'Complete Blood Count']),
                    $this->entry('Childhood Asthma', 'Chronic airway inflammation causing wheezing in children.', ['Wheezing', 'Coughing', 'Chest tightness', 'Shortness of breath'], ['Spirometry', 'Peak Flow Test', 'Chest X-Ray', 'Allergy Panel']),
                    $this->entry('Measles', 'Highly contagious viral illness with fever and rash.', ['High fever', 'Cough', 'Runny nose', 'Red rash', 'Red eyes'], ['Clinical Examination', 'Measles IgM', 'Complete Blood Count']),
                ],
            ],
            'DERM' => [
                'department_name' => 'Skin Care',
                'diseases'        => [
                    $this->entry('Psoriasis', 'Chronic autoimmune condition causing scaly skin patches.', ['Red patches with silvery scales', 'Itching', 'Dry cracked skin', 'Joint pain'], ['Skin Biopsy', 'ESR', 'CRP']),
                    $this->entry('Eczema', 'Inflamed, itchy skin condition often starting in childhood.', ['Itchy skin', 'Red or brown patches', 'Dry sensitive skin', 'Crusting'], ['Skin Prick Test', 'Patch Test', 'Total IgE']),
                    $this->entry('Skin Allergy', 'Immune reaction causing rash or irritation on skin contact.', ['Itching', 'Red rash', 'Hives', 'Swelling', 'Dry skin'], ['Skin Prick Test', 'Patch Test', 'Specific IgE Panel']),
                    $this->entry('Acne', 'Common skin condition with blocked hair follicles and oil glands.', ['Pimples', 'Blackheads', 'Whiteheads', 'Oily skin', 'Scarring'], ['Clinical Examination', 'Hormone Panel if needed']),
                ],
            ],
            'GAST' => [
                'department_name' => 'Digestive Care',
                'diseases'        => [
                    $this->entry('GERD', 'Stomach acid frequently flows back into the esophagus.', ['Heartburn', 'Regurgitation', 'Chest pain', 'Difficulty swallowing', 'Chronic cough'], ['Upper GI Endoscopy', 'Esophageal pH Monitoring', 'Barium Swallow']),
                    $this->entry('Gastric Ulcer', 'Open sores on the inner lining of the stomach.', ['Burning stomach pain', 'Bloating', 'Nausea', 'Heartburn', 'Loss of appetite'], ['Upper GI Endoscopy', 'H. pylori Test', 'Stool Occult Blood']),
                    $this->entry('Food Poisoning', 'Illness from eating contaminated food or drink.', ['Nausea', 'Vomiting', 'Diarrhea', 'Abdominal cramps', 'Fever'], ['Stool Culture', 'Stool Routine & Microscopy', 'Complete Blood Count']),
                    $this->entry('Liver Disease', 'Conditions affecting the liver including inflammation, fatty liver, and cirrhosis.', ['Fatigue', 'Jaundice', 'Abdominal pain', 'Swelling in legs', 'Dark urine'], ['Liver Function Test', 'Ultrasound Abdomen', 'Hepatitis Panel', 'Prothrombin Time']),
                    $this->entry('Hepatitis', 'Inflammation of the liver, commonly caused by viral infection.', ['Fatigue', 'Jaundice', 'Abdominal pain', 'Dark urine', 'Loss of appetite'], ['Liver Function Test', 'Hepatitis B Surface Antigen', 'Hepatitis C Antibody', 'Ultrasound Abdomen']),
                    $this->entry('Jaundice', 'Yellowing of the skin and eyes due to high bilirubin levels.', ['Yellow skin and eyes', 'Dark urine', 'Pale stools', 'Itching', 'Abdominal pain'], ['Liver Function Test', 'Bilirubin Levels', 'Ultrasound Abdomen', 'Complete Blood Count']),
                ],
            ],
            'PULM' => [
                'department_name' => 'Lung Care',
                'diseases'        => [
                    $this->entry('Asthma', 'Chronic lung condition with inflamed and narrowed airways.', ['Wheezing', 'Shortness of breath', 'Chest tightness', 'Coughing'], ['Spirometry', 'Peak Flow Test', 'Chest X-Ray', 'Allergy Panel']),
                    $this->entry('Pneumonia', 'Infection inflaming air sacs in the lungs.', ['Cough with phlegm', 'Fever and chills', 'Shortness of breath', 'Chest pain'], ['Chest X-Ray', 'Complete Blood Count', 'Sputum Culture', 'CRP']),
                    $this->entry('Bronchitis', 'Inflammation of the bronchial tubes.', ['Persistent cough', 'Mucus production', 'Fatigue', 'Chest discomfort'], ['Chest X-Ray', 'Complete Blood Count', 'Sputum Culture', 'Spirometry']),
                    $this->entry('Tuberculosis', 'Bacterial infection mainly affecting the lungs.', ['Persistent cough', 'Blood in sputum', 'Chest pain', 'Night sweats', 'Weight loss'], ['Sputum AFB Test', 'Chest X-Ray', 'GeneXpert MTB/RIF']),
                    $this->entry('COVID-19', 'Respiratory illness caused by the SARS-CoV-2 virus.', ['Fever', 'Cough', 'Shortness of breath', 'Fatigue', 'Loss of taste or smell'], ['RT-PCR Test', 'Rapid Antigen Test', 'Chest X-Ray', 'Complete Blood Count', 'CRP']),
                ],
            ],
            'ENDO' => [
                'department_name' => 'Hormone & Metabolic Care',
                'diseases'        => [
                    $this->entry('Diabetes', 'A chronic condition that affects how your body processes blood sugar (glucose).', ['Frequent urination', 'Excess thirst', 'Fatigue', 'Blurred vision', 'Slow-healing sores'], ['Fasting Blood Sugar', 'HbA1c', 'Oral Glucose Tolerance Test', 'Random Blood Sugar']),
                    $this->entry('Thyroid Disorder', 'Conditions affecting thyroid hormone production.', ['Weight changes', 'Fatigue', 'Hair loss', 'Mood changes', 'Heat or cold sensitivity'], ['TSH', 'Free T3', 'Free T4', 'Thyroid Antibodies', 'Thyroid Ultrasound']),
                    $this->entry('Obesity', 'Excess body fat increasing risk of other health problems.', ['Excess body weight', 'Breathlessness', 'Snoring', 'Joint pain', 'Fatigue'], ['BMI Assessment', 'Lipid Profile', 'Fasting Blood Sugar', 'HbA1c', 'Thyroid Function Test']),
                    $this->entry('Cholesterol', 'A condition marked by high levels of cholesterol in the blood, which can lead to fatty deposits in arteries.', ['Usually no symptoms', 'Chest pain on exertion', 'Fatigue', 'Leg pain when walking'], ['Lipid Profile', 'LDL Cholesterol', 'HDL Cholesterol', 'Triglycerides', 'ECG']),
                ],
            ],
            'ENT' => [
                'department_name' => 'Ear, Nose & Throat Care',
                'diseases'        => [
                    $this->entry('Sinusitis', 'Inflammation of the sinuses, often after infection or allergies.', ['Facial pain', 'Nasal congestion', 'Thick discharge', 'Reduced smell', 'Headache'], ['X-Ray PNS', 'CT PNS', 'Nasal Endoscopy']),
                    $this->entry('Ear Infection', 'Infection of the middle ear, common in children.', ['Ear pain', 'Fluid drainage', 'Hearing difficulty', 'Fever', 'Irritability'], ['Otoscopy', 'Tympanometry', 'Audiometry', 'Ear Swab Culture']),
                    $this->entry('Tonsillitis', 'Inflammation of the tonsils, usually from viral or bacterial infection.', ['Sore throat', 'Difficulty swallowing', 'Fever', 'Swollen tonsils', 'Bad breath'], ['Clinical Examination', 'Throat Swab Culture', 'Complete Blood Count']),
                ],
            ],
            'NEPH' => [
                'department_name' => 'Kidney Care',
                'diseases'        => [
                    $this->entry('Kidney Stone', 'Hard mineral deposits forming in the kidneys.', ['Severe back or side pain', 'Pain during urination', 'Blood in urine', 'Nausea'], ['Urinalysis', 'Ultrasound KUB', 'CT KUB', 'Kidney Function Test']),
                    $this->entry('UTI', 'Infection affecting the urinary tract, most commonly the bladder.', ['Burning during urination', 'Frequent urination', 'Cloudy urine', 'Pelvic pain'], ['Urine Routine & Microscopy', 'Urine Culture & Sensitivity', 'Ultrasound KUB']),
                    $this->entry('Chronic Kidney Disease', 'Gradual loss of kidney function over time.', ['Fatigue', 'Swelling in legs', 'Shortness of breath', 'Nausea', 'Itching'], ['Kidney Function Test', 'Urinalysis', 'Ultrasound Kidney', 'Urine Albumin']),
                ],
            ],
            'OPTH' => [
                'department_name' => 'Eye Care',
                'diseases'        => [
                    $this->entry('Cataract', 'Clouding of the lens causing blurry vision.', ['Cloudy or blurred vision', 'Sensitivity to light', 'Difficulty seeing at night', 'Fading colors'], ['Slit Lamp Examination', 'Visual Acuity Test', 'Tonometry', 'Biometry']),
                    $this->entry('Glaucoma', 'Optic nerve damage often linked to high eye pressure.', ['Gradual vision loss', 'Eye pain', 'Halos around lights', 'Red eyes'], ['Tonometry', 'Visual Field Test', 'OCT', 'Fundoscopy']),
                    $this->entry('Eye Infection', 'Infection of the eye or surrounding tissues.', ['Red eyes', 'Eye pain', 'Discharge', 'Itching', 'Blurred vision'], ['Slit Lamp Examination', 'Eye Swab Culture', 'Visual Acuity Test']),
                ],
            ],
            'GYNE' => [
                'department_name' => 'Women\'s Health',
                'diseases'        => [
                    $this->entry('PCOD', 'Hormonal disorder common among women of reproductive age.', ['Irregular periods', 'Excess hair growth', 'Acne', 'Weight gain', 'Difficulty getting pregnant'], ['Ultrasound Pelvis', 'LH', 'FSH', 'Testosterone', 'Fasting Insulin', 'HbA1c']),
                    $this->entry('Infertility', 'Inability to conceive after one year of regular unprotected intercourse.', ['Inability to get pregnant', 'Irregular periods', 'Hormonal changes'], ['Hormone Panel', 'Semen Analysis', 'HSG', 'Ultrasound Pelvis', 'Thyroid Function Test']),
                    $this->entry('Menstrual Disorder', 'Abnormal or painful menstrual cycles.', ['Heavy bleeding', 'Irregular cycles', 'Severe cramps', 'Missed periods'], ['Ultrasound Pelvis', 'Hormone Panel', 'Complete Blood Count', 'Thyroid Function Test']),
                ],
            ],
            'GSUR' => [
                'department_name' => 'Surgical Care',
                'diseases'        => [
                    $this->entry('Appendicitis', 'Inflammation of the appendix requiring prompt care.', ['Sudden abdominal pain', 'Pain near navel moving lower right', 'Nausea', 'Fever', 'Loss of appetite'], ['Complete Blood Count', 'Ultrasound Abdomen', 'CT Abdomen', 'CRP']),
                    $this->entry('Hernia', 'Organ or tissue protrudes through a weak spot in the muscle wall.', ['Visible bulge', 'Pain when lifting', 'Aching sensation', 'Weakness or pressure'], ['Clinical Examination', 'Ultrasound Abdomen', 'CT Abdomen']),
                ],
            ],
            'UROL' => [
                'department_name' => 'Urology Care',
                'diseases'        => [
                    $this->entry('Prostate Enlargement', 'Non-cancerous enlargement of the prostate gland in men.', ['Frequent urination', 'Weak urine stream', 'Difficulty starting urination', 'Incomplete bladder emptying'], ['PSA Test', 'Digital Rectal Exam', 'Ultrasound Prostate', 'Uroflowmetry']),
                    $this->entry('Kidney Stone', 'Hard deposits of minerals forming in the kidneys.', ['Severe flank pain', 'Blood in urine', 'Nausea', 'Frequent urination'], ['Urinalysis', 'Ultrasound KUB', 'CT KUB']),
                ],
            ],
            'ONCO' => [
                'department_name' => 'Cancer Care',
                'diseases'        => [
                    $this->entry('Breast Cancer', 'Cancer that forms in the cells of the breasts.', ['Lump in breast', 'Change in breast shape', 'Nipple discharge', 'Skin dimpling'], ['Mammography', 'Breast Ultrasound', 'Biopsy', 'Tumor Markers']),
                    $this->entry('Leukemia', 'Cancer of blood-forming tissues including bone marrow.', ['Fatigue', 'Frequent infections', 'Easy bruising', 'Bleeding', 'Bone pain'], ['Complete Blood Count', 'Peripheral Blood Smear', 'Bone Marrow Biopsy', 'Flow Cytometry']),
                    $this->entry('Cancer', 'Abnormal cell growth that can invade nearby tissues and spread to other parts of the body.', ['Unexplained weight loss', 'Persistent fatigue', 'Lumps or swelling', 'Pain', 'Changes in skin'], ['Tumor Markers', 'Biopsy', 'CT Scan', 'MRI', 'Complete Blood Count']),
                ],
            ],
            'PSYC' => [
                'department_name' => 'Mental Health',
                'diseases'        => [
                    $this->entry('Depression', 'Mood disorder causing persistent sadness and loss of interest.', ['Persistent sadness', 'Loss of interest', 'Fatigue', 'Sleep disturbances', 'Difficulty concentrating'], ['PHQ-9 Screening', 'Thyroid Function Test', 'Complete Blood Count', 'Vitamin B12', 'Vitamin D']),
                    $this->entry('Anxiety Disorder', 'Excessive persistent worry interfering with daily life.', ['Excessive worry', 'Restlessness', 'Rapid heartbeat', 'Sweating', 'Difficulty sleeping'], ['GAD-7 Screening', 'Thyroid Function Test', 'ECG', 'Complete Blood Count']),
                ],
            ],
            'INTM' => [
                'department_name' => 'General Medicine',
                'diseases'        => [
                    $this->entry('Anemia', 'Condition with insufficient healthy red blood cells.', ['Fatigue', 'Weakness', 'Pale skin', 'Shortness of breath', 'Dizziness'], ['Complete Blood Count', 'Iron Studies', 'Vitamin B12', 'Folate Level']),
                    $this->entry('Typhoid', 'Bacterial infection spread through contaminated food or water.', ['Prolonged fever', 'Weakness', 'Abdominal pain', 'Headache', 'Loss of appetite'], ['Widal Test', 'Blood Culture', 'Typhidot Test', 'Complete Blood Count']),
                    $this->entry('Dengue', 'Mosquito-borne viral infection causing flu-like illness.', ['High fever', 'Severe headache', 'Pain behind eyes', 'Joint and muscle pain', 'Skin rash'], ['NS1 Antigen Test', 'Dengue IgM/IgG', 'Complete Blood Count', 'Platelet Count']),
                    $this->entry('Malaria', 'Parasitic infection transmitted through infected mosquito bites.', ['High fever', 'Chills', 'Sweating', 'Headache', 'Nausea'], ['Peripheral Blood Smear', 'Malaria Antigen Test', 'Complete Blood Count']),
                    $this->entry('HIV/AIDS', 'Viral infection that attacks the immune system and can lead to AIDS.', ['Fever', 'Fatigue', 'Swollen lymph nodes', 'Weight loss', 'Recurrent infections'], ['HIV Antibody Test', 'CD4 Count', 'Viral Load Test', 'Complete Blood Count']),
                ],
            ],
            'RHEU' => [
                'department_name' => 'Joint & Autoimmune Care',
                'diseases'        => [
                    $this->entry('Rheumatoid Arthritis', 'Autoimmune disease causing joint inflammation.', ['Joint pain and swelling', 'Morning stiffness', 'Fatigue', 'Low-grade fever'], ['Rheumatoid Factor', 'Anti-CCP Antibody', 'ESR', 'CRP', 'X-Ray Joints']),
                    $this->entry('Gout', 'Form of arthritis with sudden severe joint pain from urate crystals.', ['Intense joint pain', 'Swelling', 'Redness', 'Warmth in joint'], ['Serum Uric Acid', 'Joint Fluid Analysis', 'X-Ray Joints']),
                ],
            ],
            'HEMA' => [
                'department_name' => 'Blood Disorders',
                'diseases'        => [
                    $this->entry('Anemia', 'Low red blood cell count or hemoglobin.', ['Fatigue', 'Weakness', 'Pale skin', 'Shortness of breath'], ['Complete Blood Count', 'Peripheral Blood Smear', 'Iron Studies', 'Reticulocyte Count']),
                    $this->entry('Thalassemia', 'Inherited blood disorder reducing hemoglobin production.', ['Fatigue', 'Weakness', 'Pale or yellowish skin', 'Slow growth', 'Abdominal swelling'], ['Complete Blood Count', 'Hemoglobin Electrophoresis', 'Iron Studies']),
                ],
            ],
        ];

        /** Intentionally no diseases — for testing empty states in app/admin. */
        $withoutDiseases = [
            'RADI',  // Radiology
            'ANES',  // Anesthesiology
            'PATH',  // Pathology
            'EMER',  // Emergency Medicine
            'CRIT',  // Critical Care
        ];

        $seeded = 0;
        $skipped = 0;

        foreach ($withDiseases as $code => $payload) {
            $speciality = SpecialitiesMaster::query()->where('code', $code)->first();

            if (! $speciality) {
                $this->command?->warn("SpecialityDiseasesSeeder: code [{$code}] not found — skipped.");
                $skipped++;

                continue;
            }

            $speciality->update([
                'department_name' => $payload['department_name'],
                'diseases'        => $payload['diseases'],
            ]);

            $seeded++;
        }

        foreach ($withoutDiseases as $code) {
            $speciality = SpecialitiesMaster::query()->where('code', $code)->first();

            if (! $speciality) {
                continue;
            }

            $speciality->update([
                'diseases' => null,
            ]);
        }

        $this->cleanupLegacyDiseaseRows();

        $this->command?->info("SpecialityDiseasesSeeder: seeded {$seeded} specialities with complete disease JSON.");
        $this->command?->info('SpecialityDiseasesSeeder: '.count($withoutDiseases).' specialities left without diseases (RADI, ANES, PATH, EMER, CRIT).');

        if ($skipped > 0) {
            $this->command?->warn("SpecialityDiseasesSeeder: {$skipped} codes not found in database.");
        }
    }

    /**
     * @param  list<string>  $symptoms
     * @param  list<string>  $recommendedTests
     * @return array{name: string, about: string, symptoms: list<string>, recommended_tests: list<string>}
     */
    private function entry(string $name, string $about, array $symptoms = [], array $recommendedTests = []): array
    {
        return [
            'name'              => $name,
            'about'             => $about,
            'symptoms'          => array_values($symptoms),
            'recommended_tests' => array_values($recommendedTests),
        ];
    }

    /**
     * Remove legacy duplicate rows created when old diseases were merged (code = null).
     * Remaps doctor assignments and disease packages to the parent speciality disease IDs first.
     */
    private function cleanupLegacyDiseaseRows(): void
    {
        $legacyRows = SpecialitiesMaster::query()->whereNull('code')->get();

        if ($legacyRows->isEmpty()) {
            return;
        }

        $nameToParent = $this->buildDiseaseNameToParentMap();
        $legacyIdToParentId = $legacyRows->mapWithKeys(function (SpecialitiesMaster $row) use ($nameToParent) {
            return [$row->id => ($nameToParent[$row->name] ?? null)?->id];
        });

        Doctor::query()
            ->whereNotNull('assigned_diseases')
            ->select(['id', 'assigned_diseases'])
            ->chunkById(100, function ($doctors) use ($legacyIdToParentId) {
                foreach ($doctors as $doctor) {
                    $original = collect((array) ($doctor->assigned_diseases ?? []))
                        ->map(fn ($id) => (int) $id)
                        ->filter(fn ($id) => $id > 0)
                        ->values()
                        ->all();

                    $remapped = collect($original)
                        ->map(fn (int $id) => $legacyIdToParentId->get($id) ?? $id)
                        ->unique()
                        ->values()
                        ->all();

                    if ($remapped !== $original) {
                        $doctor->update(['assigned_diseases' => $remapped ?: null]);
                    }
                }
            });

        DB::table('disease_packages')
            ->whereNotNull('disease_id')
            ->orderBy('id')
            ->get()
            ->each(function ($package) use ($legacyRows, $nameToParent) {
                $referenceId = (int) $package->disease_id;

                if ($referenceId >= 1000) {
                    return;
                }

                if (! $legacyRows->contains('id', $referenceId)) {
                    return;
                }

                $legacy = $legacyRows->firstWhere('id', $referenceId);
                $parent = $nameToParent[$legacy->name] ?? null;

                if (! $parent) {
                    return;
                }

                $index = $this->findDiseaseIndex($parent, $legacy->name);

                if ($index === null) {
                    return;
                }

                DB::table('disease_packages')
                    ->where('id', $package->id)
                    ->update([
                        'disease_id' => $parent->id,
                    ]);
            });

        $deleted = SpecialitiesMaster::query()->whereNull('code')->delete();

        $this->command?->info("SpecialityDiseasesSeeder: removed {$deleted} legacy duplicate disease rows.");
    }

    /**
     * @return array<string, SpecialitiesMaster>
     */
    private function buildDiseaseNameToParentMap(): array
    {
        $map = [];

        SpecialitiesMaster::query()
            ->whereNotNull('code')
            ->where('status', 'active')
            ->orderBy('id')
            ->get()
            ->each(function (SpecialitiesMaster $parent) use (&$map) {
                foreach ($parent->fresh()->normalizedDiseaseEntries() as $entry) {
                    $map[(string) $entry['name']] = $parent;
                }
            });

        return $map;
    }

    private function findDiseaseIndex(SpecialitiesMaster $speciality, string $diseaseName): ?int
    {
        foreach ($speciality->fresh()->normalizedDiseaseEntries() as $index => $entry) {
            if (strcasecmp((string) $entry['name'], $diseaseName) === 0) {
                return $index;
            }
        }

        return null;
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterQualification;

class MasterQualificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $qualifications = [
            [
                'name' => 'MBBS',
                'description' => 'Bachelor of Medicine, Bachelor of Surgery - Basic medical degree required to practice medicine',
            ],
            [
                'name' => 'MD',
                'description' => 'Doctor of Medicine - Postgraduate degree in medicine, specialization in internal medicine or other medical fields',
            ],
            [
                'name' => 'MS',
                'description' => 'Master of Surgery - Postgraduate degree in surgery, specialization in surgical procedures',
            ],
            [
                'name' => 'DM',
                'description' => 'Doctorate of Medicine - Super-specialization degree in medical fields like Cardiology, Neurology, etc.',
            ],
            [
                'name' => 'MCh',
                'description' => 'Master of Chirurgiae - Super-specialization degree in surgical fields like Neurosurgery, Cardiac Surgery, etc.',
            ],
            [
                'name' => 'DNB',
                'description' => 'Diplomate of National Board - Postgraduate medical qualification awarded by the National Board of Examinations',
            ],
            [
                'name' => 'BDS',
                'description' => 'Bachelor of Dental Surgery - Undergraduate degree in dentistry',
            ],
            [
                'name' => 'MDS',
                'description' => 'Master of Dental Surgery - Postgraduate degree in dental specialties',
            ],
            [
                'name' => 'BAMS',
                'description' => 'Bachelor of Ayurvedic Medicine and Surgery - Degree in Ayurvedic medicine',
            ],
            [
                'name' => 'BHMS',
                'description' => 'Bachelor of Homeopathic Medicine and Surgery - Degree in Homeopathic medicine',
            ],
            [
                'name' => 'BUMS',
                'description' => 'Bachelor of Unani Medicine and Surgery - Degree in Unani medicine',
            ],
            [
                'name' => 'BPT',
                'description' => 'Bachelor of Physiotherapy - Degree in physiotherapy and rehabilitation',
            ],
            [
                'name' => 'MPT',
                'description' => 'Master of Physiotherapy - Postgraduate degree in physiotherapy',
            ],
            [
                'name' => 'B.Pharm',
                'description' => 'Bachelor of Pharmacy - Undergraduate degree in pharmacy',
            ],
            [
                'name' => 'M.Pharm',
                'description' => 'Master of Pharmacy - Postgraduate degree in pharmacy',
            ],
            [
                'name' => 'B.Sc Nursing',
                'description' => 'Bachelor of Science in Nursing - Undergraduate degree in nursing',
            ],
            [
                'name' => 'M.Sc Nursing',
                'description' => 'Master of Science in Nursing - Postgraduate degree in nursing',
            ],
            [
                'name' => 'Diploma in Medical',
                'description' => 'Diploma level qualification in various medical fields',
            ],
            [
                'name' => 'FCPS',
                'description' => 'Fellow of College of Physicians and Surgeons - Postgraduate medical qualification',
            ],
            [
                'name' => 'MRCP',
                'description' => 'Member of the Royal College of Physicians - Postgraduate medical qualification from UK',
            ],
            [
                'name' => 'FRCS',
                'description' => 'Fellow of the Royal College of Surgeons - Postgraduate surgical qualification from UK',
            ],
            [
                'name' => 'MRCOG',
                'description' => 'Member of the Royal College of Obstetricians and Gynaecologists - Specialization in obstetrics and gynecology',
            ],
            [
                'name' => 'DGO',
                'description' => 'Diploma in Gynecology and Obstetrics - Diploma in women\'s health and childbirth',
            ],
            [
                'name' => 'DCH',
                'description' => 'Diploma in Child Health - Specialization in pediatric medicine',
            ],
            [
                'name' => 'D.Ortho',
                'description' => 'Diploma in Orthopedics - Specialization in bone and joint disorders',
            ],
            [
                'name' => 'DLO',
                'description' => 'Diploma in Laryngology and Otology - Specialization in ear, nose, and throat',
            ],
            [
                'name' => 'DOMS',
                'description' => 'Diploma in Ophthalmic Medicine and Surgery - Specialization in eye care',
            ],
            [
                'name' => 'DDVL',
                'description' => 'Diploma in Dermatology, Venereology and Leprosy - Specialization in skin diseases',
            ],
            [
                'name' => 'DA',
                'description' => 'Diploma in Anesthesia - Specialization in anesthesia and pain management',
            ],
            [
                'name' => 'DMRD',
                'description' => 'Diploma in Medical Radio-Diagnosis - Specialization in radiology and diagnostic imaging',
            ],
            [
                'name' => 'DCP',
                'description' => 'Diploma in Clinical Pathology - Specialization in laboratory medicine and pathology',
            ],
        ];

        foreach ($qualifications as $qualification) {
            MasterQualification::updateOrCreate(
                ['name' => $qualification['name']],
                $qualification
            );
        }

        $this->command->info('Master Qualifications seeded successfully!');
    }
}


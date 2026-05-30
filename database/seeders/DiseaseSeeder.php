<?php

namespace Database\Seeders;

use App\Models\Disease;
use Illuminate\Database\Seeder;

class DiseaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $diseases = [
            [
                'name' => 'Diabetes',
                'about' => 'A chronic condition that affects how your body processes blood sugar (glucose).',
                'symptoms' => ['Frequent urination', 'Excess thirst', 'Fatigue', 'Blurred vision', 'Slow-healing sores'],
                'recommended_tests' => ['Fasting Blood Sugar', 'HbA1c', 'Oral Glucose Tolerance Test', 'Random Blood Sugar', 'Urine Microalbumin'],
            ],
            [
                'name' => 'Hypertension',
                'about' => 'A long-term condition in which blood pressure in the arteries is persistently elevated.',
                'symptoms' => ['Headaches', 'Shortness of breath', 'Nosebleeds', 'Chest pain', 'Dizziness'],
                'recommended_tests' => ['Blood Pressure Monitoring', 'ECG', 'Lipid Profile', 'Kidney Function Test', 'Echocardiogram'],
            ],
            [
                'name' => 'Asthma',
                'about' => 'A chronic lung condition that causes inflammation and narrowing of the airways.',
                'symptoms' => ['Wheezing', 'Shortness of breath', 'Chest tightness', 'Coughing', 'Difficulty breathing at night'],
                'recommended_tests' => ['Spirometry', 'Peak Flow Test', 'Chest X-Ray', 'Allergy Panel', 'FeNO Test'],
            ],
            [
                'name' => 'Heart Disease',
                'about' => 'A group of conditions that affect the heart and blood vessels, often involving narrowed or blocked arteries.',
                'symptoms' => ['Chest pain', 'Shortness of breath', 'Fatigue', 'Irregular heartbeat', 'Swelling in legs'],
                'recommended_tests' => ['ECG', 'Echocardiogram', 'Lipid Profile', 'Troponin Test', 'Stress Test'],
            ],
            [
                'name' => 'Cancer',
                'about' => 'A disease caused by uncontrolled growth and spread of abnormal cells in the body.',
                'symptoms' => ['Unexplained weight loss', 'Persistent fatigue', 'Lumps or swelling', 'Persistent pain', 'Changes in skin or moles'],
                'recommended_tests' => ['Biopsy', 'Complete Blood Count', 'Tumor Markers', 'CT Scan', 'PET Scan'],
            ],
            [
                'name' => 'Tuberculosis',
                'about' => 'A serious bacterial infection caused by Mycobacterium tuberculosis, mainly affecting the lungs.',
                'symptoms' => ['Persistent cough', 'Blood in sputum', 'Chest pain', 'Night sweats', 'Weight loss'],
                'recommended_tests' => ['Sputum AFB Test', 'Chest X-Ray', 'Mantoux Test (TST)', 'GeneXpert MTB/RIF', 'Sputum Culture'],
            ],
            [
                'name' => 'COVID-19',
                'about' => 'An infectious disease caused by the SARS-CoV-2 virus, primarily affecting the respiratory system.',
                'symptoms' => ['Fever', 'Dry cough', 'Fatigue', 'Loss of taste or smell', 'Shortness of breath'],
                'recommended_tests' => ['RT-PCR Test', 'Rapid Antigen Test', 'Chest CT Scan', 'CRP', 'Complete Blood Count'],
            ],
            [
                'name' => 'Dengue',
                'about' => 'A mosquito-borne viral infection that can cause a severe flu-like illness and, in some cases, serious complications.',
                'symptoms' => ['High fever', 'Severe headache', 'Pain behind the eyes', 'Joint and muscle pain', 'Skin rash'],
                'recommended_tests' => ['NS1 Antigen Test', 'Dengue IgM/IgG', 'Complete Blood Count', 'Platelet Count', 'Hematocrit'],
            ],
            [
                'name' => 'Malaria',
                'about' => 'A life-threatening disease caused by parasites transmitted to humans through infected mosquito bites.',
                'symptoms' => ['High fever', 'Chills and sweating', 'Headache', 'Nausea and vomiting', 'Body aches'],
                'recommended_tests' => ['Peripheral Blood Smear', 'Rapid Diagnostic Test (RDT)', 'Complete Blood Count', 'Liver Function Test', 'Parasite Antigen Test'],
            ],
            [
                'name' => 'Typhoid',
                'about' => 'A bacterial infection caused by Salmonella typhi, usually spread through contaminated food or water.',
                'symptoms' => ['Prolonged fever', 'Weakness', 'Abdominal pain', 'Headache', 'Loss of appetite'],
                'recommended_tests' => ['Widal Test', 'Blood Culture', 'Typhidot Test', 'Stool Culture', 'Complete Blood Count'],
            ],
            [
                'name' => 'Pneumonia',
                'about' => 'An infection that inflames the air sacs in one or both lungs, which may fill with fluid or pus.',
                'symptoms' => ['Cough with phlegm', 'Fever and chills', 'Shortness of breath', 'Chest pain', 'Fatigue'],
                'recommended_tests' => ['Chest X-Ray', 'Complete Blood Count', 'Sputum Culture', 'Blood Culture', 'CRP'],
            ],
            [
                'name' => 'Migraine',
                'about' => 'A neurological condition characterized by recurrent moderate to severe headaches, often with other symptoms.',
                'symptoms' => ['Throbbing headache', 'Nausea', 'Sensitivity to light', 'Sensitivity to sound', 'Visual disturbances'],
                'recommended_tests' => ['MRI Brain', 'CT Brain', 'Neurological Examination', 'Eye Examination', 'Blood Pressure Check'],
            ],
            [
                'name' => 'Arthritis',
                'about' => 'Inflammation of one or more joints that causes pain, stiffness, and reduced mobility.',
                'symptoms' => ['Joint pain', 'Joint stiffness', 'Swelling', 'Reduced range of motion', 'Warmth around joints'],
                'recommended_tests' => ['Rheumatoid Factor', 'Anti-CCP Antibody', 'ESR', 'CRP', 'X-Ray Joints'],
            ],
            [
                'name' => 'Kidney Stone',
                'about' => 'Hard deposits of minerals and salts that form inside the kidneys and can cause severe pain when passing.',
                'symptoms' => ['Severe back or side pain', 'Pain during urination', 'Blood in urine', 'Nausea', 'Frequent urination'],
                'recommended_tests' => ['Urinalysis', 'KUB X-Ray', 'Ultrasound KUB', 'CT KUB', 'Kidney Function Test'],
            ],
            [
                'name' => 'Liver Disease',
                'about' => 'Conditions that damage the liver and affect its ability to perform essential functions such as filtering blood.',
                'symptoms' => ['Jaundice', 'Abdominal pain', 'Swelling in legs', 'Dark urine', 'Persistent fatigue'],
                'recommended_tests' => ['Liver Function Test', 'Ultrasound Abdomen', 'Viral Hepatitis Panel', 'PT/INR', 'Alpha-Fetoprotein (AFP)'],
            ],
            [
                'name' => 'Thyroid Disorder',
                'about' => 'Conditions that affect the thyroid gland and disrupt production of hormones that regulate metabolism.',
                'symptoms' => ['Unexplained weight changes', 'Fatigue', 'Hair loss', 'Mood changes', 'Sensitivity to cold or heat'],
                'recommended_tests' => ['TSH', 'Free T3', 'Free T4', 'Thyroid Antibodies (Anti-TPO)', 'Thyroid Ultrasound'],
            ],
            [
                'name' => 'Epilepsy',
                'about' => 'A neurological disorder marked by recurrent, unprovoked seizures due to abnormal electrical activity in the brain.',
                'symptoms' => ['Seizures', 'Temporary confusion', 'Staring spells', 'Uncontrolled jerking movements', 'Loss of awareness'],
                'recommended_tests' => ['EEG', 'MRI Brain', 'Video EEG Monitoring', 'Complete Blood Count', 'Electrolyte Panel'],
            ],
            [
                'name' => 'Stroke',
                'about' => 'A medical emergency that occurs when blood supply to part of the brain is interrupted or reduced.',
                'symptoms' => ['Sudden numbness', 'Confusion', 'Trouble speaking', 'Vision problems', 'Severe headache'],
                'recommended_tests' => ['CT Brain', 'MRI Brain', 'Carotid Doppler', 'ECG', 'Lipid Profile'],
            ],
            [
                'name' => 'Depression',
                'about' => 'A common and serious mood disorder that negatively affects how you feel, think, and handle daily activities.',
                'symptoms' => ['Persistent sadness', 'Loss of interest', 'Fatigue', 'Sleep disturbances', 'Difficulty concentrating'],
                'recommended_tests' => ['PHQ-9 Screening', 'Thyroid Function Test', 'Complete Blood Count', 'Vitamin B12', 'Vitamin D'],
            ],
            [
                'name' => 'Anxiety Disorder',
                'about' => 'A mental health condition involving excessive, persistent worry or fear that interferes with daily life.',
                'symptoms' => ['Excessive worry', 'Restlessness', 'Rapid heartbeat', 'Sweating', 'Difficulty sleeping'],
                'recommended_tests' => ['GAD-7 Screening', 'Thyroid Function Test', 'ECG', 'Complete Blood Count', 'Cortisol Level'],
            ],
            [
                'name' => 'Obesity',
                'about' => 'A complex condition involving excess body fat that increases the risk of other health problems.',
                'symptoms' => ['Excess body weight', 'Breathlessness', 'Snoring', 'Joint pain', 'Fatigue'],
                'recommended_tests' => ['BMI Assessment', 'Lipid Profile', 'Fasting Blood Sugar', 'HbA1c', 'Thyroid Function Test'],
            ],
            [
                'name' => 'Cholesterol',
                'about' => 'A condition marked by high levels of cholesterol in the blood, which can lead to fatty deposits in arteries.',
                'symptoms' => ['Usually no symptoms', 'Chest pain', 'Shortness of breath', 'Leg pain when walking', 'Heart attack or stroke risk'],
                'recommended_tests' => ['Lipid Profile', 'LDL Cholesterol', 'HDL Cholesterol', 'Triglycerides', 'Lipoprotein (a)'],
            ],
            [
                'name' => 'Gastric Ulcer',
                'about' => 'Open sores that develop on the inner lining of the stomach, often caused by infection or long-term NSAID use.',
                'symptoms' => ['Burning stomach pain', 'Bloating', 'Nausea', 'Heartburn', 'Loss of appetite'],
                'recommended_tests' => ['Upper GI Endoscopy', 'H. pylori Test', 'Stool Occult Blood', 'Complete Blood Count', 'Urea Breath Test'],
            ],
            [
                'name' => 'GERD',
                'about' => 'Gastroesophageal reflux disease occurs when stomach acid frequently flows back into the esophagus.',
                'symptoms' => ['Heartburn', 'Regurgitation', 'Chest pain', 'Difficulty swallowing', 'Chronic cough'],
                'recommended_tests' => ['Upper GI Endoscopy', 'Esophageal pH Monitoring', 'Barium Swallow', 'Esophageal Manometry', 'H. pylori Test'],
            ],
            [
                'name' => 'Anemia',
                'about' => 'A condition in which you lack enough healthy red blood cells to carry adequate oxygen to body tissues.',
                'symptoms' => ['Fatigue', 'Weakness', 'Pale skin', 'Shortness of breath', 'Dizziness'],
                'recommended_tests' => ['Complete Blood Count', 'Iron Studies', 'Peripheral Blood Smear', 'Vitamin B12', 'Folate Level'],
            ],
            [
                'name' => 'Skin Allergy',
                'about' => 'An immune reaction that causes redness, itching, or rash when the skin comes into contact with an allergen.',
                'symptoms' => ['Itching', 'Red rash', 'Hives', 'Swelling', 'Dry or cracked skin'],
                'recommended_tests' => ['Skin Prick Test', 'Patch Test', 'Total IgE', 'Specific IgE Panel', 'Complete Blood Count'],
            ],
            [
                'name' => 'Psoriasis',
                'about' => 'A chronic autoimmune skin condition that causes rapid buildup of skin cells, forming scales and red patches.',
                'symptoms' => ['Red patches with silvery scales', 'Itching', 'Dry cracked skin', 'Thickened nails', 'Joint pain'],
                'recommended_tests' => ['Skin Biopsy', 'ESR', 'CRP', 'Rheumatoid Factor', 'Uric Acid'],
            ],
            [
                'name' => 'Eczema',
                'about' => 'A group of conditions that cause the skin to become inflamed, itchy, and irritated, often starting in childhood.',
                'symptoms' => ['Itchy skin', 'Red or brown patches', 'Dry sensitive skin', 'Swelling', 'Crusting or oozing'],
                'recommended_tests' => ['Skin Prick Test', 'Total IgE', 'Patch Test', 'Specific IgE Panel', 'Complete Blood Count'],
            ],
            [
                'name' => 'Chickenpox',
                'about' => 'A highly contagious viral infection caused by the varicella-zoster virus, common in children.',
                'symptoms' => ['Itchy rash with blisters', 'Fever', 'Headache', 'Fatigue', 'Loss of appetite'],
                'recommended_tests' => ['Clinical Examination', 'Varicella IgM', 'Varicella IgG', 'Complete Blood Count', 'Tzanck Smear'],
            ],
            [
                'name' => 'Hepatitis',
                'about' => 'Inflammation of the liver, most commonly caused by viral infections, alcohol use, or toxins.',
                'symptoms' => ['Jaundice', 'Abdominal pain', 'Dark urine', 'Fatigue', 'Nausea'],
                'recommended_tests' => ['Liver Function Test', 'HBsAg', 'Anti-HCV', 'Viral Load', 'Ultrasound Liver'],
            ],
            [
                'name' => 'HIV/AIDS',
                'about' => 'HIV attacks the immune system and, if untreated, can progress to AIDS, severely weakening the body\'s defenses.',
                'symptoms' => ['Fever', 'Fatigue', 'Swollen lymph nodes', 'Weight loss', 'Recurrent infections'],
                'recommended_tests' => ['HIV ELISA', 'Western Blot', 'CD4 Count', 'HIV Viral Load', 'Complete Blood Count'],
            ],
            [
                'name' => 'Parkinson Disease',
                'about' => 'A progressive nervous system disorder that affects movement, often causing tremors and stiffness.',
                'symptoms' => ['Tremor', 'Slowed movement', 'Muscle stiffness', 'Impaired balance', 'Speech changes'],
                'recommended_tests' => ['Neurological Examination', 'DaTscan', 'MRI Brain', 'Complete Blood Count', 'Thyroid Function Test'],
            ],
            [
                'name' => 'Alzheimer Disease',
                'about' => 'A progressive brain disorder that slowly destroys memory, thinking skills, and the ability to carry out daily tasks.',
                'symptoms' => ['Memory loss', 'Confusion', 'Difficulty planning', 'Mood changes', 'Trouble recognizing people'],
                'recommended_tests' => ['MMSE Assessment', 'MoCA Test', 'MRI Brain', 'CSF Analysis', 'Thyroid Function Test'],
            ],
            [
                'name' => 'Osteoporosis',
                'about' => 'A bone disease that develops when bone mineral density and mass decrease, making bones weak and brittle.',
                'symptoms' => ['Back pain', 'Loss of height', 'Stooped posture', 'Bone fractures', 'Often no early symptoms'],
                'recommended_tests' => ['DEXA Scan', 'Calcium Level', 'Vitamin D', 'Bone Markers (P1NP, CTX)', 'X-Ray Spine'],
            ],
            [
                'name' => 'PCOD',
                'about' => 'Polycystic ovary syndrome is a hormonal disorder common among women of reproductive age with enlarged ovaries.',
                'symptoms' => ['Irregular periods', 'Excess hair growth', 'Acne', 'Weight gain', 'Difficulty getting pregnant'],
                'recommended_tests' => ['Ultrasound Pelvis', 'LH', 'FSH', 'Testosterone', 'Fasting Insulin', 'HbA1c'],
            ],
            [
                'name' => 'Infertility',
                'about' => 'The inability to conceive after one year of regular unprotected intercourse, affecting either partner.',
                'symptoms' => ['Inability to get pregnant', 'Irregular or absent periods', 'Hormonal changes', 'Pain during intercourse', 'Often no other symptoms'],
                'recommended_tests' => ['Hormone Panel (FSH, LH, AMH)', 'Semen Analysis', 'HSG (Hysterosalpingography)', 'Ultrasound Pelvis', 'Thyroid Function Test'],
            ],
            [
                'name' => 'Sinusitis',
                'about' => 'Inflammation or swelling of the tissue lining the sinuses, often caused by infection or allergies.',
                'symptoms' => ['Facial pain', 'Nasal congestion', 'Thick nasal discharge', 'Reduced sense of smell', 'Headache'],
                'recommended_tests' => ['X-Ray PNS', 'CT PNS', 'Nasal Endoscopy', 'Complete Blood Count', 'Allergy Panel'],
            ],
            [
                'name' => 'Bronchitis',
                'about' => 'Inflammation of the lining of the bronchial tubes, which carry air to and from the lungs.',
                'symptoms' => ['Persistent cough', 'Mucus production', 'Fatigue', 'Shortness of breath', 'Chest discomfort'],
                'recommended_tests' => ['Chest X-Ray', 'Complete Blood Count', 'Sputum Culture', 'Spirometry', 'CRP'],
            ],
            [
                'name' => 'Jaundice',
                'about' => 'A condition in which the skin, whites of the eyes, and mucous membranes turn yellow due to high bilirubin levels.',
                'symptoms' => ['Yellow skin and eyes', 'Dark urine', 'Pale stools', 'Itching', 'Abdominal pain'],
                'recommended_tests' => ['Liver Function Test', 'Bilirubin (Total & Direct)', 'Ultrasound Abdomen', 'Viral Hepatitis Panel', 'Complete Blood Count'],
            ],
            [
                'name' => 'Appendicitis',
                'about' => 'Inflammation of the appendix, a small pouch attached to the large intestine, requiring prompt medical care.',
                'symptoms' => ['Sudden abdominal pain', 'Pain near navel moving to lower right', 'Nausea', 'Fever', 'Loss of appetite'],
                'recommended_tests' => ['Complete Blood Count', 'Ultrasound Abdomen', 'CT Abdomen', 'CRP', 'Urinalysis'],
            ],
            [
                'name' => 'Food Poisoning',
                'about' => 'Illness caused by eating contaminated food or drink containing harmful bacteria, viruses, or toxins.',
                'symptoms' => ['Nausea', 'Vomiting', 'Diarrhea', 'Abdominal cramps', 'Fever'],
                'recommended_tests' => ['Stool Culture', 'Stool Routine & Microscopy', 'Complete Blood Count', 'Electrolyte Panel', 'Stool Occult Blood'],
            ],
            [
                'name' => 'Eye Infection',
                'about' => 'An infection of the eye or surrounding tissues caused by bacteria, viruses, fungi, or parasites.',
                'symptoms' => ['Red eyes', 'Eye pain', 'Discharge', 'Itching', 'Blurred vision'],
                'recommended_tests' => ['Slit Lamp Examination', 'Eye Swab Culture', 'Visual Acuity Test', 'Fluorescein Staining', 'Intraocular Pressure'],
            ],
            [
                'name' => 'Glaucoma',
                'about' => 'A group of eye conditions that damage the optic nerve, often linked to abnormally high pressure in the eye.',
                'symptoms' => ['Gradual vision loss', 'Eye pain', 'Halos around lights', 'Red eyes', 'Often no early symptoms'],
                'recommended_tests' => ['Tonometry', 'Visual Field Test', 'OCT (Optical Coherence Tomography)', 'Fundoscopy', 'Gonioscopy'],
            ],
            [
                'name' => 'Cataract',
                'about' => 'Clouding of the normally clear lens of the eye, leading to blurry vision and difficulty seeing at night.',
                'symptoms' => ['Cloudy or blurred vision', 'Sensitivity to light', 'Difficulty seeing at night', 'Fading colors', 'Double vision in one eye'],
                'recommended_tests' => ['Slit Lamp Examination', 'Visual Acuity Test', 'Retinal Examination', 'Tonometry', 'Biometry (IOL Calculation)'],
            ],
            [
                'name' => 'Ear Infection',
                'about' => 'An infection of the middle ear, common in children, often following a cold or respiratory infection.',
                'symptoms' => ['Ear pain', 'Fluid drainage from ear', 'Hearing difficulty', 'Fever', 'Irritability in children'],
                'recommended_tests' => ['Otoscopy', 'Tympanometry', 'Audiometry', 'Complete Blood Count', 'Ear Swab Culture'],
            ],
            [
                'name' => 'UTI',
                'about' => 'A urinary tract infection affects any part of the urinary system, most commonly the bladder and urethra.',
                'symptoms' => ['Burning during urination', 'Frequent urination', 'Cloudy urine', 'Pelvic pain', 'Strong-smelling urine'],
                'recommended_tests' => ['Urine Routine & Microscopy', 'Urine Culture & Sensitivity', 'Ultrasound KUB', 'Complete Blood Count', 'Kidney Function Test'],
            ],
            [
                'name' => 'Back Pain',
                'about' => 'Pain felt anywhere along the spine, most commonly in the lower back, due to strain, injury, or underlying conditions.',
                'symptoms' => ['Muscle ache', 'Stabbing pain', 'Limited flexibility', 'Pain radiating to leg', 'Difficulty standing straight'],
                'recommended_tests' => ['X-Ray Spine', 'MRI Spine', 'Complete Blood Count', 'ESR', 'CRP'],
            ],
            [
                'name' => 'Slip Disc',
                'about' => 'A herniated disc occurs when the soft inner portion of a spinal disc pushes through a tear in the outer ring.',
                'symptoms' => ['Arm or leg pain', 'Numbness or tingling', 'Weakness', 'Back pain', 'Pain worsening with movement'],
                'recommended_tests' => ['MRI Spine', 'X-Ray Spine', 'Nerve Conduction Study', 'EMG', 'CT Myelography'],
            ],
            [
                'name' => 'Fracture',
                'about' => 'A break in the continuity of a bone, usually caused by trauma, overuse, or conditions that weaken bones.',
                'symptoms' => ['Severe pain', 'Swelling', 'Bruising', 'Deformity', 'Inability to move the affected area'],
                'recommended_tests' => ['X-Ray', 'CT Scan', 'Bone Density Test (DEXA)', 'Complete Blood Count', 'MRI (if soft tissue injury suspected)'],
            ],
            [
                'name' => 'Leukemia',
                'about' => 'A cancer of the body\'s blood-forming tissues, including bone marrow, causing abnormal white blood cell production.',
                'symptoms' => ['Fatigue', 'Frequent infections', 'Easy bruising', 'Bleeding', 'Bone pain'],
                'recommended_tests' => ['Complete Blood Count', 'Peripheral Blood Smear', 'Bone Marrow Biopsy', 'Flow Cytometry', 'Cytogenetic Analysis'],
            ],
        ];

        foreach ($diseases as $disease) {
            Disease::updateOrCreate(
                ['name' => $disease['name']],
                [
                    'about' => $disease['about'],
                    'symptoms' => $disease['symptoms'],
                    'recommended_tests' => $disease['recommended_tests'],
                    'is_active' => true,
                ]
            );
        }
    }
}

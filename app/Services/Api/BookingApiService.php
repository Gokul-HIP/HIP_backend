<?php

namespace App\Services\Api;

use App\Models\CareGiver;
use App\Models\CaregiverBooking;
use App\Models\DiagnosticPackage;
use App\Models\StemCellBooking;
use App\Models\DiagnosticTestBooking;
use App\Models\WellnessBooking;
use App\Models\DoctorBooking;
use App\Models\ProcedureBooking;
use App\Models\WellnessCenters;
use Illuminate\Support\Facades\Log;

class BookingApiService
{
    public function wellnessList(){

        $wellnssCenters = WellnessCenters::with('wellnessCategory')->select('id','centre_name','centre_type')->paginate(10);
        return $wellnssCenters;

    }

    public function wellnessDetails($id){

        $wellnessCenter = WellnessCenters::with('wellnessCategory')
            ->select('id', 'centre_name', 'centre_type', 'address_line_1', 'address_line_2', 'city', 'state', 'pincode', 'latitude', 'longitude', 'contact_person_name', 'contact_person_mobile', 'contact_person_email', 'centre_website', 'centre_instagram_links', 'centre_facebook_links', 
            'centre_linkedin_links', 'centre_twitter_links', 'centre_youtube_links')->find($id);

        return $wellnessCenter;    

    }

    public function procedureBooking($request){

        $procedureBooking = ProcedureBooking::create([
            'name' => $request->name,
            'mobile_number' => $request->mobile_number,
            'message' => $request->message,
            'procedure_id' => $request->procedure_id,
            'hospital_id' => $request->hospital_id,
        ]);
        
        return $procedureBooking;

    }

    public function doctorBooking($request, $memberId = null){

        $doctorBooking = DoctorBooking::create([
            'name' => $request->name,
            'mobile_number' => $request->mobile_number,
            'member_id' => $memberId,
            'hospital_id' => $request->hospital_id,
            'doctor_id' => $request->doctor_id,
            'booking_date' => $request->booking_date,
            'required_time_slots' => $request->required_time_slots,
            'purpose' => $request->purpose,
        ]);

        return $doctorBooking;

    }

    public function wellnessBooking($request, $memberId = null){

        $wellnessBooking = WellnessBooking::create([
            'name' => $request->name,
            'mobile_number' => $request->mobile_number,
            'member_id' => $memberId,
            'center_id' => $request->center_id,
            'consultation_type' => "In-Person",
            'purpose' => $request->purpose ?? null,
        ]);

        return $wellnessBooking;

    }

    public function diagnosticTestBooking($request, $memberId =null ){

        if($request->test_type == 'single' || $request->test_type == 'multi'){

            $diagnosticTestBooking = DiagnosticTestBooking::create([
                'name' => $request->name,
                'mobile_number' => $request->mobile_number,
                'member_id' => $memberId,
                'diagnostic_center_id' => $request->diagnostic_center_id,
                'test_type' => $request->test_type,
                'test_items' => $request->test_items,
                'sample_collection' => $request->sample_collection,
                'booking_date' => $request->booking_date,
                'required_time_slots' => $request->required_time_slots,
                'purpose' => $request->purpose,
            ]);

            return $diagnosticTestBooking;

        }

        if($request->test_type == 'package'){

            $package = DiagnosticPackage::find($request->package_id);

            $testItesms = $package?->lab_tests ?? [];

            if(empty($testItesms)){
                return null;
            }

            if(is_string($testItesms)) {
                $testItesms = json_decode($testItesms, true);
            }
            
            $diagnosticTestBooking = DiagnosticTestBooking::create([
                'name' => $request->name,
                'mobile_number' => $request->mobile_number,
                'member_id' => $memberId,
                'diagnostic_center_id' => $request->diagnostic_center_id,
                'test_type' => $request->test_type,
                'test_items' => $testItesms,
                'sample_collection' => $request->sample_collection,
                'booking_date' => $request->booking_date,
                'required_time_slots' => $request->required_time_slots,
                'purpose' => $request->purpose,
            ]);

            return $diagnosticTestBooking;

        }

        return null;

    }

    public function stemCellBooking($request, $memberId = null){

        $stemCellBooking = StemCellBooking::create([
            'name' => $request->name,
            'mobile_number' => $request->mobile_number,
            'member_id' => $memberId,
            'booking_date' => $request->booking_date,
            'required_time_slots' => $request->required_time_slots,
            'purpose' => $request->purpose,
            'status' => 'enquiry',
        ]);

        return $stemCellBooking;

    }
    
}
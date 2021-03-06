<?php

namespace App;

use App\Lookup;

use App\OldModels\WorksheetView;
use App\OldModels\ViralworksheetView;

use App\OldModels\SampleView;
use App\OldModels\ViralsampleView;

use App\Mother;
use App\Patient;
use App\Batch;
use App\Sample;

use App\Viralpatient;
use App\Viralbatch;
use App\Viralsample;


use App\Worksheet;
use App\Viralworksheet;

class Copier
{
	private static $limit = 10000;

	public static function copy_eid()
	{
		$start = Sample::max('id');
		ini_set("memory_limit", "-1");
		$fields = Lookup::samples_arrays();	
        $sample_date_array = ['datecollected', 'datetested', 'datemodified', 'dateapproved', 'dateapproved2'];
        $batch_date_array = ['datedispatchedfromfacility', 'datereceived', 'datedispatched', 'dateindividualresultprinted', 'datebatchprinted'];

		$offset_value = 0;
		while(true)
		{
			$samples = SampleView::when($start, function($query) use ($start){
				return $query->where('id', '>', $start);
			})->limit(self::$limit)->offset($offset_value)->get();
			if($samples->isEmpty()) break;
			

			foreach ($samples as $key => $value) {
				$patient = Patient::existing($value->facility_id, $value->patient)->get()->first();

				if(!$patient){
					$mother = new Mother($value->only($fields['mother']));
					$mother->save();
					$patient = new Patient($value->only($fields['patient']));
					$patient->mother_id = $mother->id;

                    if($patient->dob) $patient->dob = Lookup::clean_date($patient->dob);

                    if(!$patient->dob) $patient->dob = Lookup::previous_dob(SampleView::class, $value->patient, $value->facility_id);

                    if(!$patient->dob) $patient->dob = Lookup::calculate_dob($value->datecollected, 0, $value->age, SampleView::class, $value->patient, $value->facility_id);


					$patient->sex = Lookup::resolve_gender($value->gender, SampleView::class, $value->patient, $value->facility_id);
					$enrollment_data = self::get_enrollment_data($value->patient, $value->facility_id);
					if($enrollment_data) $patient->fill($enrollment_data);
					// $patient->ccc_no = $value->enrollment_ccc_no;
					$patient->save();
				}
				
				$value->original_batch_id = self::set_batch_id($value->original_batch_id);
				$batch = Batch::existing($value->original_batch_id, $value->lab_id)->get()->first();

				if(!$batch){
					$batch = new Batch($value->only($fields['batch']));
                    foreach ($batch_date_array as $date_field) {
                        $batch->$date_field = Lookup::clean_date($batch->$date_field);
                    }
					$batch->save();
				}

				$sample = new Sample($value->only($fields['sample']));
                foreach ($sample_date_array as $date_field) {
                    $sample->$date_field = Lookup::clean_date($sample->$date_field);
                }

				$sample->batch_id = $batch->id;
				$sample->patient_id = $patient->id;

                if($sample->age == 0 && $batch->datecollected && $patient->dob){
                    $sample->age = Lookup::calculate_age($batch->datecollected, $patient->dob);
                }

				$sample->save();
			}
			$offset_value += self::$limit;
			echo "Completed eid {$offset_value} at " . date('d/m/Y h:i:s a', time()). "\n";
		}
	}


	public static function copy_vl()
	{
		$start = Viralsample::max('id');
		ini_set("memory_limit", "-1");
		$fields = Lookup::viralsamples_arrays();
        $sample_date_array = ['datecollected', 'datetested', 'datemodified', 'dateapproved', 'dateapproved2'];
        $batch_date_array = ['datedispatchedfromfacility', 'datereceived', 'datedispatched', 'dateindividualresultprinted', 'datebatchprinted'];	
		$offset_value = 0;
		while(true)
		{
			$samples = ViralsampleView::when($start, function($query) use ($start){
				return $query->where('id', '>', $start);
			})->limit(self::$limit)->offset($offset_value)->get();
			if($samples->isEmpty()) break;

			foreach ($samples as $key => $value) {
				$patient = Viralpatient::existing($value->facility_id, $value->patient)->get()->first();

				if(!$patient){
					$patient = new Viralpatient($value->only($fields['patient']));

                    if($patient->dob) $patient->dob = Lookup::clean_date($patient->dob);

                    if(!$patient->dob) $patient->dob = Lookup::previous_dob(ViralsampleView::class, $value->patient, $value->facility_id);
                    if(!$patient->dob) $patient->dob = Lookup::calculate_dob($value->datecollected, $value->age, 0, ViralsampleView::class, $value->patient, $value->facility_id);

					$patient->sex = Lookup::resolve_gender($value->gender, ViralsampleView::class, $value->patient, $value->facility_id);
					$patient->save();
				}

				$value->original_batch_id = self::set_batch_id($value->original_batch_id);
				$batch = Viralbatch::existing($value->original_batch_id, $value->lab_id)->get()->first();

				if(!$batch){
					$batch = new Viralbatch($value->only($fields['batch']));
                    foreach ($batch_date_array as $date_field) {
                        $batch->$date_field = Lookup::clean_date($batch->$date_field);
                    }
					$batch->save();
				}

				$sample = new Viralsample($value->only($fields['sample']));
                foreach ($sample_date_array as $date_field) {
                    $sample->$date_field = Lookup::clean_date($sample->$date_field);
                }
				$sample->batch_id = $batch->id;
				$sample->patient_id = $patient->id;

                if($sample->age == 0 && $batch->datecollected && $patient->dob){
                    $sample->age = Lookup::calculate_viralage($batch->datecollected, $patient->dob);
                }

				$sample->save();
			}
			$offset_value += self::$limit;
			echo "Completed vl {$offset_value} at " . date('d/m/Y h:i:s a', time()). "\n";
		}
	}



    public static function copy_worksheet()
    {
        $work_array = [
            'eid' => ['model' => Worksheet::class, 'view' => WorksheetView::class],
            'vl' => ['model' => Viralworksheet::class, 'view' => ViralworksheetView::class],
        ];

        $date_array = ['kitexpirydate', 'sampleprepexpirydate', 'bulklysisexpirydate', 'controlexpirydate', 'calibratorexpirydate', 'amplificationexpirydate', 'datecut', 'datereviewed', 'datereviewed2', 'datecancelled', 'daterun', 'created_at'];

        ini_set("memory_limit", "-1");

        foreach ($work_array as $key => $value) {
            $model = $value['model'];
            $view = $value['view'];

            $start = $model::max('id');              

            $offset_value = 0;
            while(true)
            {
                $worksheets = $view::when($start, function($query) use ($start){
                    return $query->where('id', '>', $start);
                })->limit(self::$limit)->offset($offset_value)->get();
                if($worksheets->isEmpty()) break;

                foreach ($worksheets as $worksheet_key => $worksheet) {
                    $duplicate = $worksheet->replicate();
                    $work = new $model;                    
                    $work->fill($duplicate->toArray());
                    foreach ($date_array as $date_field) {
                        $work->$date_field = Lookup::clean_date($worksheet->$date_field);
                    }
                    $work->id = $worksheet->id;
                    $work->save();
                }
                $offset_value += self::$limit;
                echo "Completed {$key} worksheet {$offset_value} at " . date('d/m/Y h:i:s a', time()). "\n";
            }
        }
    }

    private static function set_batch_id($batch_id)
    {
        if($batch_id == floor($batch_id)) return $batch_id;
        return (floor($batch_id) + 0.5);
    }

    public static function get_enrollment_data($patient, $facility_id)
    {
    	$sample = SampleView::where('patient', $patient)
    				->where('facility_id', $facility_id)
    				->where('hei_validation', '>', 0)
    				->get()
    				->first();

    	if($sample){
    		return [
    			'hei_validation' => $sample->hei_validation,
    			'enrollment_ccc_no' => $sample->enrollment_ccc_no,
    			'enrollment_status' => $sample->enrollment_status,
    			'referredfromsite' => $sample->referredfromsite,
    			'otherreason' => $sample->otherreason,
    		];
    	}
    	else{
    		return false;
    	}
    }

    public static function assign_patient_statuses()
    {
    	print_r("==> Getting patient data at " . date('d/m/Y h:i:s a', time()). "\n");
    	ini_set("memory_limit", "-1");
    	$patients = \App\Patient::whereNull('hiv_status')->get();
        
        print_r("==> Started assigning patients` statuses at " . date('d/m/Y h:i:s a', time()). "\n");
        foreach ($patients as $key => $patient) {
            $samples = Sample::select('samples.id','patient_id','parentid','samples.result as result_id','results.name as result','datetested')->join('results', 'results.id','=','samples.result')
				->where('patient_id', '=', $patient->id)->orderBy('datetested','asc')->get();
            if ($samples->count() == 1){
            	$sample = $samples->first();
	            if ($sample->result < 3) {
	                $patient->hiv_status = $sample->result;
	                $patient->save();
	                // print_r("\tPatient $patient->patient save completed at " . date('d/m/Y h:i:s a', time()). "\n");
                }
            } else {
            	$data = [];
            	foreach ($samples as $key => $sample) {
                    $data[] = ['id'=>$sample->id,'patient_id'=>$sample->patient_id,'result_id'=>$sample->result_id,'result'=>$sample->result,'datetested'=>$sample->datetested];
                }
                if (!empty($data)) {
	                $length = sizeof($data)-1;
	                $arr = $data[$length];
	                if ($arr['result_id'] > 2) {
	                	$length -= $length;
	                	$arr = $data[$length];
	                	$status = $arr['result_id'];
	                } else {
	                	$status = $arr['result_id'];
	                }
	                $patient->hiv_status = $status;
	                $patient->save();
	                // print_r("\tPatient $patient->patient save completed at " . date('d/m/Y h:i:s a', time()). "\n");
	            }
            }
            // break;
        }
        // dd($data);
        return "==> Completed assigning patients` statuses at " . date('d/m/Y h:i:s a', time()). "\n";
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\SampleView;
use App\ViralsampleView;
use App\Batch;
use App\Viralbatch;
use App\Lookup;
use DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (auth()->user()->user_type_id == 1)
            return redirect('users');
            // return view('dashboard.home')->with('pageTitle', 'Dashboard');
        if (auth()->user()->user_type_id == 8) {
            $batch = session('batcheLoggedInWith');
            if (isset($batch['eid'])) {
                $batchID = $batch['eid'];
                $data = Lookup::get_eid_lookups();
                $batch = Batch::where('original_batch_id', '=', $batchID)->where('facility_id', '=', auth()->user()->facility_id)->first();
                $batch = $batch->load(['sample.patient.mother','view_facility', 'receiver', 'creator.facility']);
                $data = (object) $data;
                // dd($batch);
                return view('tables.batch_details', compact('data','batch'))->with('pageTitle', "EID Batch :: $batchID");
            } else {
                $batchID = $batch['vl'];
                $data = Lookup::get_viral_lookups();
                $batch = Viralbatch::where('original_batch_id', '=', $batchID)->where('facility_id', '=', auth()->user()->facility_id)->first();
                $batch = $batch->load(['sample.patient','view_facility', 'receiver', 'creator.facility']);
                $data = (object) $data;
                // dd($batch);
                return view('tables.viralbatch_details', compact('data','batch'))->with('pageTitle', "VIRAL LOAD Batch :: $batchID");
            }
        }

        if(auth()->user()->user_type_id == 9)
            return redirect('reports/support');
        
        return redirect('reports/EID');
    }
}
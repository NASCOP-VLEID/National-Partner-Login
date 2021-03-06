<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\User;
use App\Batch;
use App\Viralbatch;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    // protected function redirectTo () {
    //     $user = Auth::user();
    //     dd($user);
    // }
    

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function fac_login()
    {
        return view('auth.fac-login', ['login_error' => session()->pull('login_error')]);
    }


    public function facility_login(Request $request)
    {
        $facility_id = $request->input('facility_id');
        $batch_no = $request->input('batch_no');

        $batch = Batch::where(['original_batch_id' => $batch_no, 'facility_id' => $facility_id])->first();
        
        if($batch){
            if($batch->outdated()) return $this->failed_facility_login(); 
            $user = User::where(['facility_id' => $facility_id, 'user_type_id' => 8])->first();
            
            if($user){
                session(['batcheLoggedInWith'=>['eid'=>$batch_no]]);
                Auth::login($user);
                return redirect('/home');
            }
        }

        $batch = Viralbatch::where(['original_batch_id' => $batch_no, 'facility_id' => $facility_id])->get()->first();

        if($batch){
            if($batch->outdated()) return $this->failed_facility_login(); 
            $user = User::where(['facility_id' => $facility_id, 'user_type_id' => 8])->first();

            if($user){
                session(['batcheLoggedInWith'=>['vl'=>$batch_no]]);
                Auth::login($user);
                return redirect('/home');
            }
        }
        return $this->failed_facility_login(); 
    }

    public function failed_facility_login()
    {
        session(['login_error' => 'There was no batch for that facility']);
        return redirect('/login/facility');
    }
}


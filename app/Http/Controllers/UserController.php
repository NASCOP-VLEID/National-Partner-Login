<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserType;
use App\User;

use DB;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     **/
    public function index()
    {
        $columns = $this->_columnBuilder(['#','Full Names','Email Address','Account Type','Last Access','Action']);
        $row = "";
        $newUsers = [];

        $users = User::select('users.*','user_types.user_type')->join('user_types', 'user_types.id', '=', 'users.user_type_id')->where('users.user_type_id', '<>', 8);
        if (auth()->user()->user_type_id == 4) {
            $users = $users->where('user_type_id', '=', auth()->user()->user_type_id)->where('level', '=', auth()->user()->level);

            $subusers = User::selectRaw('distinct users.id, users.*,user_types.user_type')
                                ->join('user_types', 'user_types.id', '=', 'users.user_type_id')
                                ->join('view_facilitys', 'view_facilitys.subcounty_id', '=', 'users.level')
                                ->where('view_facilitys.county_id', '=', auth()->user()->level)->get();
        }
        
        $users = $users->get();
        foreach ($users as $key => $value) {
            $newUsers[] = (object)[
                            "id" => $value->id, "user_type_id" => $value->user_type_id,"surname" => $value->surname,
                            "oname" => $value->oname, "email" => $value->email, "level" => $value->level, "telephone" => $value->telephone,
                            "deleted_at" => $value->deleted_at, "created_at" => $value->created_at, "updated_at" => $value->updated_at,
                            "user_type" => $value->user_type
                        ];
        }
        if (auth()->user()->user_type_id == 4) {
            foreach ($subusers as $key => $value) {
                $newUsers[] = (object)[
                                "id" => $value->id, "user_type_id" => $value->user_type_id,"surname" => $value->surname,
                                "oname" => $value->oname, "email" => $value->email, "level" => $value->level, "telephone" => $value->telephone,
                                "deleted_at" => $value->deleted_at, "created_at" => $value->created_at, "updated_at" => $value->updated_at,
                                "user_type" => $value->user_type
                            ];
            }
        }

        $users = (object) $newUsers;

        foreach ($users as $key => $value) {
            $id = md5($value->id);
            $passreset = url("user/passwordReset/$id");
            $statusChange = url("user/status/$id");
            $delete = url("user/delete/$id");
            $row .= '<tr>';
            $row .= '<td>'.($key+1).'</td>';
            $row .= '<td>'.$value->surname.' '.$value->oname.'</td>';
            $row .= '<td>'.$value->email.'</td>';
            $row .= '<td>'.$value->user_type.'</td>';
            $row .= '<td>'.$value->created_at.'</td>';
            $row .= '<td><a href="'.$passreset.'">Reset Password</a> | <a href="'.$statusChange.'">Deactivate</a> | <a href="'.$delete.'">Delete</a></td>';
            $row .= '</tr>';
        }

        return view('tables.display', compact('columns','row'))->with('pageTitle', 'Users');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $usertype = auth()->user()->user_type_id;
        $accounts = UserType::whereNull('deleted_at')->where('id', '<>', 8)
                                ->when($usertype,function($query) use ($usertype) {
                                    return $query->where('id','=',4)->orWhere('id','=',5);
                                })->get();
        if (auth()->user()->user_type_id == 1) {
            $level = DB::table('partners')->get();
        } else if (auth()->user()->user_type_id == 4) {
            $level = DB::table('districts')->where('county', '=', auth()->user()->level)->get();
        }


        return view('forms.users', compact('accounts','level'))->with('pageTitle', 'Add User');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (User::where('email', '=', $request->email)->count() > 0) {
            session(['toast_message'=>'User already exists', 'toast_error'=>1]);
            return redirect()->route('user.add');
        } else {
            $user = new User;
            
            $user->surname = $request->surname;
            $user->oname = $request->oname;
            $user->email = $request->email;
            $user->user_type_id = $request->user_type;
            $user->lab_id = 0;
            $user->password = bcrypt($request->password);
            if (isset($request->partner)) {
                $user->level = $request->level;
            } else {
                if ($request->user_type == auth()->user()->user_type_id) {
                    $user->level = auth()->user()->level;
                } else {
                    $user->level = $request->level;
                }
            }
            $user->telephone = $request->telephone;
            $user->save();
            
            session(['toast_message'=>'User created succesfully']);

            if ($request->submit_type == 'release')
                return redirect()->route('users');

            if ($request->submit_type == 'add')
                return redirect()->route('user.add');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = self::__unHashUser($id);
        if (!empty($user)) {
            $user->password = $request->password;
            $user->update();
            session(['toast_message'=>'User password succesfully updated']);
        } else {
            session(['toast_message'=>'User password succesfully updated','toast_error'=>1]);
        }
        if (isset($request->user)) {
            return back();
        } else {
            return redirect()->route('users');
        }      
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function passwordreset($id = null)
    {
        $user = null;
        if (null == $id) {
            $user = 'personal';
            return view('forms.passwordReset', compact('user'))->with('pageTitle', 'Password Reset');
        } else {
            $user = self::__unHashUser($id);
            return view('forms.passwordReset', compact('user'))->with('pageTitle', 'Password Reset');
        }
    }

    private static function __unHashUser($hashed){
        $user = [];
        foreach (User::get() as $key => $value) {
            if ($hashed == md5($value->id)) {
                $user = $value;
                break;
            }
        }

        return $user;
    }
}

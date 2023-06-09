<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use function PHPUnit\Framework\returnValue;

class LoginController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where('username', $request->username)->first();
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
                    $auth = $request->user();
                    if($auth->user_flag != 'admin') {
                        switch ($auth->user_flag) {
                            case 'student':
                                $owner = Student::where('user_id', $auth->id)->first();
                                break;
                            case 'teacher':
                                $owner = Teacher::where('user_id', $auth->id)->first();
                                break;
                        }
                        $data['fullname'] = $owner->first_name . " " . $owner->middle_name . " " . $owner->last_name;
                    }
                    $data['user_flag'] = $auth->user_flag;
                    $data['token'] = $auth->createToken('LoginToken')->plainTextToken;
                    return response()->json(['data' => $data]);
                }
            } else {
                return response()->json([
                    'password' => ['Incorrect password.']
                ], 401);
            }
        } else {
            return response()->json([
                'username' => ['Username is incorrect or does not exist.']
            ], 401);
        }
    }
}

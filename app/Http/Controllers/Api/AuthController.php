<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\SendWelcomeEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\User;
use App\Jobs\SendWelcomeEmailJob;
use Illuminate\Support\Facades\Mail;
class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = validator($request->all(), [
            'name' => 'required|string|max:255',
            'email'=>'required|string|unique:users',
            'password'=>'required|string',
            'c_password' => 'required|same:password'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $user = new User([
                'name'  => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);
            $user->save();
            DB::commit();
            //Mail::to($request->email)->send(new SendWelcomeEmail());
            dispatch(new SendWelcomeEmailJob($request->email));
            return response()->json([
                'status' =>'success',
                'message' => 'Successfully created user! Please check your email.',
            ],201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $th->getMessage()], 500);
        }
    }
    public function login(Request $request)
    {
        $validator = validator($request->all(), [
            'email' => 'required|email:rfc,dns',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        try {
            $user = User::firstWhere(['email' => $request->email]);
            if ($user && password_verify($request->password, $user->password)) {
                $token = $user->createToken("API TOKEN")->plainTextToken;
                return response()->json([
                    "status"=> "success",
                    'accessToken' => $token,
                    'token_type' => 'Bearer',
                    'user' => [
                        'id'=> $user->id,
                        'name'=> $user->name,
                        'email'=> $user->email,
                    ]
                ]);
            }
            return response()->json(['status' => 'error', 'message' => "Invalid Credentials"], 401);
        } catch (\Throwable $th) {
            return response()->json(['status' => 'error', 'message' => $th->getMessage()], 500);
        }
    }
    public function user(Request $request)
    {
        return response()->json($request->user());
    }
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
        'message' => 'Successfully logged out'
        ]);

    }
}

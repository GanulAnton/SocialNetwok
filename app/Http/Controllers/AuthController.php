<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Console\EventListener\ErrorListener;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        //INSERT INTO users (username,email,password) VALUES ('request.username','request.email','request.password');
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);
        //SELECT EXISTS(SELECT * FROM oauth_personal_access_clients) AS exists;
        //SELECT * FROM oauth_personal_access_clients ORDER BY id DESC LIMIT 1
        //SELECT * FROM oauth_clients WHERE oauth_clients.id = oauth_personal_access_clients.client_id LIMIT 1
        //SELECT * FROM oauth_clients WHERE id = oauth_personal_access_clients.client_id LIMIT 1
        //INSERT INTO oauth_access_tokens (`id`, `user_id`, `client_id`, `scopes`, `revoked`, `created_at`, `updated_at`, `expires_at`) VALUES (...)
        $token = $user->createToken('LaravelAuthApp')->accessToken;
        $response = [
            'user' => UserResource::make($user),
            'token' => $token,
        ];
        return response($response);
    }

    public function login(LoginRequest $request)
    {
        //SELECT * FROM users WHERE email = 'request.email' LIMIT 1;
        $user = User::where('email', $request->email)->first();
        if(!$user || !Hash::check($request->password, $user->password)){
            return response([
                'message' => 'Bad creds'
            ]);
        }
        //SELECT EXISTS(SELECT * FROM oauth_personal_access_clients) AS exists;
        //SELECT * FROM oauth_personal_access_clients ORDER BY id DESC LIMIT 1;
        //SELECT * FROM oauth_clients WHERE oauth_clients.id = oauth_personal_access_clients.client_id LIMIT 1;
        //SELECT * FROM oauth_clients WHERE id = oauth_personal_access_clients.client_id LIMIT 1;
        //INSERT INTO oauth_access_tokens (`id`, `user_id`, `client_id`, `scopes`, `revoked`, `created_at`, `updated_at`, `expires_at`) VALUES (...);
        $token = $user->createToken('LaravelAuthApp')->accessToken;
        $response = [
            'user' => UserResource::make($user),
            'token' => $token,
        ];
        return response($response);
    }

    public function logout(Request $request)
    {
        //DELETE FROM `oauth_access_tokens` WHERE `id` = user.token;
        $request->user()->token()->delete();
        return [
            'message' => 'Logged out'
        ];
    }
}


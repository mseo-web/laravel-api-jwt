<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;


class UsersController extends Controller
{
    public function register(Request $request)
    {
         $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255', 
            'email' => 'required|string|email|max:255|unique:users', 
            'password' => 'required|string|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

         $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

         $token = JWTAuth::fromUser($user);

         return response()->json([
            'message' => 'Пользователь успешно зарегистрирован',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/',
        ]);

        $user = User::where('email', $request->email)->first();

    
        if (!$user) {
            return response()->json(['error' => 'Неверный адрес электронной почты'], 401);
        } elseif (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Неправильный пароль'], 401);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Вход успешный',
            'token' => $token,
            'user' => $user->makeHidden(['password', 'created_at', 'updated_at']),  // Hide sensitive fields
        ]);
    }

    public function dashboard(Request $request)
    {
         try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'Срок действия токена истек'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Токен недействителен'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Токен не предоставлен'], 401);
        }
    
         return response()->json([
            'user' => $user,
            'message' => 'Добро пожаловать в вашу панель управления'
        ]);
    }

    public function logout(Request $request)
    {
        try {
             $token = JWTAuth::getToken();
    
            if (!$token) {
                return response()->json(['error' => 'Токен не предоставлен'], 401);
            }
    
            JWTAuth::invalidate($token);
    
            return response()->json(['message' => 'Успешно вышел из системы']);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Не удалось выйти'], 500);
        }
    }
}

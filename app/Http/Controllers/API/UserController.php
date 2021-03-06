<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Rules\Password;

class UserController extends Controller
{
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:255', 'unique:users'],
                'email' => ['required', 'string', 'max:255', 'unique:users', 'email'],
                'phone_number' => ['nullable', 'string', 'max:15'],
                'password' => ['required', 'string', new Password]
            ]);

            User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'password' => Hash::make($request->password),
            ]);

            $user = User::where('email', $request->email)->first();

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'User Registered');
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error
            ], 'Authentication Failed', 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'email|required',
                'password' => 'required'
            ]);

            $credentials = request(['email', 'password']);
            if (!Auth::attempt($credentials)) {
                return ResponseFormatter::error([
                    'message' => 'Unauthorized'
                ], 'Authentication Failed', 500);
            }

            $user = User::where('email', $request->email)->first();

            if (!Hash::check($request->password, $user->password, [])) {
                throw new \Exception('Invalid Credentials');
            }

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'Authenticated');
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error
            ], 'Authentication Failed', 500);
        }
    }

    public function fetch(Request $request)
    {
        return ResponseFormatter::success(
            $request->user(),
            'Data profile user berhasil diambil'
        );
    }

    public function updateProfile(Request $request)
    {
        try {
            $data = $request->all();

            $request->validate([
                'name' => 'required',
                'username' => 'required|unique:users',
                'email' => 'required|unique:users',
                'phone_number' => 'nullable|max:15',
                'password' => 'required',
            ]);

            $user = Auth::user();
            $user->update([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'phone_number' => $data['phone_number'],
                'password' => Hash::make($data['password'])
            ]);

            return ResponseFormatter::success($user, 'Profile Updated');
        } catch (Exception $error) {
            return ResponseFormatter::error(
                null,
                ['message' => 'Something went wrong', 'error' => $error],
                500
            );
        }
    }

    public function logout(Request $request) 
    {
        try {
            $token = $request->user()->currentAccessToken()->delete();

            return ResponseFormatter::success($token, 'Token Revoked');
        } catch (Exception $error) {
            return ResponseFormatter::error(
                null,
                [
                    'message' => 'Something went wrong',
                    'error' => $error
                ],
                500
            );
        }
    }
}

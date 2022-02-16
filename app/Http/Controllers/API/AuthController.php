<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use JWTAuth;

/**
 * Class AuthController
 * @package App\Http\Controllers\API
 */
class AuthController extends Controller
{
    /**
     * User Registration
     * @param Request $request
     * name: required
     * email: required|email|unique
     * phone: required
     * password: required|min:8
     *
     * @return \Illuminate\Http\JsonResponse
     * success: if true,
     * data: [
     *  'auth' : authentication token,
     *  'user' : Registered user data
     * ]
     */
    public function register(Request $request)
    {
        try {
            $valdation = Validator::make(
                $request->only(['name', 'phone', 'email', 'password']),
                [
                    // validation rules
                    'name' => 'required|min:6',
                    'email' => 'required|email|unique:users,email',
                    'password' => 'required|min:8',
                    'phone' => 'required',
                ],
                [
                    // validation error messages
                    'name.required' => 'Please enter name',
                    'name.min' => 'Name should have at least 6 letters',
                    'email.required' => 'Please enter email',
                    'email.email' => 'Please enter email in appropriate format',
                    'email.unique' => 'Email you have entered already exists. Please try another email',
                    'password.required' => 'Please enter password',
                    'password.min' => 'Your password should have at least 8 letters',
                    'phone.required' => 'Please specify phone'
                ]);

            if ($valdation->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $valdation->messages()
                ]);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password)
            ]);

            $credentials = $request->only('email', 'password');
            $authToken = JWTAuth::attempt($credentials);

            return response()->json([
                'success' => true,
                'data' => [
                    'auth' => $authToken,
                    'user' => $user,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * User Login
     * @param Request $request
     * email: required|email
     * password: required|min:8
     *
     * @return \Illuminate\Http\JsonResponse
     * success: if true,
     *  'auth' : authentication token,
     *  'data' : Logged in user data
     */
    public function login(Request $request)
    {
        try {
            $valdation = Validator::make(
                $request->only(['email', 'password']),
                [
                    // validation rules
                    'email' => 'required|email',
                    'password' => 'required|min:8',
                ],
                [
                    // validation error messages
                    'email.required' => 'Please enter email',
                    'email.email' => 'Please enter email in appropriate format',
                    'password.required' => 'Please enter password',
                    'password.min' => 'Your password should have at least 8 letters',
                ]);

            if ($valdation->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $valdation->messages()
                ]);
            }

            $credentials = $request->only('email', 'password');
            $authToken = JWTAuth::attempt($credentials);

            if (isset($authToken) && $authToken !== false) {
                return response()->json([
                    'success' => true,
                    'auth' => $authToken,
                    'data' => Auth::user()
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials for login.',
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * User Logout API
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * success: true if logged out
     */
    public function logout(Request $request)
    {
        if (JWTAuth::invalidate(JWTAuth::getToken())) {
            Auth::logout();
            return response()->json([
                'success' => true,
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout user. Try again.'
            ], 500);
        }
    }
}

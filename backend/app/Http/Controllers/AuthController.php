<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\RegisterSuccessMail;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        // GỬI EMAIL
        Mail::to($user->email)->send(new RegisterSuccessMail($user));

        return response()->json([
            'message' => 'Đăng ký thành công! Đã gửi email.',
            'user' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Email hoặc mật khẩu không đúng'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Đăng nhập thành công',
            'token' => $token, // 🔥 QUAN TRỌNG
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Đăng xuất thành công',
        ]);
    }

    public function redirectGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogle()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::where('email', $googleUser->getEmail())->first();

        if (!$user) {
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'password' => bcrypt(Str::random(16)),
                'role' => 'user'
            ]);
        }


        $token = $user->createToken('auth_token')->plainTextToken;


        return redirect("http://localhost:5173/login-success?token=$token");
    }

    public function redirectFacebook()
    {
        return Socialite::driver('facebook')
            ->stateless()
            ->scopes(['email'])
            ->redirect();
    }

    public function handleFacebook()
    {
        $facebookUser = Socialite::driver('facebook')->stateless()->user();

        $email = $facebookUser->getEmail();

        $user = User::where('facebook_id', $facebookUser->getId())->first();

        if (!$user && $email) {
            $user = User::where('email', $email)->first();
        }

        if (!$user) {
            $user = User::create([
                'name' => $facebookUser->getName() ?: 'Facebook User',
                'email' => $email ?: 'fb_' . $facebookUser->getId() . '@noemail.local',
                'facebook_id' => $facebookUser->getId(),
                'avatar' => $facebookUser->getAvatar(),
                'password' => bcrypt(Str::random(16)),
                'role' => 'user',
            ]);
        } else {
            if (!$user->facebook_id) {
                $user->facebook_id = $facebookUser->getId();
            }

            if (!$user->avatar && $facebookUser->getAvatar()) {
                $user->avatar = $facebookUser->getAvatar();
            }

            $user->save();
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return redirect("http://localhost:5173/login-success?token=$token");
    }
}

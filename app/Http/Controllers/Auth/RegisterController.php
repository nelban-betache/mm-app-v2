<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = '/';

    public function __construct()
    {
        $this->middleware('guest');
    }

    protected function validator(array $data)
    {
        Log::info('Validating user data:', $data);

        return Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'contact_no' => ['nullable', 'numeric', 'regex:/^\d{10,11}$/'], // Validate numeric with specific regex
            'address' => ['nullable', 'string', 'max:255'],
            'birthdate' => ['required', 'date', 'before:today'],
            'role' => ['required', 'string', 'in:Health Worker,Feminine'],
            'menstruation_status' => ['required_if:role,Feminine', 'boolean'],
        ], [
            'contact_no.regex' => 'The contact number must be 10 or 11 digits.',
            'unique' => 'The :attribute field has already been taken.'
        ]);
    }

    protected function create(array $data)
    {
        try {
            Log::info('Creating user:', $data);

            $role = $data['role'];
            Log::info('Role:', ['role' => $role]);

            $defaultMenstruationStatus = $role === 'Feminine' ? $data['menstruation_status'] : null;
            $userRoleId = $role === 'Health Worker' ? 3 : 2; // 3 for Health Worker, 2 for User

            Log::info('Default Menstruation Status:', ['status' => $defaultMenstruationStatus]);
            Log::info('User Role ID:', ['user_role_id' => $userRoleId]);

            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'email' => $data['email'],
                'contact_no' => $data['contact_no'],
                'address' => $data['address'] ?? null,
                'birthdate' => date('Y-m-d', strtotime($data['birthdate'])),
                'password' => Hash::make($data['password']),
                'menstruation_status' => $defaultMenstruationStatus,
                'user_role_id' => $userRoleId,
                'is_active' => false,
            ]);

            Log::info('User created successfully:', ['user_id' => $user->id]);

            return $this->registered($user);
        } catch (\Exception $e) {
            Log::error('Error during registration:', ['message' => $e->getMessage()]);
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    protected function registered($user)
    {
        Session::flush();
        Auth::logout();

        Session::flash('post-register', 'Registration completed! Please wait for the admin to verify your account.');

        Log::info('Redirecting to login page after registration.');

        return redirect()->route('login');
    }
}

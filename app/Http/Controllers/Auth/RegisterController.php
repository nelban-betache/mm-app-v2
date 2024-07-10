<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

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
        return Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'menstruation_status' => ['required_if:role,Health Worker', 'nullable', 'boolean'],
            'birthdate' => ['required', 'date', 'before:today'],
            'contact_no' => ['required', 'numeric', 'regex:/^\d{10,11}$/', 'unique:users,contact_no'],
        ], [
            'contact_no.regex' => 'The contact number must be 10 or 11 digits.',
            'contact_no.unique' => 'The contact number has already been taken.',
            'unique' => 'The :attribute field has already been taken.'
        ]);
    }

    protected function create(array $data)
    {
        try {
            $role = $data['role'] ?? 'User'; // Adjust based on your form field name
            
            $roleId = ($role === 'Health Worker') ? 3 : 2; // Default role ID for User
            
            // Adjusting menstruation_status for Health Workers
            $menstruationStatus = ($role === 'Health Worker') ? null : $data['menstruation_status'];

            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'email' => $data['email'],
                'contact_no' => $data['contact_no'],
                'address' => $data['address'] ?? null,
                'birthdate' => date('Y-m-d', strtotime($data['birthdate'])),
                'password' => Hash::make($data['password']),
                'menstruation_status' => $menstruationStatus,
                'user_role_id' => $roleId,
                'is_active' => false, // inactive by default, need to be verified by admin
            ]);

            // Handle successful registration
            $this->registered();

            return $user;
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    protected function registered()
    {
        Session::flush();
        Auth::logout();

        Session::flash('post-register', 'Registration completed! Please wait for the admin to verify your account.');

        return redirect()->route('login.page');
    }
}

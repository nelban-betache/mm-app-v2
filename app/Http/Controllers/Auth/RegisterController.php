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
            'menstruation_status' => ['nullable', 'boolean'], // Adjust as per your requirement
            'birthdate' => ['required', 'date', 'before:today'],
            'contact_no' => ['required', 'numeric', 'regex:/^\d{10,11}$/', 'unique:users,contact_no'],
            'role' => ['required', 'string', 'in:User,Health Worker'], // Ensure role is validated
        ], [
            'contact_no.regex' => 'The contact number must be 10 or 11 digits.',
            'contact_no.unique' => 'The contact number has already been taken.',
            'unique' => 'The :attribute field has already been taken.',
            'role.in' => 'Invalid role selected.', // Custom error message for role validation
        ]);
    }

    protected function create(array $data)
    {
        try {
            // Determine role ID based on selected role
            $roleId = ($data['role'] === 'Health Worker') ? 3 : 2;

            // Adjust menstruation_status for Health Workers if needed
            $menstruationStatus = ($roleId === 3) ? null : $data['menstruation_status'];

            // Log/debug statement to check values
            \Log::info("Role ID: $roleId, Menstruation Status: $menstruationStatus");

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
            // Log the error
            \Log::error("Registration failed: " . $e->getMessage());

            // Redirect back with error message
            return redirect()->back()->withInput()->withErrors(['error' => 'Registration failed. Please try again later.']);
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

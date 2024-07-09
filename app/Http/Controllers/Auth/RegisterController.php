<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

use App\Models\User;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'contact_no' => ['nullable', 'numeric', 'regex:/^\d{10,11}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'birthdate' => ['required', 'date', 'before:today'],
            'role' => ['required', 'string', 'in:Health Worker,Feminine'],
            'menstruation_status' => ['required_if:role,Feminine', 'boolean'],
        ], [
            'contact_no.regex' => 'The contact number must be 10 or 11 digits.',
            'unique' => 'The :attribute field has already been taken.'
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        try {
            $role = $data['role'];
    
            // Determine default values based on role
            $defaultMenstruationStatus = $role === 'Feminine' ? $data['menstruation_status'] : null;
            $userRoleId = $role === 'Health Worker' ? 3 : 2; // 3 for Health Worker, 2 for User
    
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
                'is_active' => false, // inactive by default, needs to be verified by admin
            ]);
    
            $this->registered();
    
            return $user;
        } catch (\Exception $e) {
            // Handle any exceptions during registration
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Handle a registration request for the application.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    protected function registered()
    {
        Session::flush(); // Flush any existing session data
        Auth::logout(); // Logout the user after registration

        // Flash a message to the user
        Session::flash('post-register', 'Registration completed! Please wait for the admin to verify your account.');

        // Redirect the user to the login page
        return redirect()->route('login.page');
    }
}

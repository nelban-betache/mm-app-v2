<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Validation\Rule;

use App\Models\User;

use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use Session;

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
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['Health Worker', 'Feminine'])],
            'menstruation_status' => ['nullable', 'in:0,1', 'required_if:role,Feminine'], // Ensure menstruation_status is required for Feminine role
            'birthdate' => ['required', 'date', 'before:today'],
            'contact_no' => ['required', 'numeric', 'digits_between:10,11', 'unique:users,contact_no'],
        ], [
            'contact_no.regex' => 'The contact number must be 10 or 11 digits.',
            'contact_no.unique' => 'The contact number has already been taken.',
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
        $role = $data['role'] === 'Health Worker' ? 3 : 2;
    
        return User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'email' => $data['email'],
            'contact_no' => $data['contact_no'],
            'address' => $data['address'] ?? null,
            'birthdate' => $data['birthdate'] ?? null,
            'password' => Hash::make($data['password']),
            'user_role_id' => $role,
            'menstruation_status' => $role === 2 ? $data['menstruation_status'] : null, // Set menstruation_status only if role is Feminine
        ]);
    }
    
    

    protected function registered() {
        Session::flush();
        Auth::logout();

        Session::flash('post-register', 'Registration completed! Please wait for the admin to verify your account.');

        return redirect()->route('login.page');
    }
}

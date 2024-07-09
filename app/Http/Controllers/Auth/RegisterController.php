<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Session;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default, this controller uses a trait to
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
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'menstruation_status' => ['required_if:role,Feminine', 'boolean'],
            'birthdate' => ['required', 'date', 'before:today'],
            'contact_no' => ['numeric', 'nullable', 'regex:/^\d{10,11}$/', 'unique:users,contact_no', 'required_if:email,null'],
            'role' => ['required', 'string', 'in:Feminine,Health Worker'],
        ], [
            'contact_no.regex' => 'The contact number must be 10 or 11 digits.',
            'contact_no.unique' => 'The contact number has already been taken.',
            'unique' => 'The :attribute field has already been taken.',
            'role.in' => 'Please select a valid role.',
            'menstruation_status.required_if' => 'The menstruation status field is required for Feminine role.',
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
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'email' => $data['email'],
                'contact_no' => $data['contact_no'],
                'address' => $data['address'] ?? null,
                'birthdate' => date('Y-m-d', strtotime($data['birthdate'])),
                'password' => Hash::make($data['password']),
                'menstruation_status' => $data['menstruation_status'] ?? null,
                'user_role_id' => 2, // 2 = default for user only role
                'is_active' => false, // inactive by default, need to be verified by admin
            ]);

            $this->registered();

            return $user;
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        Auth::guard()->login($this->create($request->all()));

        return $this->registered($request, Auth::user())
                        ?: redirect($this->redirectPath());
    }

    protected function registered(Request $request, $user)
    {
        Session::flush();
        Auth::logout();

        Session::flash('post-register', 'Registration completed! Please wait for the admin to verify your account.');

        return redirect()->route('login.page');
    }
}

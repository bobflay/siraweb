<?php
namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Batch;
use Log;
use Validator;
use Mail;
use App\Mail\NewClientRegistration;
use App\Mail\NewClientRegistrationAdmin;
use App\Models\OTP;
use App\Models\Notification;

use App\Mail\SendOTP;
use Carbon\Carbon;


class AuthController extends Controller
{
    public function login(Request $request)
    {

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {

            $user = Auth::user();
            $user->load(['roles', 'basesCommerciales', 'zones']);

            $accessToken = $user->createToken('authToken')->plainTextToken;
            $this->requestOTP($request);
            return response()->json([
                'status'=>true,
                'message'=>'User Logged in successfully',
                'access_token' => $accessToken,
                'user'=>$user
            ], 201);


        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function register(Request $request)
    {
        // Validation rules
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:15|unique:users',
            'dob' => 'required|date',
            'password' => 'required|string|min:8',
        ]);
    
        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if(!is_null($request->get('photo')))
        {
            // Decode the base64 photo
            $photo = $request->input('photo');
            $photo = str_replace('data:image/jpeg;base64,', '', $photo);
            $photo = str_replace(' ', '+', $photo);
            $filename = 'photos/' . uniqid() . '.jpg';
            \Storage::disk('public')->put($filename, base64_decode($photo));
            $photoPath = $filename;
        }else{
            $photoPath = null;
        }


        // Create the user
        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'dob' => $request->dob,
            'password' => bcrypt($request->password),
            'photo' => $photoPath, // Save the photo path
        ]);
    
        // Save the user
        $user->save();
    
        // Issue token
        $accessToken = $user->createToken('authToken')->plainTextToken;
    

        Mail::to($user->email)->send(new NewClientRegistration($user));
        Mail::to('bob.fleifel@gmail.com')->send(new NewClientRegistrationAdmin($user));


        // Return success response with token
        return response()->json([
            'status'=>true,
            'message'=>'User Registered successfully',
            'access_token' => $accessToken,
            'user'=>$user
        ], 201);
    }


    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Successfully logged out'
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $user->load(['roles', 'basesCommerciales', 'zones']);

        return response()->json([
            'status' => true,
            'user' => $user
        ]);
    }


    public function requestOTP(Request $request)
    {

        // $request->validate([
        //     'email' => 'required|email',
        // ]);

        // $otp = $this->generateOTP();
        // OTP::create([
        //     'email' => $request->email,
        //     'otp' => $otp,
        // ]);

        // Notification::create([
        //     'title'=>'OTP',
        //     'message'=>"Your OTP code is: $otp",
        //     'seen' => true,
        //     'user_id'=>auth()->user()->id
        // ]);

        // Mail::to($request->email)->send(new SendOTP($otp));

        // return response()->json([
        //     'status'=>true,
        //     'message' => 'OTP sent to your email',
        //     'data'=>null
        // ]);
    }

    public function verifyOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required',
        ]);

        $otpRecord = OTP::where('email', $request->email)->where('otp', $request->otp)->first();

        if (!$otpRecord) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP',
                'data' => null
            ], 400);
        }

        // Optionally, you can check the expiration time of the OTP
        $otpCreationTime = Carbon::parse($otpRecord->created_at);
        if (Carbon::now()->diffInMinutes($otpCreationTime) > 10) {
            return response()->json([
                'status' => false,
                'message' => 'OTP expired',
                'data' => null
            ], 400);
        }

        // Successful OTP verification
        // Here you can create a token for the user or perform any other authentication logic

        return response()->json([
            'status'=> true,
            'message' => 'OTP verified successfully',
            'data' => null
        ]);
    }

    public function generateOTP($length = 4) {
        $digits = '0123456789';
        $otp = '';
        for ($i = 0; $i < $length; $i++) {
            $otp .= $digits[rand(0, strlen($digits) - 1)];
        }
        return $otp;
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Current password is incorrect'
            ], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Password changed successfully'
        ]);
    }


}

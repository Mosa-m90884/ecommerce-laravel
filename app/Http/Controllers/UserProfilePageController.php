<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserProfilePageController extends Controller
{
    public function index()
    {
        return view('profile.dashboard');
    }
    public function editInfo()
    {
        return view('profile.user-info-edit');
    }
    public function avatar(Request $request){
        $request->validate([
            'avatar'=>'required|image|max:10000|mimes:jpg,jpeg,png'
        ]);

        $file = $request->file('avatar');
        $path = 'public/users/'.date('FY');

        if(auth()->user()->avatar!='users/default.png'){
            if(file_exists(storage_path('app/public/'.auth()->user()->avatar)))
            unlink(storage_path('app/public/'.auth()->user()->avatar));
        }

        $store_path  = Storage::putFile($path, $file);
        $store_path_without_public=str_replace('public/','',$store_path);

        User::where('id',auth()->user()->id)->update([
            'avatar'=> $store_path_without_public
        ]);

        return back()->with('success','Uploaded successfully');

    }

    protected function infoChange(Request $request){
        $request->validate([
            'name' => ['nullable','string', 'max:255'],
            'email' => ['nullable','string', 'email', 'max:255', 'unique:users'],
            'password' => ['nullable','string', 'min:8'],
        ]);

        if($request->password){
            $pass=Hash::make($request->password);
            User::where('id',auth()->user()->id)->update([
                'password'=>$pass
            ]);
        }
        if($request->email){
            User::where('id',auth()->user()->id)->update([
                'email'=>$request->email
            ]);
        }
        if($request->name){
            User::where('id',auth()->user()->id)->update([
                'name'=>$request->name
            ]);
        }

        return back()->with('success', 'Info updated');

    }

    public function avatar_remove(){
        if(auth()->user()->avatar!='users/default.png'){
            if(file_exists(storage_path('app/public/'.auth()->user()->avatar)))
            unlink(storage_path('app/public/'.auth()->user()->avatar));
        }

        User::where('id',auth()->user()->id)->update([
            'avatar'=>'users/default.png'
        ]);

        notify()->success('Avatar removed');
        return back();
    }
    public function register(Request $request){

        $validator = Validator::make($request->all(),[
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users|max:255',
            'password' => 'required|string|max:255|min:6|confirmed'
        ]);

        if($validator->fails()){
            return response(['errors' => $validator->errors()], 422);
        }

        $user = new User();
        $user->first_name = $request->firstName;
        $user->last_name = $request->lastName;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->save();

        return $this->getResponse($user);
    }
    public function login(Request $request){

        $validator = Validator::make($request->all(),[
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|max:255'
        ]);

        if($validator->fails()){
            return response(['errors' => $validator->errors()], 422);
        }

        $credentials = \request(['email', 'password']);

        if(Auth::attempt($credentials)){
            $user = $request->user();
            return  $this->getResponse($user);
        }

    }

    public function logout(Request $request){
        $request->user()->token()->revoke();
        return response('Successfully logged out',200);
    }

    public function user(Request $request){
        return $request->user();
    }

    private function getResponse(User $user){

        $tokenResult =   $user->createToken("Personal Access Token");
        $token = $tokenResult->token;
        $token->expires_at = Carbon::now()->addWeeks(1);
        $token->save();
        if (Auth::user()) {
            $user2= Auth::user();
        }

     //   return response(['prod'=>Product::all(),'kkk'=>'nnn'],'222');
        return  response([
            'accessToken' => $tokenResult->accessToken,
            'user' =>$user2,
            'tokenType' => "Bearer",
            'expiresAt' => Carbon::parse($token->expires_at)->toDateTimeString()
        ],200);
    }
    public function authFailed(){
        return response('unauthenticated', 401);
    }
}

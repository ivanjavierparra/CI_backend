<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required'
        ]);
        
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['errors' => ['result' => 'Unauthorized']], 401);
        }

        return $this->respondWithToken($token);
    }


    /**
     * Registar nuevo usuario.
     */
    public function register(Request $request) {

        $request->validate([
            'name' => 'required',
            'lastname' => 'required',
            'email' => 'required',
            'password' => 'required'
        ]);
        
        User::create([
            
            'name' => request('name'),

            'lastname' => request('lastname'),

            'email' => request('email'),

            'password' => Hash::make(request('password')),
        ]);

        return $this->login(request());
    }


    /**
     * Editar datos del usuario.
     */
    public function edite(Request $request) {

         $request->validate([
             'name' => 'required',
             'lastname' => 'required',
             'birthdate' => 'required',
             'city' => 'required',
             'gender' => 'required',
             'numero_renapa' => 'required',
             //'avatar' => 'required'
             //'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
         ]);

        auth()->user()->name = $request['name'];
        auth()->user()->lastname = $request['lastname'];
        auth()->user()->birthdate = $request['birthdate'];
        auth()->user()->city = $request['city'];
        auth()->user()->gender = $request['gender'];
        auth()->user()->numero_renapa = $request['numero_renapa'];
        //auth()->user()->avatar = $request['avatar'];
        auth()->user()->save();

        return response('edite', Response::HTTP_ACCEPTED);
    }


    /**
     * Cambiar el password del usuario.
     * 
     * @param String password_actual
     * @param String password_nuevo
     * 
     * @return String message
     */
    public function password(Request $request) {

        $password_actual = $request['password_actual'];
        $password_nuevo = $request['password_nuevo'];
        
        $hash_viejo = auth()->user()->password;
        if( Hash::check($password_actual, $hash_viejo) ) {
            auth()->user()->password = Hash::make($password_nuevo);
            auth()->user()->save();
            return response('password', Response::HTTP_ACCEPTED);
        }
        else {
            return response()->json(['message' => 'contraseña incorrecta.']); 
        }        
    }


    /**
     * https://stackoverflow.com/questions/58064924/react-laravel-file-showing-in-state-but-not-in-controller
     * https://appdividend.com/2018/03/23/react-js-laravel-file-upload-tutorial/
     * 
     * Permite cambiar la foto de perfil del usuario.
     */
    public function avatar(Request $request) {
        
        if ($request->hasFile('profile_pic')) {    
            
            // Obtengo la imagen
            $image = $request->file('profile_pic');

            // Creo un nombre para la imagen: timestamp + nombre original de la imagen.
            $name = time().'-'.$request->file('profile_pic')->getClientOriginalName();

            // Defino la carpeta donde se guardará la imagen
            $destinationPath = public_path("\img");

            // Guardo la imagen en la carpeta destino
            $image->move($destinationPath, $name);

            auth()->user()->avatar = $name;
            auth()->user()->save();
        
            return response()->json('File successfully added');
        }
    }


    /**
     * Update the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        auth()->user()->update($request->all());
        return response('update', Response::HTTP_ACCEPTED);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'users' => auth()->user() // devuelvo el usuario actual
        ]);
    }
}

<?php

namespace App\Http\Controllers;


use \Exception, \Validator, \Response, \Mail;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\Request, DB;
use \Hash, \Config, Carbon\Carbon;
use App\Models\Usuario;

class ResetPasswordController extends Controller
{
	public function validarToken(Request $request){
		$input = $request->only('id','reset_token');
		$mensajes = [
            
            'required'      => "required"
        ];

        $reglas = [
			'id'        => 'required',
			'reset_token'        => 'required'
        ];

        $v = Validator::make($input, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
		}
		
		$usuario = Usuario::where('id',$input['id'])->where('reset_token',$input['reset_token'])->first();
		
		
		if(!$usuario){
			return Response::json(['error' => ["token"=>["invalid"]]], HttpResponse::HTTP_UNAUTHORIZED);
		} else {
			// Bloqueamos acciones de los usuarios que no están en el servidor que les corresponde
			if($usuario->servidor_id != env('SERVIDOR_ID')){
				return Response::json(['error' => ["token"=>["invalid"]]], HttpResponse::HTTP_UNAUTHORIZED);
			} else {
				return Response::json(['data' => "Token válido"], 200);
			}
			
		}
	}

	public function obtenerPreguntaSecreta(Request $request,  $id){
		
		
		
		$usuario = Usuario::find($id);
		try {
			if(!$usuario){
				return Response::json(['error' => ["usuario"=>["not-exist"]]], HttpResponse::HTTP_CONFLICT);
			} else {			
				// Bloqueamos acciones de los usuarios que no están en el servidor que les corresponde
				if($usuario->servidor_id != env('SERVIDOR_ID')){
					return Response::json(['error' => ["pregunta"=>["not-exist"]]], HttpResponse::HTTP_CONFLICT);
				}

				if($usuario->pregunta_secreta != null || $usuario->pregunta_secreta != ""){
					return Response::json(['data' => $usuario->pregunta_secreta], 200);
				} else {
					return Response::json(['error' => ["pregunta"=>["not-exist"]]], HttpResponse::HTTP_CONFLICT);
				}
			}	
		} catch (Exception $e) {			
			return response()->json(['error' => $e->getMessage()], 500);
		}
	}

	public function validarRespuesta(Request $request)
    {
        
        
		$input = $request->only('id','respuesta');
		$mensajes = [
            
            'required'      => "required",
        ];

        $reglas = [
			'id'        => 'required',
			'respuesta' => 'required'
        ];

        $v = Validator::make($input, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        try {
			
			$usuario = Usuario::find($input['id']);

			if(!$usuario){
				return Response::json(['error' => ["id"=>["not-exist"]]], HttpResponse::HTTP_CONFLICT);
			}
			if($usuario->pregunta_secreta != null || $usuario->pregunta_secreta != ""){

				// Bloqueamos acciones de los usuarios que no están en el servidor que les corresponde
				if($usuario->servidor_id != env('SERVIDOR_ID')){
					return Response::json(['error' => ["respuesta"=>["wrong"]]], HttpResponse::HTTP_CONFLICT);
				}

				if($usuario->respuesta == $input['respuesta']){
					$reset_token = Hash::make($usuario->id.".".time());
					$usuario->reset_token = $reset_token;
					$usuario->save();
					return Response::json(['data' => ["reset_token" => $reset_token]], 200);

				} else{
					return Response::json(['error' => ["respuesta"=>["wrong"]]], HttpResponse::HTTP_CONFLICT);
				}


			} else {
				return Response::json(['error' => ["pregunta"=>["not-exist"]]], HttpResponse::HTTP_CONFLICT);
			}

        } catch (Exception $e) {
        
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

	public function passwordNuevo(Request $request,  $id){
		$input = $request->only('reset_token','password_nuevo');
		$mensajes = [
            
            'required'      => "required"
        ];

        $reglas = [
			'reset_token'        => 'required',
			'password_nuevo'        => 'required'
        ];

        $v = Validator::make($input, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
		}
		
		$usuario = Usuario::where('id',$id)->where('reset_token',$input['reset_token'])->first();
		try {
			if(!$usuario){
				return Response::json(['error' => ["token"=>["invalid"]]], HttpResponse::HTTP_UNAUTHORIZED);
			} else {

				// Bloqueamos acciones de los usuarios que no están en el servidor que les corresponde
				if($usuario->servidor_id != env('SERVIDOR_ID')){
					return Response::json(['error' => ["token"=>["invalid"]]], HttpResponse::HTTP_UNAUTHORIZED);
				}

				$usuario->password = Hash::make($input['password_nuevo']);
				$usuario->reset_token = '';
				$usuario->save();
				return Response::json(['data' => "Contraseña actualizada"], 200);
			}	
		} catch (Exception $e) {			
			return response()->json(['error' => $e->getMessage()], 500);
		}
	}



    public function enviarEmail(Request $request)
    {
        
        
		$input = $request->only('email','reset_url');
		$mensajes = [
            
            'required'      => "required",
            'email'         => "email",
        ];

        $reglas = [
            'email'        => 'required|email'
        ];

        $v = Validator::make($input, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        try {
			

			if(!isset($input['email'])){
				throw new Exception("El correo es obligatorio");
			}
			
			$usuario = Usuario::where('email',$input['email'])->first();

			if(!$usuario || trim($input['email']) == ""){
				return Response::json(['error' => ["email"=>["not-exist"]]], HttpResponse::HTTP_CONFLICT);
			}

			// Bloqueamos acciones de los usuarios que no están en el servidor que les corresponde
			if($usuario->servidor_id != env('SERVIDOR_ID')){
				return Response::json(['error' => ["email"=>["not-exist"]]], HttpResponse::HTTP_UNAUTHORIZED);
			}

			$reset_token = Hash::make($usuario->id.".".time());
			$usuario->reset_token = $reset_token;
			$usuario->save();


			Mail::send('emails.reset-password', ['usuario' => $usuario, 'reset_url' => $input['reset_url']], function ($m) use ($usuario) {
				$m->from('informatica.salud.chiapas@gmail.com', 'SIAL');
	
				$m->to($usuario->email, $usuario->nombre)->subject('Reestablecer contraseña');
			});

        } catch (Exception $e) {
        
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
<?php
require_once '../respuestas/response.php';
require_once '../modelos-datos/database.php';



class User extends Database
{
	private $table = 'usuarios';  //nombre de la tabla

	//parámetros permitidos para hacer consultas selección.
	//sólo permito hacer consultas get siempre que esten estos parámetros aqui
	private $allowedConditions_get = array(
		'id',
		'nombre',
		'disponible',
		'imagen',
		'page'
	);


	//parámetros permitidos para la inserción. Al hacer el POST
	private $allowedConditions_insert = array(
		'email',
		'password',
		'nombre',
		'imagen',
		'disponible'
	);

//parámetros permitidos para la actualización.
private $allowedConditions_update = array(
	'email',
	'password',
	'nombre',
	'imagen',
	'disponible'
	
);

	private function validateInsert($data){
		
		if(!isset($data['email']) || empty($data['email'])){
			$response = array(
				'result' => 'error',
				'details' => 'El campo email es obligatorio'
			);

			Response::result(400, $response);
			exit;
		}
		if(!isset($data['nombre']) || empty($data['nombre'])){
			$response = array(
				'result' => 'error',
				'details' => 'El campo nombre es obligatorio'
			);

			Response::result(400, $response);
			exit;
		}
		/*
		Si viene el campo disponible, debe ser booleano.
		*/
		if(isset($data['disponible']) && !($data['disponible'] == "1" || $data['disponible'] == "0")){
			$response = array(
				'result' => 'error',
				'details' => 'El campo disponible debe ser del tipo boolean'
			);

			Response::result(400, $response);
			exit;
		}

		if (!isset($data['password'])  ||  empty($data['password'])) {
			$response = array(
				'result' => 'error',
				'details' => 'El password es obligatoria'
			);

			Response::result(400, $response);
			exit;
		}
		
		
		if (isset($data['imagen']) && !empty($data['imagen'])) {
			
			$img_array = explode(';base64,', $data['imagen']);
			$extension = strtoupper(explode('/', $img_array[0])[1]); //me quedo con jpeg
			if ($extension!='PNG' && $extension!='JPG'  && $extension!='JPEG') {
				$response = array('result'  => 'error', 'details' => 'Formato de la imagen no permitida, sólo PNG/JPE/JPEG');
				Response::result(400, $response);
				exit;
			}
		}

		

		return true;
	}



	private function validateUpdate($data){
		
		if(!isset($data['email']) || empty($data['email'])){
			$response = array(
				'result' => 'error',
				'details' => 'El campo email es obligatorio'
			);

			Response::result(400, $response);
			exit;
		}
		
		if(isset($data['disponible']) && !($data['disponible'] == "1" || $data['disponible'] == "0")){
			$response = array(
				'result' => 'error',
				'details' => 'El campo disponible debe ser del tipo boolean'
			);

			Response::result(400, $response);
			exit;
		}

		if (!isset($data['password'])  ||  empty($data['password'])){
			$response = array(
				'result' => 'error',
				'details' => 'El password es obligatoria'
			);

			Response::result(400, $response);
			exit;
		}

		if (isset($data['imagen']) && !empty($data['imagen'])) {
			$img_array = explode(';base64,', $data['imagen']);
			$extension = strtoupper(explode('/', $img_array[0])[1]); //me quedo con jpeg
			if ($extension!='PNG' && $extension!='JPG'  && $extension!='JPEG') {
				$response = array('result'  => 'error', 'details' => 'Formato de la imagen no permitida, sólo PNG/JPE/JPEG');
				Response::result(400, $response);
				exit;
			}
		}
		
		return true;
	}


	public function get($params){
		foreach ($params as $key => $param) {
			if(!in_array($key, $this->allowedConditions_get)){
				unset($params[$key]);
				$response = array(
					'result' => 'error',
					'details' => 'Error en la solicitud'
				);
	
				Response::result(400, $response);
				exit;
			}
		}

		//ejecuta el método getDB de Database. Contendrá todos los usuarios.
		$usuarios = parent::getDB($this->table, $params);

		return $usuarios;
	}


	public function insert($params)
	{
		//recordamos que params, es un array asociaivo del tipo 'id'=>'1', 'nombre'=>'andrea'
		foreach ($params as $key => $param) {
			//echo $key." = ".$params[$key];
			if(!in_array($key, $this->allowedConditions_insert)){
				unset($params[$key]);
				$response = array(
					'result' => 'error',
					'details' => 'Error en la solicitud. Parametro no permitido'
				);
	
				Response::result(400, $response);
				exit;
			}
		}
		
		if($this->validateInsert($params)){
			
			if (isset($params['imagen'])){
				/*echo "Tiene imagen";
				exit;*/
				$img_array = explode(';base64,', $params['imagen']);  //datos de la imagen
				$extension = strtoupper(explode('/', $img_array[0])[1]); //formato de la imagen
				$datos_imagen = $img_array[1]; //aqui me quedo con la imagen
				$nombre_imagen = uniqid(); //creo un único id.
				$path = dirname(__DIR__, 1)."/public/img/".$nombre_imagen.".".$extension;
				
				file_put_contents($path, base64_decode($datos_imagen));  //subimos la imagen al servidor.
				$params['imagen'] = $nombre_imagen.'.'.$extension;
			}

			$password_encriptada = hash('sha256' , $params['password']);
			$params['password'] = $password_encriptada;
			return parent::insertDB($this->table, $params);
		}

		
	}




	public function update($id, $params)
	{
		foreach ($params as $key => $parm) {
			//debe comprobar que los parámetros son los permitidos.
			//si hubiera otro parámetro como 'codigo', no estaría permitida.
			if(!in_array($key, $this->allowedConditions_update)){
				unset($params[$key]);
				echo $params[$key];
				$response = array(
					'result' => 'error',
					'details' => 'Error en la solicitud dentro del modelo datos'
				);
	
				Response::result(400, $response);
				exit;
			}
		}
this->validateUpdate($params)){
			//ahora debemos encriptar la password
			$password_encriptada = hash('sha256' , $params['password']);
			$params['password'] = $password_encriptada;
			//Si mandamos imagen.
			if (isset($params['imagen'])){

				//necesito saber el nombre del fichero antiguo a partir del id y eliminarlo del servidor.
				$usuarios = parent::getDB($this->table, $_GET);
				$usuario = $usuarios[0];
				$imagen_antigua = $usuario['imagen'];
				//echo $imagen_antigua;
				$path = dirname(__DIR__, 1)."/public/img/".$imagen_antigua;
				//si no puedo eliminar la imagen antigua, lo indico.
				if (!unlink($path)){
					$response = array(
						'result' => 'warning',
						'details' => 'No se ha podido eliminar el fichero antiguo'
					);	
					Response::result(200, $response);
					exit;
					
				}
				

				//ahora tengo que crear la nueva imagen y actualizar registro.
				
				$img_array = explode(';base64,', $params['imagen']);  //datos de la imagen
				$extension = strtoupper(explode('/', $img_array[0])[1]); //formato de la imagen
				$datos_imagen = $img_array[1]; //aqui me quedo con la imagen
				$nombre_imagen = uniqid(); //creo un único id.
				$path = dirname(__DIR__, 1)."/public/img/".$nombre_imagen.".".$extension;
				file_put_contents($path, base64_decode($datos_imagen));  //subimos la imagen al servidor.
				$params['imagen'] = $nombre_imagen.'.'.$extension;
			}




			//actualizamos el registro a partir de una query que habrá que armar en updateDB
			$affected_rows = parent::updateDB($this->table, $id, $params);

			if($affected_rows==0){
				$response = array(
					'result' => 'error',
					'details' => 'No hubo cambios'
				);
				
				Response::result(200, $response);
				exit;
			}
		}

			
	}


	public function delete($id)
	{

		//Necesito eliminar su imagen, en el supuesto de que exista.	
		$usuarios = parent::getDB($this->table, $_GET);
		$usuario = $usuarios[0];
		$imagen_antigua = $usuario['imagen'];
		if(!empty($imagen_antigua)){
			$path = dirname(__DIR__, 1)."/public/img/".$imagen_antigua;
			if (!unlink($path)){
				$response = array(
					'result' => 'warning',
					'details' => 'No se ha podido eliminar la imagen del usuario'
				);	
				Response::result(200, $response);
				exit;
					
			}

		}
		
		$affected_rows = parent::deleteDB($this->table, $id);

		if($affected_rows==0){
			$response = array(
				'result' => 'error',
				'details' => 'No hubo cambios'
			);

			Response::result(200, $response);
			exit;
		}
	}
}

?>
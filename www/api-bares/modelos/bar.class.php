<?php
require_once '../respuestas/response.php';
require_once '../modelos-datos/database.php';

class Bar extends Database
{
	private $table = 'bares';

	//parámetros permitidos para hacer consultas selección.
	private $allowedConditions_get = array(
		'id',
		'id_usuario',
		'nombre',
		'descripcion',
		'imagen',
	);


	//parámetros permitidos para la inserción.
	private $allowedConditions_insert = array(
		'id_usuario',
		'nombre',
		'descripcion',
		'imagen',
	);

	//parámetros permitidos para la actualización.
	private $allowedConditions_update = array(
		'nombre',
		'descripcion',
		'imagen',
		
	);


	private function validate($data){
		
		if(!isset($data['id_usuario']) || empty($data['id_usuario'])){
			$response = array(
				'result' => 'error',
				'details' => 'El campo id del usuario es obligatorio'
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
		if(!isset($data['descripcion']) || empty($data['descripcion'])){
			$response = array(
				'result' => 'error',
				'details' => 'El campo descripcion es obligatorio'
			);

			Response::result(400, $response);
			exit;
		}

		/*
		Tengo que comprobar la extensión de la imagen del pueblo
		*/
		if (isset($data['imagen']) & !empty($data['imagen'])){
			$img_array = explode(';base64,', $data['imagen']);
			$extension = strtoupper(explode('/', $img_array[0])[1]);
			if ($extension !='PNG' && $extension!= 'JPG' && $extension!= 'JPEG'){
				$response = array('result'  => 'error', 'details' => 'Formato de la imagen no permitida, sólo PNG/JPE/JPEG');
				Response::result(400, $response);
				exit;
			}	
		}
			
		return true;
	}



	/*
Recorre los parámetros y si no existe alguno de ellos, crea un
error 400. Si no hubo error, ejecutará el método getDB de la clasle
Database que es el padre. 

Al método que le pasa es el nombre de la tabla y los parámetros. Devuelve
los objetos de tipo clase.
	*/
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

		$usuarios = parent::getDB($this->table, $params);

		return $usuarios;
	}



	/*
Recorremos todos los parámetros comprobando si están permitidos.
En el momento que encuentre a un parámetro que no está dentro de los permitidos,
arma un error 400 y se sale.

Si no se sale, los parámetros son los correctos, por tanto hay que ejecutar
una función que valida, porque consideramos que el campo nombre debe estar ya que es
obligatorio y de que no venga su nombre vacío.

Si la validación es correcta, llamamos al método insertDB de database. Nos arma la consulta
y la ejecutará.
*/
	public function insert($params)
	{
		foreach ($params as $key => $param) {
			//echo $key."=".$param;
			if(!in_array($key, $this->allowedConditions_insert)){
				unset($params[$key]);
				
				$response = array(
					'result' => 'error',
					'details' => 'Error en la solicitud de insercion, por parametros'
				);	
	
				Response::result(400, $response);
				exit;
			}
		}
		

		if($this->validate($params)){
			//Me quedo con los datos del fichero y con el nombre del fichero
			//Debemos de crear la imagen en el servidor.
			if (isset($params['imagen'])){
				$img_array = explode(';base64,', $params['imagen']);  //datos de la imagen
				$extension = strtoupper(explode('/', $img_array[0])[1]); //formato de la imagen
				$datos_imagen = $img_array[1]; //aqui me quedo con los datos de la imagen
				$nombre_imagen = uniqid(); //creo un único id.
				$path = dirname(__DIR__, 1)."/public/img/".$nombre_imagen.".".$extension;
				file_put_contents($path, base64_decode($datos_imagen));  //subimos la imagen al servidor.
				$params['imagen'] = $nombre_imagen.'.'.$extension;  //pasamos como parametro en foto, con el nombre y extensión completo.
			}//fin isset

			return parent::insertDB($this->table, $params);
		}

		
	}



	public function update($id, $params)
	{
		foreach ($params as $key => $parm) {
			if(!in_array($key, $this->allowedConditions_update)){
				unset($params[$key]);
				//echo $params[$key];
				$response = array(
					'result' => 'error',
					'details' => 'Error en la solicitud dentro del modelo datos'
				);
	
				Response::result(400, $response);
				exit;
			}
		}

		if($this->validate($params)){
			

			if (isset($params['imagen'])){
				//necesito saber el nombre del fichero antiguo a partir del id y eliminarlo del servidor.
				$usuarios = parent::getDB($this->table, $_GET);
				$usuario = $usuarios[0];
				$imagen_antigua = $usuario['imagen'];
				$path = dirname(__DIR__, 1)."/public/img/".$imagen_antigua;
				//si no puedo eliminar la imagen antigua, lo indico.
				unlink($path);
		
				$img_array = explode(';base64,', $params['imagen']);  //datos de la imagen
				$extension = strtoupper(explode('/', $img_array[0])[1]); //formato de la imagen
				$datos_imagen = $img_array[1]; //aqui me quedo con la imagen
				$nombre_imagen = uniqid(); //creo un único id.
				$path = dirname(__DIR__, 1)."/public/img/".$nombre_imagen.".".$extension;
				file_put_contents($path, base64_decode($datos_imagen));  //subimos la imagen al servidor.
				$params['imagen'] = $nombre_imagen.'.'.$extension;  //pasamos como parametro en foto, con el nombre y extensión completo.
			}//fin isset

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
					'details' => 'No se ha podido eliminar la imagen del bar'
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
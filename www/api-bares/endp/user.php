<?php
require_once '../respuestas/response.php';
require_once '../modelos/user.class.php';
require_once '../modelos/auth.class.php';



/*
ESTE ENDPOINT, SERÁ LLAMADO SIEMPRE QUE QUERAMOS HACER UN
****LISTADO (GET)
****MODIFICAR-USUARIO (PUT)
*****ELIMINAR-USUARIO(DELETE)

Compara que el token sea el correcto y que la decodificación con clave privada sea la correcta.*/
$user = new User();  //creamos un objeto de la clase User.

switch ($_SERVER['REQUEST_METHOD']) {
	case 'GET':
		$params = $_GET; //leemos los parámetros por URL

		$usuarios = $user->get($params); 	//Recuperamos todos los usuarios.
		//Arma la respuesta con resultado ok y los usuarios en el array.  Luego le pasamos
		//a resul nuestro response.
		//ME FALTA, MANDAR COMO IMAGEN, LA URL.
		$url_raiz_img="http://".$_SERVER['HTTP_HOST']."/api-bares/public/img";

		for($i=0; $i< count($usuarios); $i++){
			if (!empty($usuarios[$i]['imagen']))
				$usuarios[$i]['imagen'] = $url_raiz_img ."/". $usuarios[$i]['imagen'];
		}
		
		$response = array(
			'result' => 'ok',
			'usuarios' => $usuarios
		);

		Response::result(200, $response);

		break;

	case 'POST':
		$params = json_decode(file_get_contents('php://input'), true);  //supongo que se envía por @body

		/*
		Comprueba si existen parámetros. Si no existe, devuelve la respuesta de error 400.
		*/
		if(!isset($params)){
			$response = array(
				'result' => 'error',
				'details' => 'Error en la solicitud'
			);

			Response::result(400, $response);
			exit;
		}


		//aquí insertamos el nuevo usuario a partir de nuestro objeto user.
		$insert_id = $user->insert($params);

		$response = array(
			'result' => 'ok',
			'insert_id' => $insert_id
		);

		Response::result(201, $response);


		break;

	case 'PUT':
		$params = json_decode(file_get_contents('php://input'), true);

		/*
		Es obligatorio que al editar un usuario, exista el parámetro id y valor.
		*/
		if(!isset($params) || !isset($_GET['id']) || empty($_GET['id'])){
			$response = array(
				'result' => 'error',
				'details' => 'Error en la solicitud de actualización'
			);

			Response::result(400, $response);
			exit;
		}

		//actualizamos por id.
		$user->update($_GET['id'], $params);
		/**
		 * toca actualizar el token del usuario, ya que modificó obligatoriamente
		 * el campo email.
		 */
		$auth->modifyToken($_GET['id'], $params["email"]);
		$response = array(
			'result' => 'ok'
		);

		Response::result(200, $response);
		
		break;

	case 'DELETE':

		/*
		Es obligatorio el id por GET
		*/
		if(!isset($_GET['id']) || empty($_GET['id'])){
			$response = array(
				'result' => 'error',
				'details' => 'Error en la solicitud'
			);

			Response::result(400, $response);
			exit;
		}
		//eliminamos al usuario, cuya id pasamos.
		$user->delete($_GET['id']);

		$response = array(
			'result' => 'ok'
		);

		Response::result(200, $response);
		break;
	default:
		$response = array(
			'result' => 'error'
		);

		Response::result(404, $response);

		break;
}
?>
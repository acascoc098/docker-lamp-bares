<?php
require_once '../respuestas/response.php';
require_once '../modelos/bar.class.php';
require_once '../modelos/auth.class.php';

/**
 * endpoint para la gestión de datos con los bares.
 * Get (para objeter todos los bares)
 *  - token (para la autenticación y obtención del id usuario)
 * 
 * Post (para la creación de bar)
 *  - token (para la autenticación y obtención del id usuario)
 *  - datos del bar por body
 * 
 * Put (para la actualización del bar)
 *  *  - token (para la autenticación y obtención del id usuario)
 *  - id del bar por parámetro
 *  - datos nuevos del barr body
 * 
 * Delete (para la eliminación del bar)
 *  *  - token (para la autenticación y obtención del id usuario)
 *  - id del bar por parámetro
 * 
 */


$auth = new Authentication();
//Compara que el token sea el correcto 
$auth->verify();



//hasta aquí, el token está perfectamente verificada. Creamos modelo para que pueda gestionar las peticiones
$bar = new Bar();

switch ($_SERVER['REQUEST_METHOD']) {
	case 'GET':
		$params = $_GET;  //aquí están todos los parámetros por url

        if (isset($_GET['id_usuario']) && !empty($_GET['id_usuario'])){
            if ($_GET['id_usuario'] != $auth->getIdUser()){
                $response = array(
                    'result' => 'error',
                    'details' => 'El id no corresponde con el del usuario autenticado. '
                ); 
                Response::result(400, $response);
			    exit;
            }
        }else{
            $params['id_usuario'] = $auth->getIdUser();
        }


        
        //Recuperamos todos los bares
        $bares = $bar->get($params);
        //$auth->insertarLog('lleva a solicitud de bares');
        $url_raiz_img = "http://".$_SERVER['HTTP_HOST']."/api-bares/public/img";
		for($i=0; $i< count($bares); $i++){
			if (!empty($bares[$i]['imagen']))
				$bares[$i]['imagen'] = $url_raiz_img ."/". $bares[$i]['imagen'];
		}


        $response = array(
            'result'=> 'ok',
            'bares'=> $bares
        );
       // $auth->insertarLog('devuelve bares'); 
        Response::result(200, $response);
        break;
    
    case 'POST':
       // $auth->insertaLog("Recibe petición de creacion de bar");

        /**
         * Recibimos el json con los datos a insertar, pero necesitamos
         * ogligatoriamente el id del usuario. Si no está, habrá un error.
         * El id del usuario verificado, deberá ser igual al id_usuario que
         * es la clave secundaria.
         */
        $params = json_decode(file_get_contents('php://input'), true);
     
       
            //si pasamos un id del usuario, comprobamos que sea el mismo que el del token
        if (isset($params['id_usuario']) && !empty($params['id_usuario'])){
            if ($params['id_usuario'] != $auth->getIdUser()){
                $response = array(
                    'result' => 'error',
                    'details' => 'El id pasado por body no corresponde con el del usuario autenticado. '
                ); 
                Response::result(400, $response);
			    exit;
            }
        }else{
            //hay que añadir a $params el id del usuario.
            $params['id_usuario'] = $auth->getIdUser();
        }




        $insert_id_bar = $bar->insert($params);
        //Debo hacer una consulta, para devolver tambien el nombre de la imagen.
        $id_param['id'] = $insert_id_bar;
        $bar = $bar->get($id_param);
        if($bar[0]['imagen'] !='')
            $name_file =  "http://".$_SERVER['HTTP_HOST']."/api-bares/public/img/".$bar[0]['imagen'];
        else
            $name_file = '';

        $response = array(
			'result' => 'ok insercion',
			'insert_id' => $insert_id_bar,
            'file_img'=> $name_file
		);

		Response::result(201, $response);
        break;


    case 'PUT':
		$params = json_decode(file_get_contents('php://input'), true);

        if (!isset($params) || !isset($_GET['id']) || empty($_GET['id'])  ){
            $response = array(
				'result' => 'error',
				'details' => 'Error en la solicitud de actualización del bar. No has pasado el id del bar'
			);

			Response::result(400, $response);
			exit;
        }

         //si pasamos un id del usuario, comprobamos que sea el mismo que el del token
         if (isset($params['id_usuario']) && !empty($params['id_usuario'])){
            if ($params['id_usuario'] != $auth->getIdUser()){
                $response = array(
                    'result' => 'error',
                    'details' => 'El id del body no corresponde con el del usuario autenticado. '
                ); 
                Response::result(400, $response);
			    exit;
            }
        }else{
            //hay que añadir a $params el id del usuario.
            $params['id_usuario'] = $auth->getIdUser();
        }


        $bar->update($_GET['id'], $params);  //actualizo ese bar.
        $id_param['id'] = $_GET['id'];
        $bar = $bar->get($id_param);
       

        if($bar[0]['imagen'] !='')
            $name_file =  "http://".$_SERVER['HTTP_HOST']."/api-bares/public/img/".$bar[0]['imagen'];
        else
            $name_file = '';
            
        $response = array(
			'result' => 'ok actualizacion',
            'file_img'=> $name_file
		);



		Response::result(200, $response);
        break;


    case 'DELETE':
        /*
        El id, también lo puedo sacar del token. Lo modificaré mas adelante.
        */
        if(!isset($_GET['id']) || empty($_GET['id'])){
			$response = array(
				'result' => 'error',
				'details' => 'Error en la solicitud'
			);

			Response::result(400, $response);
			exit;
		}

		$bar->delete($_GET['id']);

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
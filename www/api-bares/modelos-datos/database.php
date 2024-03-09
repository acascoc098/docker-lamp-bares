<?php

class Database
{
	private $connection;  //guardará la conexión
	private $results_page = 50; //número de resultados por página.

	public function __construct(){
		$this->connection = new mysqli('db', 'root', 'andrea', 'baresDb', '3306');
		if($this->connection->connect_errno){
			echo 'Error de conexión a la base de datos';
			exit;
		}
	}


	public function getDB($table, $extra = null)
	{
		$page = 0;
		$query = "SELECT * FROM $table";

		if(isset($extra['page'])){
			$page = $extra['page'];
			unset($extra['page']);
		}

		if($extra != null){
			$query .= ' WHERE';

			foreach ($extra as $key => $condition) {
				$query .= ' '.$key.' = "'.$condition.'"';
				if($extra[$key] != end($extra)){
					$query .= " AND ";
				}
			}
		}

		if($page > 0){
			$since = (($page-1) * $this->results_page);
			$query .= " LIMIT $since, $this->results_page";
		}
		else{
			//sólo queremos los primeros 50 registros o menos.
			$query .= " LIMIT 0, $this->results_page";
		}

		//echo $query;exit;
		$results = $this->connection->query($query);
		$resultArray = array();

		//pasamos todos los registros a resultArray.
		foreach ($results as $value) {
			$resultArray[] = $value;
			
		}

	//	echo $resultArray['id'];exit;
		return $resultArray;  //retornamos el array con los registros.
	}



	public function insertDB($table, $data)
	{
		$fields = implode(',', array_keys($data));
		$values = '"';
		$values .= implode('","', array_values($data));
		$values .= '"';

		//aquí hacemos la inserción de la query en la tabla.
		$query = "INSERT INTO $table (".$fields.') VALUES ('.$values.')';
		//echo $query;exit;
		$this->connection->query($query);

		return $this->connection->insert_id;
	}


	public function updateDB($table, $id, $data)
	{	
		$query = "UPDATE $table SET ";
		foreach ($data as $key => $value) {
			$query .= "$key = '$value'";
			/*
			si ese dato no es el último, hay que añadir una ,
			*/
			if(sizeof($data) > 1 && $key != array_key_last($data)){
				$query .= " , ";
			}
		}

		$query .= ' WHERE id = '.$id;

		//echo $query; exit;
		$this->connection->query($query);

		if(!$this->connection->affected_rows){
			return 0;
		}

		return $this->connection->affected_rows;
	}




	public function deleteDB($table, $id)
	{
		$query = "DELETE FROM $table WHERE id = $id";
		$this->connection->query($query);

		if(!$this->connection->affected_rows){
			return 0;
		}

		return $this->connection->affected_rows;
	}
}


?>
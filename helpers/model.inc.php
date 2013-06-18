<?php

	class DbConn {
	
		private static $instance = null;
		private $db = null;
		
		private function __clone() {}
		private function __construct()
		{	
			$this->db = new PDO(PDO_DATABASE, PDO_USERNAME, PDO_PASSWORD, array(
								PDO::ATTR_PERSISTENT => false,
								PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
								PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
							) );
			$this->db -> exec("SET NAMES utf8");
		}
		
		public static function getInstance()
		{
			if( is_null( static::$instance ) )
				static::$instance = new DbConn();
			
			return static::$instance;
		}
		
		public function getDB()
		{
			return $this->db ;
		}
	}
			
	
	abstract class ActiveRecord {


		protected $data = array();


		protected function getData($key, $default = null)
		{
			if( isset( $this->data[$key] ) )
				return $this->data[$key];

			$this->data[$key] = $default;

			return $default;
		}

		protected static function fillModel($data, $model)
		{
			if( !is_array( $data ) || count( $data ) <= 0 )
				return null;

			$model->data = $data;				
			return $model;
		}
	
		protected static function cachedQuery($id, $prefix, $sql, $arrExec, $validationFunc = null)
		{
			if( is_null( $id ) || strlen( $id ) == 0 )
				return false;
		
			$id_key = CommonCache::buildVarName($prefix, $id);
			
			$cc = CommonCache::getInstance();
			
			$arr = $cc->get( $id_key );

			if( $arr !== false && is_array( $arr ) )
			{
				if( is_null( $validationFunc ) )
					return $arr;
					
				else
				{
				
					if( $validationFunc( $arr ) )
						return $arr;
						
					else
						$cc->delete( $id_key );

				}
			}	
			else
			{
				$arr = static::query( $sql, $arrExec );
				
				if( is_array( $arr ) && count( $arr ) > 0 )
				{
					if( is_null( $validationFunc ) || $validationFunc( $arr ) )
					{
						$cc->set( $id_key, $arr ) ;
					
						return $arr;
					}
				}
			}
		}
		
		
		protected static function executeQuery( $sql, $execArr, &$stmt = null )
		{
			$dbh = DbConn::getInstance()->getDB();

			$stmt = $dbh->prepare($sql);
			
			return $stmt->execute($execArr) ;
		}
		protected static function query( $sql, $execArr )
		{
			if( static::executeQuery( $sql, $execArr, $stmt ) !== false )
				return $stmt->fetch();
				
			return false;
		}



	}

	interface SavableActiveRecord {
		public function save();
	}

	interface DeletableActiveRecord {
		public function delete();
	}

<?php

	DEFINE('PAGE_HEADER', VIEWS_DIR . '/header.html.php');
	DEFINE('PAGE_FOOTER', VIEWS_DIR . '/footer.html.php');

	class Template {
		
		private $data = array();
		
		private $include_headers = true;
		
		private $jsonArray = array();
		private $jsonCode = R_GLOB_ERR_UNDEFINED;
		
		public function includeHeaders( $inc )
		{
			if( is_bool( $inc ) )
				$this->include_headers = $inc;
		}
		
		
		public function render( $view )
		{
			$router = Router::getInstance();
			
			$view_file = VIEWS_DIR . "/{$router->getControllerName()}/{$view}.html.php" ;
			
			if( !is_file( $view_file ) )
				throw new Exception( "View file '{$view_file}' does not exist" );
				
			$data = $this->data ;
			
			if( $this->include_headers )
				include_once( PAGE_HEADER );

			include_once( $view_file );
			
			if( $this->include_headers )
				include_once( PAGE_FOOTER );
		}


		public function renderHTML( $html )
		{
			header('Content-type: text/html; charset=utf-8', true);

			echo $html;
		}
		public function renderText( $text )
		{
			header('Content-type: text/plain; charset=utf-8', true);

			echo $text;
		}


		public function setJSONCode($code)
		{
			$this->jsonCode = is_int( $code ) ? $code : R_GLOB_ERR_UNDEFINED ;
		}
		public function setJSONResponse($arr)
		{
			if( !is_null($arr) && is_array( $arr ) )
				$this->jsonArray = $arr;
		}
		
		public function renderJSON( $msgArr = array() )
		{
			$jsonEnc = null;

			$arrOut = array( 's' => (int)$this->jsonCode ,
							 'm' => describeMessage( $this->jsonCode, $msgArr ) ,
							 'r' => $this->jsonArray );
			
			if( ( $jsonEnc = @json_encode( $arrOut ) ) === false )
				throw new Exception("Cannot encode array as json");
			
			else
			{
				header('Content-Type: application/json', true);
				
				echo $jsonEnc;
			}
		}
	
	
		public function get( $key, $default = null )
		{
			if( isset( $this->data[ $key ] ) )
				return $this->data[ $key ];
			
			return $default;
		}
		
		public function set( $key, $value )
		{
			$this->data[$key] = $value ;
		}
	}

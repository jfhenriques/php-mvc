<?php

	DEFINE('PAGE_HEADER', VIEWS_DIR . 'header.html.php');
	DEFINE('PAGE_FOOTER', VIEWS_DIR . 'footer.html.php');

	class Template {
		
		private $data = array();
		private $router = null;
		
		private $include_headers = true;
		
		private $jsonArray = array();
		private $jsonCode = R_GLOB_ERR_UNDEFINED;


		private function __clone() { }
		public function __construct($router)
		{
			$this->router = $router;
		}


		
		public function includeHeaders( $inc )
		{
			if( is_bool( $inc ) )
				$this->include_headers = $inc;
		}
		
		
		public function renderDirect( $view_file )
		{
			$router = $this->router ;
			
			if( !is_file( $view_file ) )
				throw new Exception( "View file '{$view_file}' does not exist" );
				
			$data = $this->data ;
			//extract($this->data, EXTR_SKIP);

			header('Content-type: text/html; charset=utf-8', true);

			
			if( $this->include_headers )
				include_once( PAGE_HEADER );

			include_once( $view_file );
			
			if( $this->include_headers )
				include_once( PAGE_FOOTER );

			exit;
		}

		public function render( $view )
		{
			return $this->renderDirect( VIEWS_DIR . "{$this->router->getControllerName()}" . DS . "{$view}.html.php" ) ;
		}

		public function renderHTML( $html )
		{
			header('Content-type: text/html; charset=utf-8', true);

			echo $html;

			exit;
		}
		public function renderText( $text )
		{
			header('Content-type: text/plain; charset=utf-8', true);

			echo $text;

			exit;
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

		private function init_form($name, $named_route, $method = "POST", $class = "")
		{
			echo "<form name=\"{$name}\" action=\"{$this->router->getPath('{$named_route}')}\" method=\"{$method}\" class=\"{$class}\">\n";
		}
		private function end_form()
		{
			echo "</form>\n";
		}


		public static function compileLessFilesChecked()
		{
			$cssfile = null;

			if( VERSION_HAS_CHANGED
				|| !file_exists( $cssfile = PUBLIC_DIR . RESULTING_CSS )
				|| ( DEVELOPMENT_ENVIRONMENT && DEV_ALWAYS_RECOMPILE_LESS ) )
				self::compileLessFiles( $cssfile );
		}

		public static function compileLessFiles($cssfile = null)
		{
			if( RESULTING_CSS !== '' && LESS_FILES !== '' )
			{
				if( is_null( $cssfile ) )
					$cssfile = PUBLIC_DIR . RESULTING_CSS ;

				$fp = fopen( $cssfile, 'c');
				
				if ( !flock($fp, LOCK_EX) )
				{
					@fclose( $fp );
					throw new Exception("Cannot aquire lock to '${cssfile}'. The file may already being edited.");
				}

				try {
				
					if( !ftruncate($fp, 0) )
						throw new Exception("Error while truncating '${cssfile}'.");	

					$less = new lessc;
					$less->setFormatter("compressed");

					$less->setVariables(array(
					  					'@ENVIRONMENT' => DEVELOPMENT_ENVIRONMENT ? 'development' : 'production',
					  					'@BASE_URI' => BASE_URI ) );

					foreach(explode( ',', LESS_FILES ) AS $f)
					{
						$lessFile = LESS_DIR . trim($f) ;

						if( !is_file($lessFile) )
							throw new Exception("File not found, or not file: '${lessFile}'");

						$cont = $less->compileFile( $lessFile );

						fwrite($fp, $cont);
						fwrite($fp, "\n");
					}

					@fflush($fp);
					flock($fp, LOCK_UN);
					@fclose($fp);

				} catch(Exception $e) {

					@flock($fp, LOCK_UN);
					@fclose($fp);
					@unlink($cssfile);

					throw $e;
				}
				
			}

		}

	}

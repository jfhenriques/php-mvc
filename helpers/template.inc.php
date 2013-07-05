<?php

	DEFINE('PAGE_HEADER', VIEWS_DIR . 'header.html.php');
	DEFINE('PAGE_FOOTER', VIEWS_DIR . 'footer.html.php');

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
		
		
		public function renderDirect( $view_file, $router = null )
		{
			$router = is_null( $router ) ? Router::getInstance() : $router ;
			
			if( !is_file( $view_file ) )
				throw new Exception( "View file '{$view_file}' does not exist" );
				
			$data = $this->data ;

			header('Content-type: text/html; charset=utf-8', true);

			
			if( $this->include_headers )
				include_once( PAGE_HEADER );

			include_once( $view_file );
			
			if( $this->include_headers )
				include_once( PAGE_FOOTER );
		}

		public function render( $view )
		{
			$router = Router::getInstance();

			return $this->renderDirect( VIEWS_DIR . "{$router->getControllerName()}" . DS . "{$view}.html.php", $router ) ;
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



		public static function compileLessFiles()
		{
			if( RESULTING_CSS !== '' && LESS_FILES !== '' )
			{
				$cssfile = PUBLIC_DIR . RESULTING_CSS;

				if( VERSION_HAS_CHANGED || !file_exists( $cssfile )
					|| ( DEVELOPMENT_ENVIRONMENT && DEV_ALWAYS_RECOMPILE_LESS ) )
				{
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
						  					'@DEVELOPMENT' => DEVELOPMENT_ENVIRONMENT,
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

	}

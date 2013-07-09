<?php

	DEFINE('PAGE_HEADER', VIEWS_DIR . 'header.html.php');
	DEFINE('PAGE_FOOTER', VIEWS_DIR . 'footer.html.php');


	DEFINE('NOTICE_SUCCESS'	, 'success');
	DEFINE('NOTICE_INFO'	, 'info');
	DEFINE('NOTICE_WARNINGS', 'warnings');
	DEFINE('NOTICE_ERRORS'	, 'errors');

	class Template {
		
		private $data = array();
		//private $forms = array();
		private $router = null;
		
		private $include_headers = true;
		
		private $jsonArray = array();
		private $jsonCode = R_GLOB_ERR_UNDEFINED;


		private function __clone() { }
		public function __construct($router)
		{
			$this->router = $router;

			self::__init_array($_SESSION, NOTICE_SUCCESS);
			self::__init_array($_SESSION, NOTICE_INFO);
			self::__init_array($_SESSION, NOTICE_WARNINGS);
			self::__init_array($_SESSION, NOTICE_ERRORS);

			self::__init_array($_SESSION, 'forms');
		}


		public static function __init_array($arr, $key)
		{
			if( !is_array( $arr ) )
				$arr = array( $key => array() );

			else
			if( !isset( $arr[$key] ) || !is_array( $arr[$key] ) )
				$arr[$key] = array();
		}
		public function includeHeaders( $inc )
		{
			if( is_bool( $inc ) )
				$this->include_headers = $inc;
		}

		private function set_status_code($code)
		{
			if( $code !== 200 && defined('PHP_VERSION_ID') && PHP_VERSION_ID > 50400 )
				return http_response_code( $code );

			return false;
		}
		
		
		public function renderDirect( $view_file, $st_code = 200 )
		{
			$router = $this->router ;
			
			if( !is_file( $view_file ) )
				throw new Exception( "View file '{$view_file}' does not exist" );
				
			$data = $this->data ;
			//extract($this->data, EXTR_SKIP);

			header('Content-type: text/html; charset=utf-8', true);
			$this->set_status_code($st_code);

			
			if( $this->include_headers )
				include_once( PAGE_HEADER );

			include_once( $view_file );
			
			if( $this->include_headers )
				include_once( PAGE_FOOTER );

			exit;
		}

		public function render( $view, $st_code = 200 )
		{
			return $this->renderDirect( VIEWS_DIR . "{$this->router->getControllerName()}" . DS . "{$view}.html.php", $st_code ) ;
		}

		public function renderHTML( $html, $st_code = 200 )
		{
			header('Content-type: text/html; charset=utf-8', true);

			$this->set_status_code($st_code);

			echo $html;

			exit;
		}
		public function renderText( $text, $st_code = 200 )
		{
			header('Content-type: text/plain; charset=utf-8', true);

			$this->set_status_code($st_code);

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
		
		public function renderJSON( $msgArr = array(), $st_code = 200 )
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

				$this->set_status_code($st_code);
				
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

		private function __printNotices($arrIn, $class = "")
		{

			if( !is_array( $arrIn ) )
				return false;

			$ret = "";

			foreach( $arrIn as $not )
				$ret .= "<div class=\"{$class}\">{$not}</div>\n";

			return $ret;

		}

		private function printErrors($class = 'alert-error')
		{
			if( isset( $_SESSION[NOTICE_ERRORS] ) )
			{
				$ret = $this->__printNotices( $_SESSION[NOTICE_ERRORS], $class);
				unset( $_SESSION[NOTICE_ERRORS] );

				return $ret;
			}
		}

		private function printSuccess($class = 'alert-success')
		{
			if( isset( $_SESSION[NOTICE_SUCCESS] ) )
			{
				$ret = $this->__printNotices( $_SESSION[NOTICE_SUCCESS], $class);
				unset( $_SESSION[NOTICE_SUCCESS] );

				return $ret;
			}
		}

		private function init_form($name, $named_route, $method = "POST", $class = "")
		{
			$rand = Controller::genRand64();

			$_SESSION['forms'][$name] = $rand ;

			return    "<form name=\"{$name}\" action=\"{$this->router->getPath('{$named_route}')}\" method=\"{$method}\" class=\"{$class}\">\n"
					. "<input type=\"hidden\" name=\"ctrlcode\" value=\"{$rand}\" />\n";

		}
		private function end_form()
		{
			return "</form>\n";
		}



		public static function verifyForm($name, $ctrlcode, $unset = true)
		{
			if( isset( $_SESSION['forms'][$name] ) && $_SESSION['forms'][$name] === $ctrlcode )
			{
				if( $unset )
					unset( $_SESSION['forms'][$name] );

				return true;
			}

			return false;
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

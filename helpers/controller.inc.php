<?php


	DEFINE( 'R_STATUS_OK'				, 0x0	);

	DEFINE( 'R_GLOB_ERR_UNDEFINED'		, 0x100	);
	DEFINE( 'R_GLOB_ERR_SAVE_UNABLE'	, 0x101	);

	DEFINE( 'R_GLOB_ERR_MUST_AUTH'		, 0x150	);
	DEFINE( 'R_GLOB_ERR_MUST_NOT_AUTH'	, 0x151	);


	

	function valid_var( $var, $arr, $trim = true, $default = null )
	{
		if( is_array( $arr ) && isset( $arr[ $var ] ) && !is_null( $arr[ $var ] ) )
		{
			
			if( is_string( $arr[ $var ] ) )
			{
				$val = $trim ? trim( $arr[ $var ] ) : $arr[ $var ] ;

				if( strlen( $val ) > 0 )
					return $val;
			}
			else
				return $arr[ $var ];
		}

		return $default;
	}
	function valid_request_var( $var, $trim = true, $default = null )
	{
		return valid_var( $var, $_REQUEST, $trim, $default );
	}




	function describeMessage( $code, $arr = array() )
	{

		$globStatusCode = array(
							R_GLOB_ERR_UNDEFINED		=> 'Erro Indefinido',
							R_GLOB_ERR_SAVE_UNABLE		=> 'Impossível Salvar',

							R_GLOB_ERR_MUST_AUTH		=> 'Utilizador não autenticado',
							R_GLOB_ERR_MUST_NOT_AUTH	=> 'Utilizador não pode estar autenticado',
						);
		
		if( is_null( $code ) )
			$code = R_GLOB_ERR_UNDEFINED ;

		return isset( $globStatusCode[$code] ) ?
							$globStatusCode[$code]
							: ( ( is_array( $arr ) && isset( $arr[$code] ) ) ?
										$arr[$code] :
										null );
	}




	class Controller {
	
		public $respond = null;
		private $requireAuth = false;
		
		private static $authFunction = null;
		
		
		
		
		public function __construct()
		{
			$this->respond = new Template();
			
			$this->__configure();
		}
		
		protected function __configure() {}
	

		public function __checkAuth( $auth = true, $exit = false )
		{
			if( is_null( Controller::$authFunction ) )
				return false;

			$func = Controller::$authFunction;
			$ret_val = $func();

			if( $exit && $ret_val !== $auth )
			{
				if( $auth )
					header('HTTP/1.0 403 Forbidden', true);

				$retType = Router::getInstance()->responseType() ;
				$renderCode = $auth ? R_GLOB_ERR_MUST_AUTH : R_GLOB_ERR_MUST_NOT_AUTH ;

				if( $retType === RESPOND_JSON )
				{
					$this->respond->setJSONCode( $renderCode );
					$this->respond->renderJSON();
				}

				else
					echo $auth ? "403 Forbidden" : describeMessage($renderCode) ;


				exit (1);

			}
			
			return $ret_val === $auth;
		}


		public function checkAuth($check = true)
		{
			return $this->__checkAuth($check, false);
		}

		public function requireAuth()
		{
			return $this->__checkAuth(true, true);
		}
		public function requireNoAuth()
		{
			return $this->__checkAuth(false, true);
		}


		public function sendCustomMail($to, $subject, $contentType, $text, $replySameTo = false)
		{
			$headers = sprintf("From: %s\r\nReply-To: %s\r\nMIME-Version: 1.0\r\n".
							   "Content-Type: %s; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit",
							   MAIL_FROM_ADDRESS, $replySameTo ? $to : MAIL_FROM_ADDRESS , $contentType);

			return mail($to, $subject, $text, $headers);
		}

		public static function formatURL($src)
		{
			if( is_null( $src ) )
				return null;
			
			return ( USE_STATIC_URI ? BASE_STATIC_URI : ( '/' . BASE_URI ) ) . $src ;
		}


		public static function genRand64($raw = false)
		{
			return self::genRand('sha256', $raw);
		}
		public static function genRand128($raw = false)
		{
			return self::genRand('sha512', $raw);
		}
		public static function genRand($algo, $raw = false)
		{
			return hash( $algo, uniqid(rand(), true), $raw );
		}

		
		public static function registerAuthFunction( $func )
		{
			Controller::$authFunction = &$func;
		}
	}

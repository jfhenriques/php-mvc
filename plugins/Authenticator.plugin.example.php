<?php

	class AuthenticatorPlugin extends Plugin {
	
		static private $instance = null;
		
		private $session = null;
		
		
		public static function getInstance()
		{
			if( is_null( self::$instance ) )
				self::$instance = new AuthenticatorPlugin();
				
			return self::$instance;
		}
		
		
		private function __clone() { }
		
		private function __construct()
		{
			$sess = null;
			$token = valid_request_var( 'token' );

			if( !is_null( $token )
				&& ( $sess = Session::findByToken( $token ) ) != null
				&& $sess->getValidity() >= 0
				&& ( TOKEN_VALIDITY === 0
					|| $sess->getValidity() >= time() ) )
					$this->session = $sess;
		}
		
		public function getSession()
		{
			return $this->session;
		}
		
		public function getToken()
		{
			if( !is_null( $this->session ) )
				return $this->session->getToken();
				
			return null;
		}
		
		public function getUID()
		{
			if( !is_null( $this->session ) )
				return $this->session->getUID();
				
			return 0;
		}

		public function getUser()
		{
			if( !is_null( $this->session ) )
				return User::findByUID( $this->session->getUID() );
				
			return null;
		}



		protected static function __initialize()
		{
			Controller::registerAuthFunction(function() {

				$auth = AuthenticatorPlugin::getInstance();
				
				return !is_null( $auth->getSession() );
			});
		}
	
	}

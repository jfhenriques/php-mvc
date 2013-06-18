<?php

	abstract class Plugin {

		private static $loaded = false;


		protected static function __initialize() {}		

		public static function loadPlugins()
		{
			if( !static::$loaded )
			{
				static::$loaded = true;

				$cachedplugins = AutoLoader::getInstance()->getCachedPlugins();

				if( is_array( $cachedplugins ) )
				{
					foreach( $cachedplugins AS $plug )
					{

						$plug::__initialize();
					}
				}

			}
		}

	}

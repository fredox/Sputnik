<?php

	class plugin {
	
		public $active = true;
		
		public function __construct( $sputnik ) 
		{
            $this->sputnik = $sputnik;
        }
		
		public function isActive()
		{
			return $this->active;
		}
		
		public function install()
		{
			echo "\n[".get_called_class()."] Plugin has no instalation.";
		}
		
		public function path()
		{
			$pluginName = get_called_class();
			$path       = str_replace( '_', '/', $pluginName );
			
			return './plugins/' . $path . '/';
		}
	}
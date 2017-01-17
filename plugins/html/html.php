<?php

    class html extends plugin
    {
		public $data = '';
		public $htmlUris;

		public function turnOffIfNoHtmlUri()
		{
			// Desactivamos plugin si no es una url definida como html
			if ( !in_array( $this->sputnik->request['uri'], array_keys( $this->htmlUris ) ) )
				$this->active = false;
			else
				$this->sputnik->format = 'html';
		}
		
		public function _INIT_HOOK_html()
		{
			$this->sputnik->uris = array_merge( $this->sputnik->uris, $this->htmlUris );
		}
		
		public function _PRE_HOOK_html() 
		{
			$this->turnOffIfNoHtmlUri();
		}

		public function _PRE_BUILD_HOOK_html()
		{
			$this->turnOffIfNoHtmlUri();
		}
		
        public function _CORE_printOutput()
        {
           echo $this->data;
        }
		
		public function addCss( $cssFile ) {}
		public function addJs( $jsFile ) {}
		public function addHtml( $htmlFile ) {}
		public function tag( $tag, $content ) {
			return '<'.$tag.'>'.$content.'</'.$tag.'>';
		}
    }

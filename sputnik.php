<?php
/*     
 *  .   \       .    o       .   
 *    -(S)putnik     .         o
 *      /     .           .      .
 *   .     . a RESTful prototype framework.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

class Sputnik {

    public $responseCodes = array(        
        '200'  => 'OK',
		'201'  => 'created',
        '304'  => 'Not Modified',
        '401'  => 'Unauthorized',
        '404'  => 'Not Found',
        '405'  => 'Method not allowed',
        '422'  => 'Unprocessable Entity'
    );

    public $contentTypes = array(
        'json' => 'application/json',
        'xml'  => 'application/xml',
        'html' => 'text/html'
    );

    public $allowedMethods = array(
        'GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'
    );

	public $corsOriginEnabled = false;
	
    public $headers 		= array();
    public $uris    		= array();
    public $extra   		= array();
    public $plugins 		= array();	
	public $requiredPlugins = array();
	public $response        = array();

    public $request;
    public $format = 'json';

    function __construct( $requiredPlugins = null )
    {
        if ( !empty( $requiredPlugins ) ) {
            $this->requiredPlugins = $requiredPlugins;
            $this->initPlugins();
        }
    }
	
	function enableCorsOriginIfNeeded()
	{
		if ($this->corsOriginEnabled === true)
			header("Access-Control-Allow-Origin: *");
	}

	function method()
	{
		$method = (isset($_POST['_method'])) ? $_POST['_method'] : $_SERVER['REQUEST_METHOD'];

        if ( !in_array( $method, $this->allowedMethods ) ) 
            throw new Exception( 'Method Not Allowed', 405 );

		return $method;
	}

	function fillRequest()
	{
		if ( !empty( $_SERVER['QUERY_STRING'] ) ) 
			parse_str( $_SERVER['QUERY_STRING'], $request['GET'] );

		$request = array( 'method' => $this->method() );

		if ( $request['method'] == 'POST' || $request['method'] == 'GET') 
			$request['params'] = $_REQUEST;
		else
			parse_str( file_get_contents( "php://input" ), $request['params'] );

        $this->request = $request;
        $uriParts = explode('?', $_SERVER['REQUEST_URI']);
        $this->request['uri']      = array_shift($uriParts); 
        $this->request['slugs']    = $this->slugs();
        $this->request['headers']  = $this->getRequestHeaders();
        $this->request['function'] = '';


	}

	function slugs( $index = false )
    { 
		$slugs = explode( '/', trim( $this->request['uri'], '/' ) ); 
		
		if ( !empty($index) && !is_numeric($index) ) {
			
			$slugs = explode( '/', trim( $this->request['uri'], '/' ) );
			$key   = array_search( $index, $slugs );
			
			if ( ($key !== FALSE) && isset($slugs[$key+1]) )
				return $slugs[$key+1];
			
			return false;
		}
		
		
		if ( $index == false )
			return $slugs;
		else {
            if ( isset( $slugs[$index] ) )
			    return $slugs[$index];
            else
                return false;
        }
	}  

    function setHeader( $code )
    {
        $this->headers[] = 'HTTP/1.0 ' . $code . ' ' . $this->responseCodes[(string)$code];
    }

    function setContentType( $tag )
    {
        $this->headers[] = 'Content-Type: ' . $this->contentTypes[$tag];
    }

    function actionExists( $action )
    {
        foreach ( $this->plugins as $plugin ) {
            if ( method_exists( $plugin, $action ) ) return true;
        }

        if ( method_exists( $this, $action ) ) return true;

        return false;
    }

	function run()
	{
        try {
                $this->hook( '_INIT_HOOK_' );
				$this->enableCorsOriginIfNeeded();
                $this->fillRequest();

			    foreach ( $this->uris as $pattern => $function ) {

				    if (preg_match('/^'.addcslashes($pattern,'/').'$/', $this->request['uri'])) 
				    {
						$this->request['function']	= $function;
					
					    $classMethod   = $this->request['method'] . '_' . $function;

					    if ( $this->actionExists( $classMethod ) ) {

                            $this->hook( '_PRE_HOOK_' );

                            $this->execute( $classMethod );

                            $this->hook( '_POST_HOOK_' );
							
							if ( empty( $this->response['code'] ) ) {
								$this->setHeader('200');
								$this->response['code']    = 200;
								$this->response['message'] = 'OK';
							}
						
					    }

					    break;
				    }
                }
		    
        } Catch ( Exception $e ) {
            
            $response = array( 'code' => $e->getCode(), 'message' => $e->getMessage() );
            $this->response = array_merge( $this->response, $response );
            $this->setHeader( $e->getCode() );
        }

		
        if ( empty( $this->response ) ) {

            $this->setHeader('404');
		    $this->response = array( 'code' => 404, 'message' => 'Not Found' );
        }

        $this->hook( '_PRE_BUILD_HOOK_' );

		$this->execute( '_CORE_buildResponse' );
	}

    function execute( $stringMethod )
    {
        if ( !$this->hook( $stringMethod ) ) $this->$stringMethod();
    }

    function initPlugins()
    {
		require_once( './plugins/plugin.php' );
        foreach ( $this->requiredPlugins as $k => $plugin ) {
			
            $path = str_replace( '_', '/', $plugin );
			
            include_once( './plugins/' . $path . '/' . $plugin . '.php' );
			
			$pluginObject = new $plugin( $this );
			$this->validatePluginDependencies( $pluginObject );
            $this->plugins[$k] = $pluginObject;
        }
    }

    function hook( $type )
    {
        $executed = false;

        foreach ( $this->plugins as $k => $plugin ) {
			if ( $this->plugins[$k]->isActive() === false )
				continue;
		
            $classMethods = $this->getClassMethods( $type, $plugin );

            foreach ( $classMethods as $classMethod ) {
                $executed = true;
                $this->plugins[$k]->$classMethod( $this );
            }
        }

        return $executed;
    }

    function getClassMethods( $string, $obj )
    {
        $matchedMethods = array();
        $classMethods   = get_class_methods( $obj );

        foreach ( $classMethods as $classMethod )
            if ( strpos( $classMethod, $string ) === 0 ) 
                $matchedMethods[] = $classMethod;

        return $matchedMethods;
    }

    function _CORE_buildResponse()
    {
        $this->setContentType( $this->format );

        foreach ( $this->headers as $header ) 
            header( $header );

        $this->execute( '_CORE_printOutput' );
    }

    function _CORE_printOutput()
    {
        if ( $this->format == 'json' )
            echo json_encode( $this->response );
        else
            throw new Exception( 'Internal API error: Format not supported', 500 );
    }

    function getPlugin( $plugin )
    {
        $key = array_search( $plugin, array_map( 'get_class', $this->plugins ) );

        if ( !is_numeric( $key ) ) return false;

        return $this->plugins[$key];
    }
	
	function isLoadedPlugin( $plugin )
	{
        $pluginObject = $this->getPlugin( $plugin );
		return !empty( $pluginObject );
	}
	
	function validatePluginDependencies( $pluginObject )
	{	
		if ( property_exists( $pluginObject, 'dependencies' ) ) {
			
			foreach ( $pluginObject->dependencies as $dependence ) {
			
				if ( !$this->isLoadedPlugin( $dependence ) ) {
					throw new Exception( 'Plugin:[' . get_class( $pluginObject ) . '], requires [' . $dependence . '] plugin have been loaded before ' );
				}
			}
		}
		
		return true;
	}
	
	function install()
	{
        echo "\n\n -(S)putnik installation plugins:\n\n";
		foreach ( $this->plugins as $plugin )
			$plugin->install();
        echo "\n\n";
	}

    function getRequestHeaders()
    {   
        if (!function_exists('getallheaders'))
        {
            function getallheaders()
            {
                $headers = '';
                foreach ($_SERVER as $name => $value)
                {
                   if (substr($name, 0, 5) == 'HTTP_')
                   {
                       $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                   }
                }
                return $headers;
            }
        } else {
            return getallheaders();
        }
    }
}

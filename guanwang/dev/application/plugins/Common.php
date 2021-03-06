<?php

class CommonPlugin extends Yaf_Plugin_Abstract
{
    private $_cfg;
    private $_layoutVars =array();
    public function __construct()
    {
        $this->_cfg = Yaf_Registry::get('config');
    }
    public function  __set($name, $value) {
        $this->_layoutVars[$name] = $value;
    }
	public function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
	{
	    $suffix = $this->_cfg['application']['url_suffix'];
        if( $suffix && strpos($_SERVER['REQUEST_URI'], $suffix) ) {
            $uri = explode($suffix, $_SERVER['REQUEST_URI']);
            $request->setRequestUri($uri[0]);
        }
        
		Yaf_Registry::set('layout', 1);
	}
	
	public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
	{
		$module = $request->getModuleName();
		$controller = $request->getControllerName();
		if( $module == 'Admin' && $controller != 'Index' ) {
			$user = new AdminModel();
			$info = $user->getLogin();
			if( empty($info) ) {
				throw new Exception('访问被禁止。');
			}
			session_write_close();
		}
	}
	
	public function dispatchLoopShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
	{
		$set_ly = Yaf_Registry::get('layout');
		if( ! $set_ly ) {
			return ;
		}
		
		$app_path = APPLICATION_PATH;
		$module = $request->getModuleName();
		if( is_string($set_ly) ) {
			if( empty($module) ) {
				$ly = "{$app_path}/application/views/{$set_ly}";
			} else {
				$ly = "{$app_path}/application/modules/{$module}/views/{$set_ly}";
			}
		} else {
			$layout = $this->_cfg['layout'];
			$default = $layout['default'];
			
			if( empty($module) ) {
				if( isset($layout['index']) ) {
					$ly = $layout['index'];
				} else {
					$ly = $default;
				}
				$ly = "{$app_path}/application/views/{$ly}";
			} else {
				if( isset($layout[$module]) ) {
					$ly = $layout[$module];
				} else {
					$ly = $default;
				}
				$ly = "{$app_path}/application/modules/{$module}/views/{$ly}";
			}
		}
		
		if( file_exists($ly) ) {
//            $content = $response->getBody();
//            $response->clearBody();
//            include $ly;
            $body = $response->getBody();
            /*clear existing response*/
            $response->clearBody();
            /* wrap it in the layout */
            $layout = new Yaf_View_Simple("{$app_path}/application/modules/{$module}/views/");
            $layout->content = $body;
            $layout->assign('layout', $this->_layoutVars);
            /* set the response to use the wrapped version of the content */
            $response->setBody($layout->render('layout.phtml'));
        }
	}
}

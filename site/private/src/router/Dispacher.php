<?php
// Classe dispachante, responsável por verificar qual classe/função deve atender a solicitação do cliente

namespace Router;

use DI\Container;

class Dispacher
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function dispach($callback, $params = [], $namespace = "App\\Controller\\")
    {   
        // Se o callback for uma chamada de função retorna a execução dela
        if(is_callable($callback['callback']))
        {
            return call_user_func_array($callback['callback'], array_values($params));
        
        // Caso contrário, chama a classe/método responsável por tratar a solicitação   
        } elseif (is_string($callback['callback'])) {
        
            if(!!strpos($callback['callback'], '@') !== false) {
    
    
                if(!empty($callback['namespace']))
                {
                    $namespace = $callback['namespace'];
                }
            
                $callback['callback'] = explode('@', $callback['callback']);
                $controller = $namespace.$callback['callback'][0];
                $method = $callback['callback'][1];
    
                $rc = new \ReflectionClass($controller);
    
                if($rc->isInstantiable() && $rc->hasMethod($method))
                {
                    return $this->container->call([$controller, $method], $params);
                } else {
    
                    throw new \Exception("Erro ao despachar: controller não pode ser instanciado, ou método não existe");                
                }
            }
        }
        throw new \Exception("Erro ao despachar: método não implementado");
    }
}

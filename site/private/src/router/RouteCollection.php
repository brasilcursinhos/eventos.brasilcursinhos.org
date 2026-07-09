<?php
// Classe responsável por armazenar a coleção de rotas do site 
namespace Router;

class RouteCollection
{
    protected $routes_post = [];
    protected $routes_get = [];
    protected $routes_put = [];
    protected $routes_delete = [];
    protected $routes_options = [];
    protected $route_names = [];
 
    // Método que adiciona uma nova rota a coleção
    public function add($request_type, $pattern, $callback)
    {
        switch($request_type)
        {
            case 'post':
                return $this->addPost($pattern, $callback);
                break;
            case 'get':
                return $this->addGet($pattern, $callback);
                break;
            case 'put':
                return $this->addPut($pattern, $callback);
                break;
            case 'delete':
                return $this->addDelete($pattern, $callback);
                break;
            case 'options':
                return $this->addOptions($pattern, $callback);
                break;
            defautl:
                throw new \Exception('Tipo de requisição não implementado');
        }
    }
 
    // Método que pesquisa a existência de uma determinada rota na coleção
    public function where($request_type, $pattern)
    {
        switch($request_type){
                case 'post':
                    return $this->findPost($pattern);
                    break;
                case 'get':
                    return $this->findGet($pattern);
                    break;
                case 'put':
                    return $this->findPut($pattern);
                    break;
                case 'delete':
                    return $this->findDelete($pattern);
                    break;
                case 'options': // <-- ADICIONADO
                    return $this->findOptions($pattern);
                    break;
                defautl:
                    throw new \Exception('Tipo de requisição não implementado');
        }
     
    }
 
    // Define o padrão da rota     
    protected function definePattern($pattern) 
    {
        $pattern = implode('/', array_filter(explode('/', $pattern)));
        $pattern = '/^' . str_replace('/', '\/', $pattern) . '$/';
     
        if (preg_match("/\{[A-Za-z0-9\_\-]{1,}\}/", $pattern)) {
            $pattern = preg_replace("/\{[A-Za-z0-9\_\-]{1,}\}/", "[A-Za-z0-9\-]{1,}", $pattern);
        }
     
        return $pattern;
     
    }

    // Verifica se o nome alternativo para a rota existe
    public function isThereAnyHow($name)
    {
        return $this->route_names[$name] ?? false;
    }

    // Converte uma rota com base no seu padrão e parametos para a URL correspondente
    public function convert($pattern, $params)
    {
        if(!is_array($params)) {
            $params = array($params);
        }
    
        $positions = $this->toMap($pattern);

        if($positions === false) {
            $positions = [];
        }

        $pattern = array_filter(explode('/', $pattern));
    
        if(count($positions) < count($pattern)) {

            $uri = [];

            foreach($pattern as $key => $element) {

                if(in_array($key - 1, $positions)) {
                    $uri[] = array_shift($params);
                } else {
                    $uri[] = $element;
                }
            }

            return implode('/', array_filter($uri));
        }
        return false;
    }

    // Adiciona uma rota do tipo POST a coleção
    protected function addPost($pattern, $callback){
 
        if(is_array($pattern)) {
             
            $settings = $this->parsePattern($pattern);
             
            $pattern = $settings['set'];
        } else {
             
            $settings = [];
        }
     
        $values = $this->toMap($pattern);
     
        $this->routes_post[$this->definePattern($pattern)] = [
            'callback' => $callback,
            'values' => $values,
            'namespace' => $settings['namespace'] ?? null,
            'roles' => $settings['roles'] ?? []
        ];
        
        if(isset($settings['as'])) {
            $this->route_names[$settings['as']] = $pattern;
        }

        return $this;
    }
    
    // Adiciona uma rota do tipo GET a coleção
    protected function addGet($pattern, $callback){
         
        if(is_array($pattern)) {
             
            $settings = $this->parsePattern($pattern);
             
            $pattern = $settings['set'];
        } else {
             
            $settings = [];
        }
     
        $values = $this->toMap($pattern);
         
        $this->routes_get[$this->definePattern($pattern)] = [
            'callback' => $callback,
            'values' => $values,
            'namespace' => $settings['namespace'] ?? null,
            'roles' => $settings['roles'] ?? []
        ];
     
        if(isset($settings['as'])) {
            $this->route_names[$settings['as']] = $pattern;
        }

        return $this;
    }
    
    // Adiciona uma rota do tipo PUT  a coleção
    protected function addPut($pattern, $callback){
         
        if(is_array($pattern)) {
             
            $settings = $this->parsePattern($pattern);
             
            $pattern = $settings['set'];
        } else {
             
            $settings = [];
        }
     
        $values = $this->toMap($pattern);
         
        $this->routes_put[$this->definePattern($pattern)] = [
            'callback' => $callback,
            'values' => $values,
            'namespace' => $settings['namespace'] ?? null,
            'roles' => $settings['roles'] ?? []
        ];

        if(isset($settings['as'])) {
            $this->route_names[$settings['as']] = $pattern;
        }

        return $this;
    }
    
    // Adiciona uma rota do tipo DELETE a coleção
    protected function addDelete($pattern, $callback){
     
        if(is_array($pattern)) {
             
            $settings = $this->parsePattern($pattern);
             
            $pattern = $settings['set'];
        } else {
             
            $settings = [];
        }
     
        $values = $this->toMap($pattern);
     
        $this->routes_delete[$this->definePattern($pattern)] = [
            'callback' => $callback,
            'values' => $values,
            'namespace' => $settings['namespace'] ?? null,
            'roles' => $settings['roles'] ?? []
        ];
        
        if(isset($settings['as'])) {
            $this->route_names[$settings['as']] = $pattern;
        }

        return $this;
    }

    protected function addOptions($pattern, $callback){
     
        if(is_array($pattern)) {
            $settings = $this->parsePattern($pattern);
            $pattern = $settings['set'];
        } else {
            $settings = [];
        }
     
        $values = $this->toMap($pattern);
     
        $this->routes_options[$this->definePattern($pattern)] = [
            'callback' => $callback,
            'values' => $values,
            'namespace' => $settings['namespace'] ?? null,
            'roles' => $settings['roles'] ?? []
        ];
        
        if(isset($settings['as'])) {
            $this->route_names[$settings['as']] = $pattern;
        }

        return $this;
    }

    // Rescreve a uri garantindo que não há espaços vazios entre duas /
    protected function parseUri($uri)
    {
        return trim($uri, '/');//implode('/', array_filter(explode('/', $uri)));
    }

    // Pesquisa se a rota POST informada existe
    protected function findPost($pattern_sent)
    {
        $pattern_sent = $this->parseUri($pattern_sent);
    
        foreach($this->routes_post as $pattern => $callback) {
            
            if(preg_match($pattern, $pattern_sent, $pieces)) {
                return (object) ['callback' => $callback, 'uri' => $pieces];
            }
        }

        return false;
    }
 
    // Pesquisa se a rota GET informada existe
    protected function findGet($pattern_sent)
    {
        $pattern_sent = $this->parseUri($pattern_sent);

        foreach($this->routes_get as $pattern => $callback) {
           
            if(preg_match($pattern, $pattern_sent, $pieces)) {
                return (object) ['callback' => $callback, 'uri' => $pieces];
            }
        }

        return false;
    }
    
    // Pesquisa se a rota PUT informada existe
    protected function findPut($pattern_sent)
    {
        $pattern_sent = $this->parseUri($pattern_sent);
    
        foreach($this->routes_put as $pattern => $callback) {
            
            if(preg_match($pattern, $pattern_sent, $pieces)) {
                return (object) ['callback' => $callback, 'uri' => $pieces];
            }
        }

        return false;
    }
    
    // Pesquisa se a rota DELETE informada existe
    protected function findDelete($pattern_sent)
    {
        $pattern_sent = $this->parseUri($pattern_sent);
    
        foreach($this->routes_delete as $pattern => $callback) {
            
            if(preg_match($pattern, $pattern_sent, $pieces)) {
                return (object) ['callback' => $callback, 'uri' => $pieces];
            }
        }

        return false;
    }

    protected function findOptions($pattern_sent)
    {
        $pattern_sent = $this->parseUri($pattern_sent);
    
        foreach($this->routes_options as $pattern => $callback) {
            
            if(preg_match($pattern, $pattern_sent, $pieces)) {
                return (object) ['callback' => $callback, 'uri' => $pieces];
            }
        }

        return false;
    }

    // Retorna a posição da primeira ocorrência de algum caracter do array $needles ou falso caso não haja
    protected function strposarray(string $haystack, array $needles, int $offset = 0)
    {
        $result = false;
        
        if(strlen($haystack) > 0 && count($needles) > 0) {
            
            foreach($needles as $element){
                
                $result = strpos($haystack, $element, $offset);
                
                if($result !== false){
                    break;
                }
            }
        }

        return $result;
    }
    
    // Mapeia as posições curigas (destinadas a receber valores pela URL) numa rota
    protected function toMap($pattern)
    {
        $result = [];
    
        $needles = ['{', '[', '(', "\\"];
    
        $pattern = array_filter(explode('/', $pattern));
    
        foreach($pattern as $key => $element)
        {
            $found = $this->strposarray($element, $needles);
    
            if($found !== false) {
                
                if(substr($element, 0, 1) === '{'){
                    $result[preg_filter('/([\{\}])/', '', $element)] = $key - 1;
                } else {
                    $index = 'value_' . !empty($result) ? count($result) + 1 : 1;
                    array_merge($result, [$index => $key - 1]);
                }
            }
        }

        return count($result) > 0 ? $result : false;
    }

    // Pega dos dados do padrão da rota caso seja definido um nome alternativo (apelido) para a rota
    // Ou seja redefinido o namespace do controlador invocado pelo despachante
    protected function parsePattern(array $pattern)
    {
        $result['set'] = $pattern['set'] ?? null;
        $result['as'] = $pattern['as'] ?? null;
        $result['namespace'] = $pattern['namespace'] ?? null;
        $result['roles'] = $pattern['roles'] ?? [];
        
        return $result;
    }
}

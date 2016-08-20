<?php

    header('Access-Control-Allow-Origin: *', false);
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

class Router {

    /**
    * current url requested
    */
    private $url;

    /**
    * contains all the defined routes
    */
    private $routes = [];

    /* 
    * constructor
    */
    public function __construct() {
        $this->url = (!empty($_GET['z_router_url'])) ? $_GET['z_router_url'] : '/';
    }//function

    /**
    * add a get route
    */
    public function get($routeName, callable $callback) {
        $this->routes['GET'][$routeName] = $callback;
    }//function

    /**
    * add a post route
    */
    public function post($routeName, callable $callback) {
        $this->routes['POST'][$routeName] = $callback;
    }//function

    /**
    * execution method
    */
    public function run() {
        $method = $_SERVER['REQUEST_METHOD'];
        $url = $this->url;
        $options = [
            'GET' => $_GET,
            'POST' => json_decode(file_get_contents("php://input"), true)//$_POST
        ];
        unset($options['GET']['z_router_url']);

        // method exception
        if( !isset( $this->routes[$method] ) ) {
            $this->error("Request method does not exist.");
            //throw new \Exception('Request method does not exist.');
        }

        // route exception
        if( !isset($this->routes[$method][$url]) ){
            // throw new \exception('No route');
            $this->error("No route found for this url (". $url .")");
        }

        $this->routes[$method][$url]($options);

    }//function

    public function error($message = "unknown error"){        
        header("HTTP/1.0 500 Internal Server Error");
        header('Content-Type: application/json');
        echo json_encode(["error" => $message]);
        exit(0);
    }

}//class


/**
* =========================== Definition =============================
*/

$router = new Router;

// welcome
$router->get('/', function() {
    output([
        'data' => 'Hi, you are on the greenti API !'
    ]);
});




/* ===============
*   shopping cart
*  =============== */

// list of items in the cart
// if param item => return the item
$router->get('/cart', function($options) {
    $data = getListContent($options['GET']['item']);

    output($data);
});


// add an item in the cart list
$router->post('/cart/add', function($options) {

    $toAdd = [];
    $data = $options['POST'];

    if(!empty($data['title']) && !empty($data['code'])){
        $toAdd['title'] = $data['title'];
        $toAdd['code'] = $data['code'];
        $toAdd['checked'] = ($data['checked'] == true) ? true : false;

        output(addListContent($toAdd));
    }
    else{
        error("The item you're trying to add is not valid");
    }

});

// remove an item from the cart list
$router->post('/cart/delete', function($options) {

    $toDelete = $options['POST'];

    if(isset($toDelete['item'])){
        output(deleteListContent($toDelete['item']));
    }
    else{
        error("You must pass the item's index to delete it.");
    }
});

// remove an item from the cart list
$router->post('/cart/update', function($options) {

    $toUpdate = [];
    $data = $options['POST'];
    $index = $options['GET']['item'];

    if(!empty($data['title']) && !empty($data['code']) && isset($index)){
        $toUpdate['title'] = $data['title'];
        $toUpdate['code'] = $data['code'];
        $toUpdate['checked'] = ($data['checked'] == true) ? true : false;

        output(updateListContent($index, $toUpdate));
    }
    else{
        error("The item you're trying to update is not valid");
    }
});




/* ==================
*   start the router
*  ================== */
$router->run();




/**
* =========================== Utils =============================
*/

function getListContent($index = NULL) {
    $listPath = './list.json';
    $list = json_decode(file_get_contents($listPath), true);

    if($index == NULL) return $list;
    elseif(isset($list[intVal($index)])) return $list[intVal($index)];
    else error("The item you requested doesn't exist");
}

function addListContent($toAdd = NULL){
    if($toAdd === NULL) error("You must pass the item's data to add it.");

    $data = getListContent();

    $data[] = $toAdd;

    return writeFile($data);
}

function deleteListContent($index = NULL) {
    if($index === NULL) error("You must pass the item's index to delete it.");

    $data = getListContent();

    if(!isset($data[intVal($index)])) error("The item you want to delete does not exist");
    unset($data[intVal($index)]);
    $newData  = array_values($data); 

    return writeFile($newData);
}

function updateListContent($index = NULL, $toUpdate = NULL){
    if($toUpdate === NULL) error("You must pass the item's data to update it.");
    if($index === NULL) error("You must pass the item's index to update it.");

    $data = getListContent();

    if(!isset($data[intVal($index)])) error("The item you want to update does not exist");	

    $data[intVal($index)] = $toUpdate;
    $newData  = array_values($data);	

    return writeFile($newData);
}

function output($data = []) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit(0);
}

function error($message = "unknown error"){        
    header("HTTP/1.0 500 Internal Server Error");
    header('Content-Type: application/json');
    echo json_encode(["error" => $message]);
    exit(0);
}

function writeFile($data = []) {
    $toWrite = json_encode($data);

    $listPath = './list.json';
    $myfile = fopen($listPath, "w") or error("Error while writing in the file...");
    fwrite($myfile, $toWrite);
    fclose($myfile);
    return $data;
}

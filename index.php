<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';
class MyDB extends SQLite3 {
    function __construct() {
       $this->open('friends.db');
    }
 }

 $db = new MyDB();
 if(!$db) {
    echo $db->lastErrorMsg();
    exit();
 } 
$app = new \Slim\App;

$app->get(
    '/friends',
    function (Request $request, Response $response, array $args) use ($db) {
        $sql = "select * from participant";
        $ret = $db->query($sql);
        $friends = [];
        while ($friend = $ret->fetchArray(SQLITE3_ASSOC)) {
            $friends[] = $friend;
        }
        return $response->withJson($friends);
    }
);

$app->get(
    '/friends/{id}',
    function (Request $request, Response $response, array $args) use ($db) {
        $friendId=$args['id'];
        $sql = "select * from participant where id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue('id',$args['id']);
        $ret = $stmt->execute();
        $friends = $ret->fetchArray(SQLITE3_ASSOC);
        return $response->withJson($friends);
    }
);

$app->post(
    '/friends',
    function (Request $request, Response $response, array $args) use ($db) {
        $requestData = $request->getParsedBody();
        if (!isset($requestData['name']) || !isset($requestData['surname'])) {
            return $response->withStatus(400)->withJson(['error' => 'Name and surname are required.']);
        }
        $sql = "insert into 'participant' (name, surname) values (:name, :surname)";
        $stmt = $db->prepare($sql);
        $stmt->bindValue('name', $requestData['name']);
        $stmt->bindValue('surname', $requestData['surname']);
        $stmt->execute();
        $newUserId = $db->lastInsertRowID();
        return $response->withStatus(201)->withHeader('Location', "/friends/$newUserId");
    }
);

$app->run();
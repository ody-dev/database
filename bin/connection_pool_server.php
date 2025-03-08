<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Ody\Core\Monolog\Logger;
use Ody\DB\ConnectionPool\ConnectionFactories\PDOConnectionFactory;
use Ody\DB\ConnectionPool\ConnectionPoolFactory;
use Swoole\Server;

define('PROJECT_PATH', __DIR__ . "connection_pool_server.php/");

class Test
{
    public $pool;

    public function start()
    {
        $server = new Server("127.0.0.1", 9504);
        $server->on('start', function (Server $server) {
            Logger::write('info', 'server started; listening on tcp://127.0.0.1:9504');
            $connectionPoolFactory = ConnectionPoolFactory::create(
                size: 2,
                factory: new PDOConnectionFactory(
                    dsn: 'mysql:host=0.0.0.0;port=3306;dbname=ody',
                    username: 'root',
                    password: 'root',
                ),
            );

            $this->pool = $connectionPoolFactory->instantiate();
        });

        $server->on('receive', function ($server, $fd, $reactor_id, $data) {
            Logger::write('info', "received request; $fd, $reactor_id");
            Logger::write('info', "query: $data");

            $connection = $this->pool->borrow();
            $result = $connection->query($data);
            $result = $result->fetchAll(PDO::FETCH_ASSOC);

            $server->send($fd, json_encode($result));
            $server->close($fd);
        });

        $server->start();
    }
}

(new Test())->start();





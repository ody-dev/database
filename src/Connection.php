<?php

namespace Ody\DB;

use Illuminate\Database\Connection as BaseConnection;
use Ody\Core\Monolog\Logger;
use Ody\Swoole\Process\Exception;
use Swoole\Coroutine\Client;

class Connection extends BaseConnection
{
    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        echo "construct\n";
        parent::__construct($pdo, $database, $tablePrefix, $config);

        $this->poolHost = config('database.connection_pool.host');
        $this->poolPort = config('database.connection_pool.port');
        $this->poolEnabled = config('database.connection_pool.enabled');
    }

    public function select($query, $bindings = array(), $useReadPdo = true)
    {
        return $this->run($query, $bindings, function ($query, $bindings) use ($useReadPdo) {
            if ($this->pretending()) {
                return [];
            }

            // For select statements, we'll simply execute the query and return an array
            // of the database result set. Each element in the array will be a single
            // row from the database table, and will either be an array or objects.
            $statement = $this->prepared(
                $this->getPdoForSelect($useReadPdo)->prepare($query)
            );

            $this->bindValues($statement, $this->prepareBindings($bindings));

            $statement->execute();

            \Co\go(function () use ($statement) {
                if ($this->poolEnabled)
                {
                    $this->result = $this->sendToConnectionPool($statement);
                }

                $this->result = $statement->fetchAll();
            });

            return $this->result;
        });
    }

    public function selectOne($query, $bindings = array(), $useReadPdo = true)
    {
        // This method is pretty much straight forward. Call the
        // parent::select() method. If it returns any results
        // normalize the first result or else return null.
        $records = parent::select($query, $bindings);

        if (count($records) > 0)
        {
            return with(new Normalizer)->normalize(reset($records));
        }

        return null;
    }

    private function sendToConnectionPool(\PDOStatement $statement)
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
        }

        $result = socket_connect($socket, $this->poolHost, $this->poolPort);
        if ($result === false) {
            Throw new ConnectionPoolException("connect to pool failed. Error: " . socket_strerror(socket_last_error($socket)));
        }

        socket_write($socket, $statement->queryString);
        $result = '';
        while ($out = socket_read($socket, 2048)) {
            $result .= $out;
        }

        socket_close($socket);

        return json_decode($result, true);


//        $client = new Client(SWOOLE_SOCK_TCP);
//        if (!$client->connect($this->host, $this->port, 0.5)) {
//            Logger::write('error', "connect to pool failed. Error: {$client->errCode}");
//            Throw new ConnectionPoolException("connect to pool failed. Error: {$client->errCode}");
//        }
//
//        $client->send($statement->queryString);
//        $result = $client->recv();
//        $client->close();
//
//        return json_decode($result, true);
    }
}
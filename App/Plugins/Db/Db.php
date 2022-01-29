<?php

namespace App\Plugins\Db;

use App\Plugins\Db\Connection\IConnection;

class Db implements IDb
{
    /** @var \PDO|null */
    private $connection = null;
    /** @var \PDOStatement */
    private $stmt;

    /**
     * Constructor of this class
     * @param IConnection $connectionImplementation
     */
    public function __construct(IConnection $connectionImplementation)
    {
        $this->connection = $this->connect($connectionImplementation);
    }

    /**
     * Function to start a transaction
     * */
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Function to rollback the transaction
     */
    public function rollBack()
    {
        return $this->connection->rollBack();
    }

    /**
     * Function to commit a transaction
     */
    public function commit()
    {
        return $this->connection->commit();
    }

    /**
     * @param string $query
     * @param array $bind
     * @return PDOStatement
     */
    public function executeQuery(string $query, array $bind = [])
    {
        $this->stmt = $this->connection->prepare($query);
        if ($bind) {
            $this->stmt->execute($bind);
        } else {
            $this->stmt->execute();
        }
        return $this->stmt;
    }

    /**
     * Function to get last inserted id
     * @param mixed $name
     * @return int
     */
    public function getLastInsertedId($name = null): int
    {
        $id = $this->connection->lastInsertId($name);
        return ($id ? $id : false);
    }

   /**
     * Function to get the connection
     * @return null|\PDO
     */
    public function getConnection(): ?\PDO
    {
        return $this->connection;
    }

    /**
     * Function to connect the db.
     * @return \PDO
     * @throws \PDOException
     */
    private function connect(IConnection $connectionImplementation)
    {
        try {
            $DB = new \PDO(
                $connectionImplementation->getDsn(),
                $connectionImplementation->getUsername(),
                $connectionImplementation->getPassword()
            );
            $DB->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            $DB->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            return $DB;
        } catch(\PDOException $e) {
            // Just throw it:
            throw $e;
        }
    }

    /**
     * Function to return the last executed statement if any
     * @return null|PDOStatement
     */ 
    public function getStatement(): ?\PDOStatement
    {
        return $this->stmt;
    }
}
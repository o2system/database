<?php
/**
 * This file is part of the O2System PHP Framework package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author         Steeve Andrian Salim
 * @copyright      Copyright (c) Steeve Andrian Salim
 */
// ------------------------------------------------------------------------

namespace O2System\Database\Interfaces;


interface ConnectionInterface
{
    /**
     * DriverInterface::connect
     *
     * Connect to the database.
     *
     * @param bool $persistent
     *
     * @return resource
     */
    public function connect ( $persistent = true );

    // ------------------------------------------------------------------------

    /**
     * DriverInterface::reconnect
     *
     * Keep or establish the connection if no queries have been sent for
     * a length of time exceeding the server's idle timeout.
     *
     * @return mixed
     */
    public function reconnect ();

    // ------------------------------------------------------------------------

    /**
     * DriverInterface::disconnect
     *
     * Close database connection.
     *
     * @return void
     */
    public function disconnect ();

    // ------------------------------------------------------------------------

    /**
     * DriverInterface::isConnected
     *
     * Determine if the connection is connected
     *
     * @return bool
     */
    public function isConnected ();

    // ------------------------------------------------------------------------

    /**
     * DriverInterface::getConnectionTimeStart
     *
     * Returns the time we started to connect to this database in
     * seconds with microseconds.
     *
     * Used by the Debug Toolbar's timeline.
     *
     * @return float
     */
    public function getConnectTimeStart ();

    // ------------------------------------------------------------------------

    /**
     * DriverInterface::getConnectTimeDuration
     *
     * Returns the number of seconds with microseconds that it took
     * to connect to the database.
     *
     * Used by the Debug Toolbar's timeline.
     *
     * @param int $decimals
     *
     * @return mixed
     */
    public function getConnectTimeDuration ( $decimals = 6 );

    // ------------------------------------------------------------------------

    /**
     * DriverInterface::getQueries
     *
     * Returns Queries Collections
     *
     * @return array
     */
    public function getQueries ();

    // ------------------------------------------------------------------------

    /**
     * DriverInterface::getQueriesCount
     *
     * Returns the total number of queries that have been performed
     * on this connection.
     *
     * @return int
     */
    public function getQueriesCount ();

    // ------------------------------------------------------------------------

    /**
     * DriverInterface::getLastQuery
     *
     * Returns the last query's statement object.
     *
     * @return Query
     */
    public function getLastQuery ();

    // ------------------------------------------------------------------------

    /**
     * DriverInterface::getLastInsertId
     *
     * Get Last Insert ID
     *
     * @return int|string
     */
    public function getLastInsertId ();

    // ------------------------------------------------------------------------

    /**
     * DriverInterface::getAffectedRows
     *
     * Get num of affected rows by INSERT|UPDATE|REPLACE|DELETE execution
     *
     * @return int
     */
    public function getAffectedRows ();

    // ------------------------------------------------------------------------

    /**
     * DriverInterface::execute
     *
     * Execute SQL statement against database.
     *
     * @param string $sqlStatement The SQL statement.
     *
     * @return \O2System\Database\Datastructures\Result
     * @throws \O2System\Kernel\Spl\Exceptions\RuntimeException
     */
    public function execute ( $sqlStatement );

    // ------------------------------------------------------------------------

    /**
     * DriverInterface::query
     *
     * @param string $sqlStatement
     * @param array  $binds
     *
     * @return \O2System\Database\Datastructures\Result|\O2System\Database\Registries\Query
     * @throws \O2System\Kernel\Spl\Exceptions\RuntimeException
     */
    public function query ( $sqlStatement, array $binds = [ ] );

    // ------------------------------------------------------------------------

    /**
     * DriverInterface::transactionBegin
     *
     * Starts a transaction.
     *
     * @return bool
     */
    public function transactionBegin ();

    // ------------------------------------------------------------------------

    /**
     * DriverInterface::transactionCommit
     *
     * Commit a transaction.
     *
     * @return bool
     */
    public function transactionCommit ();

    // ------------------------------------------------------------------------
}
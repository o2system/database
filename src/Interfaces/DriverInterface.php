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

// ------------------------------------------------------------------------

use O2System\DB\Registries\Query;

/**
 * Interface DriverInterface
 *
 * @package O2System\Database\Interfaces
 */
interface DriverInterface
{
    /**
     * DriverInterface::isSupported
     *
     * Check if the platform is supported.
     *
     * @return bool
     */
    public function isSupported ();

    // ------------------------------------------------------------------------

    /**
     * DriverInterface::getPlatform
     *
     * Get the name of the database platform of this connection.
     *
     * @return string The name of the database platform.
     */
    public function getPlatform ();

    // ------------------------------------------------------------------------

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
     * DriverInterface::protectIdentifiers
     *
     * This function is used extensively by the Query Builder class, and by
     * a couple functions in this class.
     * It takes a column or table name (optionally with an alias) and inserts
     * the table prefix onto it. Some logic is necessary in order to deal with
     * column names that include the path. Consider a query like this:
     *
     * SELECT hostname.database.table.column AS c FROM hostname.database.table
     *
     * Or a query with aliasing:
     *
     * SELECT m.member_id, m.member_name FROM members AS m
     *
     * Since the column name can include up to four segments (host, DB, table, column)
     * or also have an alias prefix, we need to do a bit of work to figure this out and
     * insert the table prefix (if it exists) in the proper position, and escape only
     * the correct identifiers.
     *
     * @param    string|array
     * @param    bool
     * @param    mixed
     * @param    bool
     *
     * @return    string
     */
    public function protectIdentifiers (
        $item,
        $prefixSingle = false,
        $protectIdentifiers = null,
        $fieldExists = true
    );

    // ------------------------------------------------------------------------

    /**
     * DriverInterface::escapeIdentifiers
     *
     * Escape the SQL Identifiers
     *
     * This function escapes column and table names
     *
     * @param    mixed
     *
     * @return    mixed
     */
    public function escapeIdentifiers ( $item );

    // ------------------------------------------------------------------------

    /**
     * DriverInterface::escape
     *
     * Escape string
     *
     * @param $string
     *
     * @return int|string
     */
    public function escape ( $string );

    // ------------------------------------------------------------------------

    /**
     * DriverInterface::escapeString
     *
     * Escape String
     *
     * @param string|\string[] $string
     * @param bool             $like
     *
     * @return array|string|\string[]
     */
    public function escapeString ( $string, $like = false );

    // ------------------------------------------------------------------------

    /**
     * DriverInterface::likeString
     *
     * Escape Like String
     *
     * @param $string
     *
     * @return array|string|\string[]
     */
    public function escapeLikeString ( $string );

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

    /**
     * DriverInterface::transactionRollBack
     *
     * RollBack a transaction.
     *
     * @return bool
     */
    public function transactionRollBack ();

    // ------------------------------------------------------------------------

    /**
     * DriverInterface::getTransactionStatus
     *
     * Get transaction status.
     *
     * @return mixed
     */
    public function getTransactionStatus ();

    // ------------------------------------------------------------------------

    /**
     * ActiveRecordInterface::setDatabase
     *
     * Set database table to be used by these connection.
     *
     * @param string $databaseName The database name.
     *
     * @return bool
     */
    public function setDatabase ( $databaseName );

    // ------------------------------------------------------------------------

    /**
     * ActiveRecordInterface::getDatabases
     *
     * Get list of current connection databases.
     *
     * @return array Returns an array contains \O2System\DB\Datastructures\Metadata\Database.
     */
    public function getDatabases ();

    // ------------------------------------------------------------------------

    /**
     * ActiveRecordInterface::isDatabaseExists
     *
     * Check if the database exists or not.
     *
     * @param string $databaseName The database name.
     *
     * @return bool Returns false if database doesn't exists.
     */
    public function isDatabaseExists ( $databaseName );

    // ------------------------------------------------------------------------

    /**
     * ActiveRecordInterface::getTables
     *
     * Get list of current database tables.
     *
     * @return array Returns an array
     * @throws \O2System\Kernel\Spl\Exceptions\RuntimeException
     */
    public function getTables ( $prefixLimit = false );

    /**
     * ActiveRecordInterface::getTableFields
     *
     * @param string $table The database table name.
     *
     * @return array
     * @throws \O2System\Kernel\Spl\Exceptions\RuntimeException
     */
    public function getTableFields ( $table );

    public function getTableFieldsMetadata ( $table );

    /**
     * ActiveRecordInterface::setTablePrefix
     *
     * @param string $tablePrefix The database table prefix.
     *
     * @return string
     */
    public function setTablePrefix ( $tablePrefix );

    // ------------------------------------------------------------------------

    /**
     * ActiveRecordInterface::prefixTable
     *
     * @param string $tableName Database table name.
     *
     * @return string Returns prefixed table name.
     */
    public function prefixTable ( $tableName );

    // ------------------------------------------------------------------------
}
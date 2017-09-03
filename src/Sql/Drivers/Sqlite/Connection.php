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

namespace O2System\Database\Sql\Drivers\Sqlite;

// ------------------------------------------------------------------------

use O2System\Database\Sql\Datastructures\QueryStatement;
use O2System\Spl\Datastructures\SplArrayObject;
use O2System\Spl\Exceptions\RuntimeException;
use O2System\Database\Datastructures\Config;
use O2System\Database\Sql\Abstracts\AbstractConnection;

/**
 * Class Connection
 *
 * @package O2System\Database\Sql\Drivers\Sqlite
 */
class Connection extends AbstractConnection
{
    /**
     * Connection::$isDeleteHack
     *
     * DELETE hack flag
     *
     * Whether to use the Sqlite3 "delete hack" which allows the number
     * of affected rows to be shown. Uses a preg_replace when enabled,
     * adding a bit more processing to all queries.
     *
     * @var    bool
     */
    public $isDeleteHack = false;

    /**
     * Connection::$platform
     *
     * Database driver platform name.
     *
     * @var string
     */
    protected $platform = 'Sqlite3';

    /**
     * Connection::$config
     *
     * Connection configurations.
     *
     * @var Config
     */
    protected $config
        = [
            'escapeCharacter'     => '`',
            'reservedIdentifiers' => [ '*' ],
            'likeEscapeStatement' => ' ESCAPE \'%s\' ',
            'likeEscapeCharacter' => '!',
        ];

    /**
     * Connection::$handle
     *
     * Sqlite Connection Instance.
     *
     * @var \Sqlite3
     */
    protected $handle;

    // ------------------------------------------------------------------------

    /**
     * Connection::isSupported
     *
     * Check if the platform is supported.
     *
     * @return bool
     */
    public function isSupported()
    {
        return extension_loaded( 'Sqlite3' );
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::setDatabase
     *
     * Set a specific database table to use.
     *
     * @param string $database Database name.
     *
     * @return static
     */
    public function setDatabase( $database )
    {
        $database = empty( $database )
            ? $this->database
            : $database;

        if ( is_file( $database ) ) {
            $this->handle->open( $database );
            $this->database = $database;
        }

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::getDatabases
     *
     * Get list of current connection databases.
     *
     * @return array Returns an array.
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function getDatabases()
    {
        $this->queriesResultCache[ 'databaseNames' ][] = pathinfo( $this->database, PATHINFO_FILENAME );

        return $this->queriesResultCache[ 'databaseNames' ];
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::getTables
     *
     * Get list of current database tables.
     *
     * @param bool $prefixLimit If sets TRUE the query will be limit using database table prefix.
     *
     * @return array Returns an array
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function getTables( $prefixLimit = false )
    {
        if ( empty( $this->queriesResultCache[ 'tableNames' ] ) ) {

            $SqlStatement = 'SELECT "NAME" FROM "SqlITE_MASTER" WHERE "TYPE" = \'table\'';

            if ( $prefixLimit !== false && $this->config[ 'tablePrefix' ] !== '' ) {
                $SqlStatement .= ' AND "NAME" LIKE \'' . $this->escapeLikeString( $this->config[ 'tablePrefix' ] ) . "%' ";
            }

            $result = $this->query( $SqlStatement );

            if ( $result->count() ) {
                foreach ( $result as $row ) {
                    // Do we know from which column to get the table name?
                    if ( ! isset( $key ) ) {
                        if ( isset( $row[ 'table_name' ] ) ) {
                            $key = 'table_name';
                        } elseif ( isset( $row[ 'TABLE_NAME' ] ) ) {
                            $key = 'TABLE_NAME';
                        } else {
                            /* We have no other choice but to just get the first element's key.
                             * Due to array_shift() accepting its argument by reference, if
                             * E_STRICT is on, this would trigger a warning. So we'll have to
                             * assign it first.
                             */
                            $key = array_keys( $row->getArrayCopy() );
                            $key = array_shift( $key );
                        }
                    }

                    $this->queriesResultCache[ 'tableNames' ][] = $row->offsetGet( $key );
                }
            }
        }

        return $this->queriesResultCache[ 'tableNames' ];
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractConnection::getColumns
     *
     * @param string $table The database table name.
     *
     * @return array
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function getColumns( $table )
    {
        $table = $this->prefixTable( $table );

        if ( empty( $this->queriesResultCache[ 'tableColumns' ][ $table ] ) ) {
            $result = $this->query( 'PRAGMA TABLE_INFO(' . $this->protectIdentifiers( $table, true, null, false ) . ')' );

            if ( $result->count() ) {
                foreach ( $result as $row ) {
                    // Do we know from where to get the column's name?
                    if ( ! isset( $key ) ) {
                        if ( isset( $row[ 'name' ] ) ) {
                            $key = 'name';
                        }
                    }

                    $this->queriesResultCache[ 'tableColumns' ][ $table ][ $row->offsetGet( $key ) ] = $row;
                }
            }
        }

        return $this->queriesResultCache[ 'tableColumns' ][ $table ];
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::platformGetPlatformVersionHandler
     *
     * Platform getting version handler.
     *
     * @return SplArrayObject
     */
    protected function platformGetPlatformInfoHandler()
    {
        return new SplArrayObject( \Sqlite3::version() );
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::platformConnectHandler
     *
     * Establish the connection.
     *
     * @param Config $config
     *
     * @return void
     * @throws RuntimeException
     */
    protected function platformConnectHandler( Config $config )
    {
        $this->database = $config->database;

        if ( $config->readOnly === true ) {
            $this->handle = new \Sqlite3( $config->database, SqlITE3_OPEN_READONLY );
        } else {
            $this->handle = new \Sqlite3( $config->database, SqlITE3_OPEN_READWRITE | SqlITE3_OPEN_CREATE );
        }

        // Enable throwing exceptions
        $this->handle->enableExceptions( true );

        // Set busy timeout
        if ( $config->offsetExists( 'busyTimeout' ) ) {
            if ( $config->busyTimeout != 0 ) {
                $this->handle->busyTimeout( $config->busyTimeout );
            }
        }
    }

    //--------------------------------------------------------------------

    /**
     * Connection::executeHandler
     *
     * Driver dependent way method for execute the Sql statement.
     *
     * @param QueryStatement $queryStatement Query object.
     *
     * @return bool
     */
    protected function platformExecuteHandler( QueryStatement &$queryStatement )
    {
        if ( false !== $this->handle->exec( $queryStatement->getSqlFinalStatement() ) ) {
            return true;
        }

        // Set query error information
        $queryStatement->setError( $this->handle->lastErrorCode(), $this->handle->lastErrorMsg() );

        return false;

    }

    // ------------------------------------------------------------------------

    /**
     * Connection::platformQueryHandler
     *
     * Driver dependent way method for execute the Sql statement.
     *
     * @param QueryStatement $queryStatement Query object.
     *
     * @return array
     */
    protected function platformQueryHandler( QueryStatement &$queryStatement )
    {
        $rows = [];

        if ( false !== ( $result = $this->handle->query( $queryStatement->getSqlFinalStatement() ) ) ) {
            $i = 0;
            while ( $row = $result->fetchArray( SqlITE3_ASSOC ) ) {
                $rows[ $i ] = $row;
                $i++;
            }
        } else {
            $queryStatement->setError( $this->handle->lastErrorCode(), $this->handle->lastErrorMsg() );
        }

        return $rows;
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::disconnectHandler
     *
     * Driver dependent way method for closing the connection.
     *
     * @return mixed
     */
    protected function platformDisconnectHandler()
    {
        $this->handle->close();
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::getAffectedRows
     *
     * Get the total number of affected rows from the last query execution.
     *
     * @return int  Returns total number of affected rows
     */
    public function getAffectedRows()
    {
        return $this->handle->changes();
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::getLastInsertId
     *
     * Get last insert id from the last insert query execution.
     *
     * @return int  Returns total number of affected rows
     */
    public function getLastInsertId()
    {
        return $this->handle->lastInsertRowID();
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::platformTransactionBeginHandler
     *
     * Platform beginning a transaction handler.
     *
     * @return bool
     */
    protected function platformTransactionBeginHandler()
    {
        $this->transactionInProgress = true;

        return $this->handle->exec( 'BEGIN;' );
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::platformTransactionCommitHandler
     *
     * Platform committing a transaction handler.
     *
     * @return bool
     */
    protected function platformTransactionCommitHandler()
    {
        if ( $this->handle->exec( 'COMMIT;' ) ) {
            $this->transactionInProgress = false;

            return true;
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::platformTransactionRollBackHandler
     *
     * Platform rolling back a transaction handler.
     *
     * @return bool
     */
    protected function platformTransactionRollBackHandler()
    {
        if ( $this->handle->exec( 'ROLLBACK;' ) ) {
            $this->transactionInProgress = false;

            return true;
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::prepareSqlStatement
     *
     * Platform preparing a Sql statement.
     *
     * @param string $SqlStatement Sql Statement to be prepared.
     * @param array  $options      Preparing Sql statement options.
     *
     * @return string
     */
    protected function platformPrepareSqlStatement( $SqlStatement, array $options = [] )
    {
        // Sqlite3::changes() returns 0 for "DELETE FROM TABLE" queries. This hack
        // modifies the query so that it a proper number of affected rows is returned.
        if ( $this->isDeleteHack === true && preg_match( '/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $SqlStatement ) ) {
            return trim( $SqlStatement ) . ' WHERE 1=1';
        }

        return $SqlStatement;
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::platformEscapeStringHandler
     *
     * Platform escape string handler.
     *
     * @param string $string
     *
     * @return string
     */
    protected function platformEscapeStringHandler( $string )
    {

        return \Sqlite3::escapeString( $string );
    }
}
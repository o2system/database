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

namespace O2System\Database\NoSQL\Drivers\MongoDB;

// ------------------------------------------------------------------------

use O2System\Database\NoSQL\Datastructures\QueryStatement;
use O2System\Spl\Datastructures\SplArrayObject;
use O2System\Spl\Exceptions\RuntimeException;
use O2System\Database\Datastructures\Config;
use O2System\Database\NoSQL\Abstracts\AbstractConnection;

/**
 * Class Connection
 *
 * @package O2System\Database\NoSQL\Drivers\MongoDB
 */
class Connection extends AbstractConnection
{
    /**
     * Connection::$platform
     *
     * Database driver platform name.
     *
     * @var string
     */
    protected $platform = 'MongoDB';

    /**
     * Connection::$handle
     *
     * MongoDB Connection Instance.
     *
     * @var \MongoDB\Driver\Manager
     */
    public $handle;

    /**
     * Connection::$server
     *
     * MongoDB Server Instance.
     *
     * @var \MongoDB\Driver\Server
     */
    public $server;

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
        return extension_loaded( 'mongodb' );
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
        $this->database = $database;

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::getDatabases
     *
     * Get list of current connection databases.
     *
     * @return array
     */
    public function getDatabases()
    {
        $cursor = $this->server->executeCommand( 'admin', new \MongoDB\Driver\Command( [ 'listDatabases' => 1 ] ) );

        $cursor->setTypeMap( [ 'root' => 'array', 'document' => 'array' ] );
        $result = current( $cursor->toArray() );

        $this->queriesResultCache[ 'databaseNames' ] = [];

        if ( ! empty( $result[ 'databases' ] ) ) {
            foreach ( $result[ 'databases' ] as $database ) {
                if ( ! in_array( $database[ 'name' ], [ 'admin', 'local' ] ) ) {
                    $this->queriesResultCache[ 'databaseNames' ][] = $database[ 'name' ];
                }
            }
        }

        return $this->queriesResultCache[ 'databaseNames' ];
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::getCollections
     *
     * Get list of current database collections.
     *
     * @return array Returns an array
     */
    public function getCollections()
    {
        $cursor = $this->server->executeCommand( $this->database,
            new \MongoDB\Driver\Command( [ 'listCollections' => 1 ] ) );
        $cursor->setTypeMap( [ 'root' => 'array', 'document' => 'array' ] );

        $result = new \IteratorIterator( $cursor );

        $this->queriesResultCache[ 'collectionNames' ] = [];

        foreach ( $result as $collection ) {
            if ( $collection[ 'type' ] === 'collection' ) {
                $this->queriesResultCache[ 'collectionNames' ][] = $collection[ 'name' ];
            }
        }

        return $this->queriesResultCache[ 'collectionNames' ];
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::getKeys
     *
     * @param string $collection The database collection name.
     *
     * @return array
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function getKeys( $collection )
    {
        $cursor = $this->server->executeQuery( $this->database . '.' . $collection,
            new \MongoDB\Driver\Query( [], [ 'limit' => 1 ] ) );

        $result = current( $cursor->toArray() );

        $this->queriesResultCache[ 'collectionKeys' ][ $collection ] = [];

        foreach ( get_object_vars( $result ) as $key => $value ) {
            $this->queriesResultCache[ 'collectionKeys' ][ $collection ][] = $key;
        }

        return $this->queriesResultCache[ 'collectionKeys' ][ $collection ];
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::getIndexes
     *
     * @param string $collection The database collection name.
     *
     * @return array
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function getIndexes( $collection )
    {
        $cursor = $this->server->executeCommand( $this->database,
            new \MongoDB\Driver\Command( [ 'listIndexes' => $collection ] ) );
        $cursor->setTypeMap( [ 'root' => 'array', 'document' => 'array' ] );

        $result = new \IteratorIterator( $cursor );

        $this->queriesResultCache[ 'collectionIndexes' ][ $collection ] = [];

        foreach ( $result as $index ) {
            $this->queriesResultCache[ 'collectionIndexes' ][ $collection ][] = $index[ 'name' ];
        }

        return $this->queriesResultCache[ 'collectionIndexes' ][ $collection ];
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
        $metadata = $this->server->getInfo();
        $metadata[ 'latency' ] = $this->server->getLatency();
        $metadata[ 'type' ] = $this->server->getType();
        $metadata[ 'tags' ] = $this->server->getTags();

        return new SplArrayObject( [
            'name'     => $this->getPlatform(),
            'host'     => $this->server->getHost(),
            'port'     => $this->server->getPort(),
            'metadata' => $metadata,
        ] );
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
        $hostname = empty( $config->hostname ) ? 'localhost' : $config->hostname;
        $port = empty( $config->port ) ? 27071 : $config->port;
        $this->database = $config->database;

        $this->handle = new \MongoDB\Driver\Manager( 'mongodb://' . $hostname . ':' . $port );
        $this->server = $this->handle->selectServer( new \MongoDB\Driver\ReadPreference( \MongoDB\Driver\ReadPreference::RP_PRIMARY ) );
    }

    //--------------------------------------------------------------------

    /**
     * Connection::platformDisconnectHandler
     *
     * Driver dependent way method for closing the connection.
     *
     * @return mixed
     */
    protected function platformDisconnectHandler()
    {
        $this->server = null;
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::platformExecuteHandler
     *
     * Driver dependent way method for execute the SQL statement.
     *
     * @param QueryStatement $queryStatement Query object.
     *
     * @return bool
     */
    protected function platformExecuteHandler( QueryStatement &$queryStatement, array $options = [] )
    {
        $this->parseQueryStatement( $queryStatement );

        if ( isset( $options[ 'method' ] ) ) {

            $method = $options[ 'method' ];
            unset( $options[ 'method' ] );

            $options = array_merge( [ 'safe' => true, 'ordered' => true ], $options );
            $bulk = new \MongoDB\Driver\BulkWrite( $options );
            $documents = $queryStatement->getDocument();

            if ( is_numeric( key( $documents ) ) ) { // batch process
                foreach ( $documents as $document ) {
                    switch ( $method ) {
                        case 'insert':
                            $this->lastInsertId = $bulk->insert( $document );
                            break;
                        case 'update':
                            $this->lastInsertId = $bulk->update( $queryStatement->getFilter(),
                                $document, $queryStatement->getOptions() );
                            break;
                        case 'delete':
                            $this->lastInsertId = $bulk->delete( $queryStatement->getFilter(),
                                $queryStatement->getOptions() );
                            break;
                    }
                }
            } else {
                switch ( $method ) {
                    case 'insert':
                        $this->lastInsertId = $bulk->insert( $documents );
                        break;
                    case 'update':
                        $this->lastInsertId = $bulk->update( $queryStatement->getFilter(),
                            $documents, $queryStatement->getOptions() );
                        break;
                    case 'delete':
                        $this->lastInsertId = $bulk->delete( $queryStatement->getFilter(),
                            $queryStatement->getOptions() );
                        break;
                }
            }

            try {
                $result = $this->handle->executeBulkWrite( $this->database . '.' . $queryStatement->getCollection(),
                    $bulk );

                $this->lastUpsertedIds = $result->getUpsertedIds();

                switch ( $method ) {
                    case 'insert':
                        $this->affectedDocuments = $result->getInsertedCount();
                        break;
                    case 'update':
                        $this->affectedDocuments = $result->getModifiedCount();
                        break;
                    case 'delete':
                        $this->affectedDocuments = $result->getDeletedCount();
                        break;
                }

                return true;
            } catch ( \MongoDB\Driver\Exception\BulkWriteException $e ) {
                $queryStatement->setError( $e->getCode(), $e->getMessage() );
            }
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * Connection::platformQueryHandler
     *
     * Driver dependent way method for execute the SQL statement.
     *
     * @param QueryStatement $queryStatement Query object.
     *
     * @return array
     */
    protected function platformQueryHandler( QueryStatement &$queryStatement )
    {
        $this->parseQueryStatement( $queryStatement );

        $cursor = $this->server->executeQuery( $this->database . '.' . $queryStatement->getCollection(),
            new \MongoDB\Driver\Query( $queryStatement->getFilter(), $queryStatement->getOptions() ) );

        return $cursor->toArray();
    }

    // ------------------------------------------------------------------------

    protected function parseQueryStatement( QueryStatement &$queryStatement )
    {
        $queryBuilderCache = $queryStatement->getBuilderCache();

        // Projection Option
        if ( count( $queryBuilderCache->select ) ) {

            $projection = [];

            foreach ( $queryBuilderCache->select as $field ) {
                $projection[ $field ] = 1;
            }

            $queryStatement->addOption( 'projection', $projection );
        }

        // Filter Where In
        if ( count( $queryBuilderCache->whereIn ) ) {
            foreach ( $queryBuilderCache->whereIn as $field => $clause ) {
                if ( count( $queryBuilderCache->orWhereIn ) ) {
                    $queryBuilderCache->orWhere[ $field ] = [ '$in' => $clause ];
                } else {
                    $queryBuilderCache->where[ $field ] = [ '$in' => $clause ];
                }
            }
        }

        // Filter Where Not In
        if ( count( $queryBuilderCache->whereNotIn ) ) {
            foreach ( $queryBuilderCache->whereNotIn as $field => $clause ) {
                $queryBuilderCache->where[ $field ] = [ '$nin' => $clause ];
            }
        }

        // Filter Or Where In
        if ( count( $queryBuilderCache->orWhereIn ) ) {
            foreach ( $queryBuilderCache->orWhereIn as $field => $clause ) {
                $queryBuilderCache->orWhere[ $field ] = [ '$in' => $clause ];
            }
        }

        // Filter Or Where Not In
        if ( count( $queryBuilderCache->orWhereNotIn ) ) {
            foreach ( $queryBuilderCache->orWhereNotIn as $field => $clause ) {
                $queryBuilderCache->orWhere[ $field ] = [ '$nin' => $clause ];
            }
        }

        // Filter Where Between
        if ( count( $queryBuilderCache->between ) ) {
            foreach ( $queryBuilderCache->between as $field => $clause ) {
                $queryBuilderCache->where[ $field ] = [ '$gte' => $clause[ 'start' ], '$lte' => $clause[ 'end' ] ];
            }
        }

        // Filter Or Where Between
        if ( count( $queryBuilderCache->orBetween ) ) {
            foreach ( $queryBuilderCache->orBetween as $field => $clause ) {
                $queryBuilderCache->orWhere[ $field ] = [ '$gte' => $clause[ 'start' ], '$lte' => $clause[ 'end' ] ];
            }
        }

        // Filter Where Not Between
        if ( count( $queryBuilderCache->notBetween ) ) {
            foreach ( $queryBuilderCache->notBetween as $field => $clause ) {
                $queryBuilderCache->where[ $field ][ '$not' ] = [
                    '$gte' => $clause[ 'start' ],
                    '$lte' => $clause[ 'end' ],
                ];
            }
        }

        // Filter Or Where Not Between
        if ( count( $queryBuilderCache->orNotBetween ) ) {
            foreach ( $queryBuilderCache->orNotBetween as $field => $clause ) {
                $queryBuilderCache->orWhere[ $field ][ '$not' ] = [
                    '$gte' => $clause[ 'start' ],
                    '$lte' => $clause[ 'end' ],
                ];
            }
        }

        // Filter Where
        if ( count( $queryBuilderCache->where ) ) {
            foreach ( $queryBuilderCache->where as $field => $clause ) {
                $queryStatement->addFilter( $field, $clause );
            }
        }

        // Filter Or Where
        if ( count( $queryBuilderCache->orWhere ) ) {
            $orWhere = [];
            foreach ( $queryBuilderCache->orWhere as $field => $clause ) {
                $orWhere[] = [ $field => $clause ];
            }

            $queryStatement->addFilter( '$or', $orWhere );
        }

        //print_out( json_encode( $queryStatement->getFilter(), JSON_PRETTY_PRINT ) );

        // Limit Option
        if ( $queryBuilderCache->limit > 0 ) {
            $queryStatement->addOption( 'limit', $queryBuilderCache->limit );
        }

        // Offset Option
        if ( $queryBuilderCache->offset > 0 ) {
            $queryStatement->addOption( 'skip', $queryBuilderCache->offset );
        }

        // Order Option
        if ( count( $queryBuilderCache->orderBy ) ) {

            $sort = [];
            foreach ( $queryBuilderCache->orderBy as $field => $direction ) {
                $direction = $direction === 'ASC' ? 1 : -1;
                $sort[ $field ] = $direction;
            }

            $queryStatement->addOption( 'sort', $sort );
        }

        return $queryStatement;
    }

    /**
     * Connection::getAffectedDocuments
     *
     * Get the total number of affected rows from the last query execution.
     *
     * @return int  Returns total number of affected rows
     */
    public function getAffectedDocuments()
    {
        return $this->affectedDocuments;
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
        return $this->lastInsertId;
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
        $this->handle->autocommit( false );
        $this->transactionInProgress = true;

        return $this->handle->begin_transaction( MYSQLI_TRANS_START_READ_WRITE );
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
        if ( $this->handle->commit() ) {
            $this->handle->autocommit( ( $this->transactionInProgress ? false : true ) );

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
        if ( $this->handle->rollback() ) {
            $this->transactionInProgress = false;
            $this->handle->autocommit( true );

            return true;
        }

        return false;
    }
}
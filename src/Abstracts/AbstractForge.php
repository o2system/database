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

namespace O2System\Database\Abstracts;

// ------------------------------------------------------------------------
use O2System\Spl\Exceptions\Logic\BadFunctionCall\BadMethodCallException;
use O2System\Spl\Exceptions\RuntimeException;

/**
 * Class AbstractForge
 *
 * @package O2System\Database\Abstracts
 */
abstract class AbstractForge
{
    /**
     * AbstractForge::$conn
     *
     * Forge database connection instance.
     *
     * @var AbstractConnection
     */
    protected $conn;

    /**
     * AbstractForge::$isCreateTableKeys
     *
     * CREATE TABLE keys flag
     *
     * Whether table keys are created from within the
     * CREATE TABLE statement.
     *
     * @var bool
     */
    protected $isCreateTableKeys;

    /**
     * AbstractForge::$unsigned
     *
     * @var bool
     */
    protected $unsigned = true;

    /**
     * AbstractForge::$null
     *
     * @var string
     */
    protected $null = '';

    /**
     * AbstractForge::$default
     *
     * @var string
     */
    protected $default = ' DEFAULT ';

    /**
     * AbstractForge::$builderCache
     *
     * Forge builder variables cache.
     *
     * @var array
     */
    protected $builderCache
        = [
            'table'       => null,
            'fields'      => [],
            'keys'        => [],
            'primaryKeys' => [],
        ];

    // ------------------------------------------------------------------------

    /**
     * AbstractForge::__construct.
     *
     * @param \O2System\Database\Abstracts\AbstractConnection $conn
     */
    public function __construct( AbstractConnection $conn )
    {
        $this->conn = $conn;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractForge::createDatabase
     *
     * Create database.
     *
     * @param string $database Database name.
     *
     * @return bool
     * @throws \O2System\Spl\Exceptions\Logic\BadFunctionCall\BadMethodCallException
     */
    public function createDatabase( $database, array $options = [] )
    {
        if ( false !== ( $sqlStatement = $this->platformCreateDatabaseStatement( $database, $options ) ) ) {

            if ( $this->conn->execute( $sqlStatement ) ) {
                $this->conn->queriesResultCache[ 'databaseNames' ][] = $database;
            }
        }

        if ( $this->conn->debugEnabled ) {
            // This feature is not available for the database you are using.'
            throw new BadMethodCallException( 'E_DATABASE_FEATURE_UNAVAILABLE' );
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractForge::platformCreateDatabaseStatement
     *
     * Generates a platform-specific CREATE DATABASE statement.
     *
     * @param string $database Database name.
     * @param array  $options  Create database options.
     *
     * @return string|bool
     */
    abstract protected function platformCreateDatabaseStatement( $database, array $options = [] );

    // ------------------------------------------------------------------------

    /**
     * AbstractForge::dropDatabase
     *
     * Drop database.
     *
     * @param $database
     *
     * @return bool
     * @throws \O2System\Spl\Exceptions\Logic\BadFunctionCall\BadMethodCallException
     */
    public function dropDatabase( $database )
    {
        if ( false !== ( $sqlStatement = $this->platformDropDatabaseStatement( $database ) ) ) {

            if ( $this->conn->execute( $sqlStatement ) ) {
                if ( $key = array_search( $database, $this->conn->queriesResultCache[ 'databaseNames' ] ) ) {
                    unset( $this->conn->queriesResultCache[ 'databaseNames' ][ $key ] );
                }
            }
        }

        if ( $this->conn->debugEnabled ) {
            // This feature is not available for the database you are using.'
            throw new BadMethodCallException( 'E_DATABASE_FEATURE_UNAVAILABLE' );
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractForge::platformDropDatabaseStatement
     *
     * Generates a platform-specific DROP DATABASE statement.
     *
     * @param string $database Database name.
     *
     * @return string|bool
     */
    abstract protected function platformDropDatabaseStatement( $database );

    // ------------------------------------------------------------------------

    /**
     * AbstractForge::addFields
     *
     * Add database table fields.
     *
     * @param array $fields List of database table field names.
     *
     * @return static
     */
    public function addFields( array $fields )
    {
        foreach ( $fields as $field ) {
            $this->addField( $field );
        }

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractForge::addField
     *
     * Add database table field.
     *
     * @param string $field Database table field name.
     *
     * @return static
     */
    public function addField( $field )
    {
        if ( is_string( $field ) ) {
            if ( $field === 'id' ) {
                $this->addField(
                    [
                        'id' => [
                            'type'           => 'INT',
                            'constraint'     => 9,
                            'auto_increment' => true,
                        ],
                    ]
                );
                $this->addKey( 'id', true );
            } else {
                if ( strpos( $field, ' ' ) === false ) {
                    throw new \InvalidArgumentException( 'Input information is required for that operation.' );
                }

                $this->builderCache[ 'fields' ][] = $field;
            }
        }

        if ( is_array( $field ) ) {
            $this->builderCache[ 'fields' ] = array_merge( $this->builderCache[ 'fields' ], $field );
        }

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractForge::addKey
     *
     * Add database table key column.
     *
     * @param string $key       Database table key name.
     * @param bool   $isPrimary Whether is table primary key or not.
     *
     * @return static
     */
    public function addKey( $key, $isPrimary = false )
    {
        if ( is_array( $key ) ) {
            foreach ( $key as $one ) {
                $this->addKey( $one, $isPrimary );
            }

            return $this;
        }

        if ( $isPrimary === true ) {
            $this->builderCache[ 'primaryKeys' ][] = $key;
        } else {
            $this->builderCache[ 'keys' ][] = $key;
        }

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractForge::createTable
     *
     * Create database table.
     *
     * @param string $table      Database table name.
     * @param array  $attributes Associative array of table attributes.
     *
     * @return bool
     * @throws \O2System\Spl\Exceptions\RuntimeException
     * @throws \O2System\Spl\Exceptions\Logic\BadFunctionCall\BadMethodCallException
     */
    public function createTable( $table, array $attributes = [] )
    {
        $table = $this->conn->prefixTable( $table );

        if ( $this->conn->isTableExists( $table ) ) {
            if ( $this->conn->debugEnabled ) {
                throw new RuntimeException( 'E_DATABASE_TABLE_ALREADY_EXISTS' );
            }

            return false;
        }

        if ( false !== ( $sqlStatement = $this->platformCreateTableStatement(
                $this->conn->escapeIdentifiers( $table ),
                $this->compileCreateTableColumnsStatement( $table ),
                $this->compileCreateTableAttributesStatement( $attributes )
            ) )
        ) {

            if ( $this->conn->execute( $sqlStatement ) ) {
                $this->conn->queriesResultCache[ 'tableNames' ][] = $table;
            }
        }

        if ( $this->conn->debugEnabled ) {
            // This feature is not available for the database you are using.'
            throw new BadMethodCallException( 'E_DATABASE_FEATURE_UNAVAILABLE' );
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractForge::platformCreateTableStatement
     *
     * Generates a platform-specific CREATE TABLE statement.
     *
     * @param string $table      Database table name.
     * @param string $columns    Database table columns SQL statement portion.
     * @param string $attributes Database table attributes SQL statement portion.
     *
     * @return string|bool
     */
    abstract protected function platformCreateTableStatement( $table, $columns, $attributes );

    // ------------------------------------------------------------------------

    /**
     * AbstractForge::compileCreateTableColumnsStatement
     *
     * @param string $table Database table name.
     *
     * @return string
     */
    protected function compileCreateTableColumnsStatement( $table )
    {
        $columns = $this->processFields( true );

        for ( $i = 0, $totalColumns = count( $columns ); $i < $totalColumns; $i++ ) {
            $columns[ $i ] = ( $columns[ $i ][ '_literal' ] !== false )
                ? "\n\t" . $columns[ $i ][ '_literal' ]
                : "\n\t" . $this->processColumn( $columns[ $i ] );
        }

        $sqlColumnsStatement = implode( ',', $columns )
            . $this->processPrimaryKeys( $table );

        // Are indexes created from within the CREATE TABLE statement? (e.g. in MySQL)
        if ( $this->isCreateTableKeys === true ) {
            $sqlColumnsStatement .= $this->processIndexes( $table );
        }

        return $sqlColumnsStatement;
    }

    // ------------------------------------------------------------------------

    protected function processFields( $isCreateTable = false )
    {
        $fields = [];

        foreach ( $this->builderCache[ 'fields' ] as $key => $attributes ) {
            if ( is_int( $key ) && ! is_array( $attributes ) ) {
                $fields[] = [ '_literal' => $attributes ];
                continue;
            }

            $attributes = array_change_key_case( $attributes, CASE_UPPER );

            if ( $isCreateTable === true && empty( $attributes[ 'TYPE' ] ) ) {
                continue;
            }

            isset( $attributes[ 'TYPE' ] ) && $this->setAttributeType( $attributes );

            $field = [
                'name'           => $key,
                'new_name'       => isset( $attributes[ 'NAME' ] )
                    ? $attributes[ 'NAME' ]
                    : null,
                'type'           => isset( $attributes[ 'TYPE' ] )
                    ? $attributes[ 'TYPE' ]
                    : null,
                'length'         => '',
                'unsigned'       => '',
                'null'           => '',
                'unique'         => '',
                'default'        => '',
                'auto_increment' => '',
                '_literal'       => false,
            ];

            isset( $attributes[ 'TYPE' ] ) && $this->setAttributeUnsigned( $attributes, $field );

            if ( $isCreateTable === false ) {
                if ( isset( $attributes[ 'AFTER' ] ) ) {
                    $field[ 'after' ] = $attributes[ 'AFTER' ];
                } elseif ( isset( $attributes[ 'FIRST' ] ) ) {
                    $field[ 'first' ] = (bool)$attributes[ 'FIRST' ];
                }
            }

            $this->setAttributeDefault( $attributes, $field );

            if ( isset( $attributes[ 'NULL' ] ) ) {
                if ( $attributes[ 'NULL' ] === true ) {
                    $field[ 'null' ] = empty( $this->null )
                        ? ''
                        : ' ' . $this->null;
                } else {
                    $field[ 'null' ] = ' NOT NULL';
                }
            } elseif ( $isCreateTable === true ) {
                $field[ 'null' ] = ' NOT NULL';
            }

            $this->setAttributeAutoIncrement( $attributes, $field );
            $this->setAttributeUnique( $attributes, $field );

            if ( isset( $attributes[ 'COMMENT' ] ) ) {
                $field[ 'comment' ] = $this->conn->escape( $attributes[ 'COMMENT' ] );
            }

            if ( isset( $attributes[ 'TYPE' ] ) && ! empty( $attributes[ 'CONSTRAINT' ] ) ) {
                switch ( strtoupper( $attributes[ 'TYPE' ] ) ) {
                    case 'ENUM':
                    case 'SET':
                        $attributes[ 'CONSTRAINT' ] = $this->conn->escape( $attributes[ 'CONSTRAINT' ] );
                        $field[ 'length' ] = is_array( $attributes[ 'CONSTRAINT' ] )
                            ? "('" . implode( "','", $attributes[ 'CONSTRAINT' ] ) . "')"
                            : '(' . $attributes[ 'CONSTRAINT' ] . ')';
                        break;
                    default:
                        $field[ 'length' ] = is_array( $attributes[ 'CONSTRAINT' ] )
                            ? '(' . implode( ',', $attributes[ 'CONSTRAINT' ] ) . ')'
                            : '(' . $attributes[ 'CONSTRAINT' ] . ')';
                        break;
                }
            }

            $fields[] = $field;
        }

        return $fields;
    }

    // ------------------------------------------------------------------------

    /**
     * Input attribute TYPE
     *
     * Performs a data type mapping between different databases.
     *
     * @param    array &$attributes
     *
     * @return    void
     */
    protected function setAttributeType( &$attributes )
    {
        // Usually overridden by drivers
    }

    // ------------------------------------------------------------------------

    /**
     * Input attribute UNSIGNED
     *
     * Depending on the unsigned property value:
     *
     *    - TRUE will always set $field['unsigned'] to 'UNSIGNED'
     *    - FALSE will always set $field['unsigned'] to ''
     *    - array(TYPE) will set $field['unsigned'] to 'UNSIGNED',
     *        if $attributes['TYPE'] is found in the array
     *    - array(TYPE => UTYPE) will change $field['type'],
     *        from TYPE to UTYPE in case of a match
     *
     * @param    array &$attributes
     * @param    array &$field
     *
     * @return    void
     */
    protected function setAttributeUnsigned( &$attributes, &$field )
    {
        if ( empty( $attributes[ 'UNSIGNED' ] ) OR $attributes[ 'UNSIGNED' ] !== true ) {
            return;
        }

        // Reset the attribute in order to avoid issues if we do type conversion
        $attributes[ 'UNSIGNED' ] = false;

        if ( is_array( $this->unsigned ) ) {
            foreach ( array_keys( $this->unsigned ) as $key ) {
                if ( is_int( $key ) && strcasecmp( $attributes[ 'TYPE' ], $this->unsigned[ $key ] ) === 0 ) {
                    $field[ 'unsigned' ] = ' UNSIGNED';

                    return;
                } elseif ( is_string( $key ) && strcasecmp( $attributes[ 'TYPE' ], $key ) === 0 ) {
                    $field[ 'type' ] = $key;

                    return;
                }
            }

            return;
        }

        $field[ 'unsigned' ] = ( $this->unsigned === true )
            ? ' UNSIGNED'
            : '';
    }

    // ------------------------------------------------------------------------

    /**
     * Input attribute DEFAULT
     *
     * @param    array &$attributes
     * @param    array &$field
     *
     * @return    void
     */
    protected function setAttributeDefault( &$attributes, &$field )
    {
        if ( $this->default === false ) {
            return;
        }

        if ( array_key_exists( 'DEFAULT', $attributes ) ) {
            if ( $attributes[ 'DEFAULT' ] === null ) {
                $field[ 'default' ] = empty( $this->null )
                    ? ''
                    : $this->default . $this->null;

                // Override the NULL attribute if that's our default
                $attributes[ 'NULL' ] = true;
                $field[ 'null' ] = empty( $this->null )
                    ? ''
                    : ' ' . $this->null;
            } else {
                $field[ 'default' ] = $this->default . $this->conn->escape( $attributes[ 'DEFAULT' ] );
            }
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Input attribute AUTO_INCREMENT
     *
     * @param    array &$attributes
     * @param    array &$field
     *
     * @return    void
     */
    protected function setAttributeAutoIncrement( &$attributes, &$field )
    {
        if ( ! empty( $attributes[ 'AUTO_INCREMENT' ] ) && $attributes[ 'AUTO_INCREMENT' ] === true
            && stripos( $field[ 'type' ], 'int' ) !== false
        ) {
            $field[ 'auto_increment' ] = ' AUTO_INCREMENT';
        }
    }

    //--------------------------------------------------------------------

    /**
     * Input attribute UNIQUE
     *
     * @param    array &$attributes
     * @param    array &$field
     *
     * @return    void
     */
    protected function setAttributeUnique( &$attributes, &$field )
    {
        if ( ! empty( $attributes[ 'UNIQUE' ] ) && $attributes[ 'UNIQUE' ] === true ) {
            $field[ 'unique' ] = ' UNIQUE';
        }
    }

    //--------------------------------------------------------------------

    /**
     * AbstractForge::processColumn
     *
     * Process database table column definitions.
     *
     * @param array $column Input definitions.
     *
     * @return string
     */
    protected function processColumn( $column )
    {
        return $this->conn->escapeIdentifiers( $column[ 'name' ] )
            . ' ' . $column[ 'type' ] . $column[ 'length' ]
            . $column[ 'unsigned' ]
            . $column[ 'default' ]
            . $column[ 'null' ]
            . $column[ 'auto_increment' ]
            . $column[ 'unique' ];
    }

    // ------------------------------------------------------------------------

    /**
     * Process primary keys
     *
     * @param    string $table Table name
     *
     * @return    string
     */
    protected function processPrimaryKeys( $table )
    {
        $sqlStatement = '';

        for ( $i = 0, $c = count( $this->builderCache[ 'primaryKeys' ] ); $i < $c; $i++ ) {
            if ( ! isset( $this->builderCache[ 'fields' ][ $this->builderCache[ 'primaryKeys' ][ $i ] ] ) ) {
                unset( $this->builderCache[ 'primaryKeys' ][ $i ] );
            }
        }

        if ( count( $this->builderCache[ 'primaryKeys' ] ) > 0 ) {
            $sqlStatement .= ",\n\tCONSTRAINT " . $this->conn->escapeIdentifiers( 'pk_' . $table )
                . ' PRIMARY KEY(' . implode(
                    ', ',
                    $this->conn->escapeIdentifiers( $this->builderCache[ 'primaryKeys' ] )
                ) . ')';
        }

        return $sqlStatement;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractForge::processIndexes
     *
     * Process database table column indexes.
     *
     * @param   string $table Database table name.
     *
     * @return  string
     */
    protected function processIndexes( $table )
    {
        $sqlStatements = [];

        for ( $i = 0, $c = count( $this->builderCache[ 'keys' ] ); $i < $c; $i++ ) {
            if ( is_array( $this->builderCache[ 'keys' ][ $i ] ) ) {
                for ( $i2 = 0, $c2 = count( $this->builderCache[ 'keys' ][ $i ] ); $i2 < $c2; $i2++ ) {
                    if ( ! isset( $this->builderCache[ 'fields' ][ $this->builderCache[ 'keys' ][ $i ][ $i2 ] ] ) ) {
                        unset( $this->builderCache[ 'keys' ][ $i ][ $i2 ] );
                        continue;
                    }
                }
            } elseif ( ! isset( $this->builderCache[ 'fields' ][ $this->builderCache[ 'keys' ][ $i ] ] ) ) {
                unset( $this->builderCache[ 'keys' ][ $i ] );
                continue;
            }

            is_array( $this->builderCache[ 'keys' ][ $i ] ) OR
            $this->builderCache[ 'keys' ][ $i ] = [ $this->builderCache[ 'keys' ][ $i ] ];

            $sqlStatements[] = 'CREATE INDEX '
                . $this->conn->escapeIdentifiers(
                    $table . '_' . implode( '_', $this->builderCache[ 'keys' ][ $i ] )
                )
                . ' ON '
                . $this->conn->escapeIdentifiers( $table )
                . ' ('
                . implode( ', ', $this->conn->escapeIdentifiers( $this->builderCache[ 'keys' ][ $i ] ) )
                . ');';
        }

        return $sqlStatements;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractForge::compileCreateTableAttributesStatement
     *
     * @param array $attributes Lists of database table attributes.
     *
     * @return string
     */
    protected function compileCreateTableAttributesStatement( $attributes )
    {
        $sqlAttributesStatement = '';

        foreach ( array_keys( $attributes ) as $key ) {
            if ( is_string( $key ) ) {
                $sqlAttributesStatement .= ' ' . strtoupper( $key ) . ' ' . $attributes[ $key ];
            }
        }

        return $sqlAttributesStatement;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractForge::dropTable
     *
     * @param string $table Database table name.
     *
     * @return bool
     * @throws \O2System\Spl\Exceptions\Logic\BadFunctionCall\BadMethodCallException
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function dropTable( $table )
    {
        $table = $this->conn->prefixTable( $table );

        if ( $this->conn->isTableExists( $table ) ) {
            if ( $this->conn->debugEnabled ) {
                throw new RuntimeException( 'E_DATABASE_TABLE_NOT_EXISTS' );
            }

            return false;
        }

        if ( false !== ( $sqlStatement = $this->platformDropTableStatement( $table ) )
        ) {
            if ( $this->conn->execute( $sqlStatement ) ) {
                if ( $key = array_search(
                    $table,
                    $this->conn->queriesResultCache[ 'tableNames' ]
                )
                ) {
                    unset( $this->conn->queriesResultCache[ 'tableNames' ][ $key ] );
                }

                if ( isset( $this->conn->queriesResultCache[ 'tableFields' ][ $table ] ) ) {
                    unset( $this->conn->queriesResultCache[ 'tableFields' ][ $table ] );
                }
            }
        }

        if ( $this->conn->debugEnabled ) {
            // This feature is not available for the database you are using.'
            throw new BadMethodCallException( 'E_DATABASE_FEATURE_UNAVAILABLE' );
        }

        return false;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractForge::platformDropTableStatement
     *
     * Generates a platform-specific CREATE TABLE statement.
     *
     * @param string $table Database table name.
     *
     * @return string|bool
     */
    abstract protected function platformDropTableStatement( $table );

    //--------------------------------------------------------------------

    /**
     * AbstractForge::renameTable
     *
     * @param string $oldTableName Old database table name.
     * @param string $newTableName New database table name.
     *
     * @return bool
     * @throws \O2System\Spl\Exceptions\Logic\BadFunctionCall\BadMethodCallException
     */
    public function renameTable( $oldTableName, $newTableName )
    {
        if ( $this->conn->isTableExists( $oldTableName ) === false OR
            $this->conn->isTableExists( $newTableName ) === true
        ) {
            return false;
        }

        $oldTableName = $this->conn->prefixTable( $oldTableName );
        $newTableName = $this->conn->prefixTable( $newTableName );

        if ( false !== ( $sqlStatement = $this->platformRenameTableStatement(
                $oldTableName,
                $newTableName
            ) )
        ) {

            if ( $this->conn->execute( $sqlStatement ) ) {
                if ( $key = array_search(
                    $oldTableName,
                    $this->conn->queriesResultCache[ 'tableNames' ]
                )
                ) {
                    unset( $this->conn->queriesResultCache[ 'tableNames' ][ $key ] );
                }

                if ( isset( $this->conn->queriesResultCache[ 'tableFields' ][ $oldTableName ] ) ) {
                    unset( $this->conn->queriesResultCache[ 'tableFields' ][ $oldTableName ] );
                }

                $this->conn->queriesResultCache[ 'tableNames' ][] = $newTableName;
            }
        }

        if ( $this->conn->debugEnabled ) {
            // This feature is not available for the database you are using.'
            throw new BadMethodCallException( 'E_DATABASE_FEATURE_UNAVAILABLE' );
        }

        return false;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractForge::platformRenameTableStatement
     *
     * Generates a platform-specific RENAME TABLE statement.
     *
     * @param string $oldTableName Old database table name.
     * @param string $newTableName New database table name.
     *
     * @return string|bool
     */
    abstract protected function platformRenameTableStatement( $oldTableName, $newTableName );

    //--------------------------------------------------------------------

    /**
     * AbstractForge::addColumn
     *
     * Add database table column.
     *
     * @param string $column Database column table name.
     * @param string $table  Database table name.
     *
     * @return bool
     * @throws \O2System\Spl\Exceptions\Logic\BadFunctionCall\BadMethodCallException
     */
    public function addColumn( $column, $table = null )
    {
        // Work-around for literal column definitions
        is_array( $column ) OR $column = [ $column ];

        foreach ( array_keys( $column ) as $columnKey ) {
            $this->addField( [ $columnKey => $column[ $columnKey ] ] );
        };

        if ( false !== ( $sqlStatements = $this->platformAlterTableStatement(
                $this->conn->prefixTable( $table ),
                $this->processFields(),
                'ADD'
            ) )
        ) {
            for ( $i = 0, $totalSqlStatements = count( $sqlStatements ); $i < $totalSqlStatements; $i++ ) {
                if ( $this->conn->execute( $sqlStatements[ $i ] ) === false ) {
                    return false;
                    break;
                }
            }

            return true;
        }

        if ( $this->conn->debugEnabled ) {
            // This feature is not available for the database you are using.'
            throw new BadMethodCallException( 'E_DATABASE_FEATURE_UNAVAILABLE' );
        }

        return false;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractForge::protectedAlterTableStatement
     *
     * Generates a platform-specific ALTER TABLE statement.
     *
     * @param string $table  Database table name.
     * @param mixed  $column Database table column definition.
     * @param string $type   ALTER type.
     *
     * @return string
     */
    protected function platformAlterTableStatement( $table, $column, $type )
    {
        $sqlStatement = 'ALTER TABLE ' . $this->conn->escapeIdentifiers( $table ) . ' ';

        // DROP has everything it needs now.
        if ( $type === 'DROP' ) {
            return $sqlStatement . 'DROP COLUMN ' . $this->conn->escapeIdentifiers( $column );
        }

        $sqlStatement .= ( $type === 'ADD' )
            ? 'ADD '
            : $type . ' COLUMN ';

        $sqlStatements = [];
        for ( $i = 0, $c = count( $column ); $i < $c; $i++ ) {
            $sqlStatements[] = $sqlStatement
                . ( $column[ $i ][ '_literal' ] !== false
                    ? $column[ $i ][ '_literal' ]
                    : $this->processColumn( $column[ $i ] ) );
        }

        return $sqlStatements;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractForge::dropColumn
     *
     * Drop database table column.
     *
     * @param string $column Database table column name.
     * @param string $table  Database table name.
     *
     * @return bool
     * @throws \O2System\Spl\Exceptions\Logic\BadFunctionCall\BadMethodCallException
     */
    public function dropColumn( $column, $table )
    {
        $table = $this->conn->prefixTable( $table );

        if ( false !== ( $sqlStatement = $this->platformAlterTableStatement(
                $table,
                $column,
                'DROP'
            ) )
        ) {

            if ( $this->conn->execute( $sqlStatement ) ) {
                if ( isset( $this->conn->queriesResultCache[ 'tableFields' ][ $table ] ) ) {
                    unset( $this->conn->queriesResultCache[ 'tableFields' ][ $table ][ $column ] );
                }
            }
        }

        if ( $this->conn->debugEnabled ) {
            // This feature is not available for the database you are using.'
            throw new BadMethodCallException( 'E_DATABASE_FEATURE_UNAVAILABLE' );
        }

        return false;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractForge::modifyColumn
     *
     * Modify database table column.
     *
     * @param string $column Database table column definitions.
     * @param string $table  Database table name.
     *
     * @return bool
     * @throws \O2System\Spl\Exceptions\Logic\BadFunctionCall\BadMethodCallException
     */
    public function modifyColumn( $column, $table )
    {
        $table = $this->conn->prefixTable( $table );

        // Work-around for literal column definitions
        is_array( $column ) OR $column = [ $column ];

        foreach ( array_keys( $column ) as $columnKey ) {
            $this->addField( [ $columnKey => $column[ $columnKey ] ] );
        }

        if ( count( $this->builderCache[ 'fields' ] ) === 0 ) {
            throw new \RuntimeException( 'Input information is required' );
        }

        if ( false !== ( $sqlStatement = $this->platformAlterTableStatement(
                $table,
                $this->processFields(),
                'CHANGE'
            ) )
        ) {

            if ( $this->conn->execute( $sqlStatement ) ) {
                if ( isset( $this->conn->queriesResultCache[ 'tableFields' ][ $table ] ) ) {
                    unset( $this->conn->queriesResultCache[ 'tableFields' ][ $table ][ $column ] );
                }
            }

            $this->reset();
        }

        if ( $this->conn->debugEnabled ) {
            // This feature is not available for the database you are using.'
            throw new BadMethodCallException( 'E_DATABASE_FEATURE_UNAVAILABLE' );
        }

        return false;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractForge::reset
     *
     * Resets database forge builder cached vars.
     *
     * @return static
     */
    public function reset()
    {
        $this->builderCache = [
            'table'       => null,
            'fields'      => [],
            'keys'        => [],
            'primaryKeys' => [],
        ];

        return $this;
    }
}
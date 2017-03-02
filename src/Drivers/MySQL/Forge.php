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

namespace O2System\Database\Drivers\MySQL;

// ------------------------------------------------------------------------

use O2System\Database\Abstracts\AbstractForge;

/**
 * Class Forge
 *
 * @package O2System\Database\Drivers\MySQL
 */
class Forge extends AbstractForge
{
    /**
     * Forge::$createTableKeys
     *
     * CREATE TABLE keys flag
     *
     * Whether table keys are created from within the
     * CREATE TABLE statement.
     *
     * @var    bool
     */
    protected $isCreateTableKeys = true;

    /**
     * Forge::$unsigned
     *
     * UNSIGNED support
     *
     * @var    array
     */
    protected $unsigned
        = [
            'TINYINT',
            'SMALLINT',
            'MEDIUMINT',
            'INT',
            'INTEGER',
            'BIGINT',
            'REAL',
            'DOUBLE',
            'DOUBLE PRECISION',
            'FLOAT',
            'DECIMAL',
            'NUMERIC',
        ];

    /**
     * Forge::$null
     *
     * NULL value representation in CREATE/ALTER TABLE statements
     *
     * @var    string
     */
    protected $null = 'NULL';

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
    protected function platformCreateDatabaseStatement ( $database, array $options = [ ] )
    {
        array_unshift( $options, $database );
        array_unshift( $options, 'CREATE DATABASE %s CHARACTER SET %s COLLATE %s' );

        return call_user_func_array( 'sprintf', $options );
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
    protected function platformDropDatabaseStatement ( $database )
    {
        return 'DROP DATABASE ' . $database;
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractForge::compileCreateTableAttributesStatement
     *
     * @param array $attributes Lists of database table attributes.
     *
     * @return string
     */
    protected function compileCreateTableAttributesStatement ( $attributes )
    {
        $sqlAttributesStatement = '';

        foreach ( array_keys( $attributes ) as $key ) {
            if ( is_string( $key ) ) {
                $sqlAttributesStatement .= ' ' . strtoupper( $key ) . ' = ' . $attributes[ $key ];
            }
        }

        if ( ! empty( $this->db->charset ) && ! strpos( $sqlAttributesStatement, 'CHARACTER SET' )
             && ! strpos(
                $sqlAttributesStatement,
                'CHARSET'
            )
        ) {
            $sqlAttributesStatement .= ' DEFAULT CHARACTER SET = ' . $this->conn->getConfig( 'charset' );
        }

        if ( ! empty( $this->conn->getConfig( 'collate' ) ) && ! strpos( $sqlAttributesStatement, 'COLLATE' ) ) {
            $sqlAttributesStatement .= ' COLLATE = ' . $this->conn->getConfig( '' );
        }

        return $sqlAttributesStatement;
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
    protected function platformCreateTableStatement ( $table, $columns, $attributes )
    {
        return sprintf( "CREATE TABLE %s %s (%s\n)", $table, $columns, $attributes );
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractForge::platformDropTableStatement
     *
     * Generates a platform-specific CREATE TABLE statement.
     *
     * @param string $table Database table name.
     *
     * @return string|bool
     */
    protected function platformDropTableStatement ( $table )
    {
        return "DROP TABLE " . $table;
    }

    // ------------------------------------------------------------------------

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
    protected function platformRenameTableStatement ( $oldTableName, $newTableName )
    {
        return sprintf( "ALTER TABLE %s RENAME TO %s;", $oldTableName, $newTableName );
    }

    // ------------------------------------------------------------------------

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
    protected function platformAlterTableStatement ( $table, $column, $type )
    {
        if ( $type === 'DROP' ) {
            return parent::platformAlterTableStatement( $table, $column, $type );
        }

        $sqlStatement = 'ALTER TABLE ' . $this->conn->escapeIdentifiers( $table );
        for ( $i = 0, $totalColumn = count( $column ); $i < $totalColumn; $i++ ) {
            if ( $column[ $i ][ '_literal' ] !== false ) {
                $field[ $i ] = ( $type === 'ADD' )
                    ? "\n\tADD " . $column[ $i ][ '_literal' ]
                    : "\n\tMODIFY " . $column[ $i ][ '_literal' ];
            } else {
                if ( $type === 'ADD' ) {
                    $field[ $i ][ '_literal' ] = "\n\tADD ";
                } else {
                    $field[ $i ][ '_literal' ] = empty( $field[ $i ][ 'new_name' ] )
                        ? "\n\tMODIFY "
                        : "\n\tCHANGE ";
                }

                $field[ $i ] = $field[ $i ][ '_literal' ] . $this->processColumn( $field[ $i ] );
            }
        }

        return [ $sqlStatement . implode( ',', $field ) ];
    }

    // ------------------------------------------------------------------------

    /**
     * AbstractForge::processColumn
     *
     * Process database table column definitions.
     *
     * @param array $column Field definitions.
     *
     * @return string
     */
    protected function processColumn ( $column )
    {
        $extraClause = isset( $field[ 'after' ] )
            ? ' AFTER ' . $this->conn->escapeIdentifiers( $field[ 'after' ] )
            : '';

        if ( empty( $extraClause ) && isset( $field[ 'first' ] ) && $field[ 'first' ] === true ) {
            $extraClause = ' FIRST';
        }

        return $this->conn->escapeIdentifiers( $field[ 'name' ] )
               . ( empty( $field[ 'new_name' ] )
            ? ''
            : ' ' . $this->conn->escapeIdentifiers( $field[ 'new_name' ] ) )
               . ' ' . $field[ 'type' ] . $field[ 'length' ]
               . $field[ 'unsigned' ]
               . $field[ 'null' ]
               . $field[ 'default' ]
               . $field[ 'auto_increment' ]
               . $field[ 'unique' ]
               . ( empty( $field[ 'comment' ] )
            ? ''
            : ' COMMENT ' . $field[ 'comment' ] )
               . $extraClause;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractForge::processIndexes
     *
     * Process database table column indexes.
     *
     * @param   string $table Database table name.
     *
     * @return  string
     */
    protected function processIndexes ( $table )
    {
        $sqlStatement = '';

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

            $sqlStatement .= ",\n\tKEY " . $this->conn->escapeIdentifiers(
                    implode( '_', $this->builderCache[ 'keys' ][ $i ] )
                )
                             . ' (' . implode(
                                 ', ',
                                 $this->conn->escapeIdentifiers( $this->builderCache[ 'keys' ][ $i ] )
                             ) . ')';
        }

        $this->builderCache[ 'keys' ] = [ ];

        return $sqlStatement;
    }
}
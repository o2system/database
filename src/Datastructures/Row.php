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

namespace O2System\Database\Datastructures;

// ------------------------------------------------------------------------

use ArrayAccess;
use Countable;
use IteratorAggregate;
use O2System\Spl\Exceptions\BadClassCallException;
use O2System\Spl\Iterators\ArrayIterator;

/**
 * Class Row
 *
 * @package O2System\DB\Datastructures
 */
class Row implements IteratorAggregate, ArrayAccess, Countable
{
    /**
     * List of result row fields
     *
     * @access  protected
     * @type    array
     */
    protected $fields = [ ];

    // ------------------------------------------------------------------------

    /**
     * Row::__construct
     *
     * @param array $fields
     */
    public function __construct ( array $fields = [ ] )
    {
        $this->fields = $fields;
    }

    // ------------------------------------------------------------------------

    /**
     * Row::count
     *
     * Num of row fields
     *
     * @return int
     */
    public function count ()
    {
        return count( $this->fields );
    }

    // ------------------------------------------------------------------------

    /**
     * Row::getFields
     *
     * Return row fields
     *
     * @return array
     */
    public function getFields ()
    {
        return array_keys( $this->fields );
    }

    // ------------------------------------------------------------------------

    /**
     * Fetch Fields Into
     *
     * @param string $className
     * @param array  $classArgs
     *
     * @return object
     */
    public function fetchFieldsInto ( $className, array $classArgs = [ ] )
    {
        if ( is_string( $className ) ) {
            if ( ! class_exists( $className ) ) {
                throw new BadClassCallException( 'E_DB_FETCH_FIELDS_INTO_CLASS_NOT_FOUND', 0, [ $className ] );
            }
        }

        $classObject = $className;
        $reflection = new \ReflectionClass( $className );

        if ( count( $classArgs ) ) {
            $constructor = $reflection->getConstructor();
            $classObject = is_null( $constructor )
                ? $reflection->newInstance()
                : $reflection->newInstanceArgs(
                    $classArgs
                );
        } elseif ( is_string( $className ) ) {
            $classObject = new $className;
        }

        foreach ( $this->fields as $fieldName => $fieldValue ) {
            if ( method_exists( $classObject, $setFieldMethod = 'set' . studlycapcase( $fieldName ) ) ) {
                call_user_func_array( [ &$classObject, $setFieldMethod ], [ $fieldValue ] );
            } elseif ( method_exists( $classObject, '__set' ) ) {
                $classObject->__set( $fieldName, $fieldValue );
            } else {
                $classObject->{camelcase( $fieldName )} = $fieldValue;
            }
        }

        return $classObject;
    }

    /**
     * __toArray
     *
     * Convert each rows into array
     *
     * @return array
     */
    public function getArrayCopy ()
    {
        return $this->fields;
    }

    /**
     * Values
     *
     * Return row fields values
     *
     * @return array
     */
    public function getValues ()
    {
        return array_values( $this->fields );
    }

    /**
     * Magic method __get
     *
     * @param $field
     *
     * @return mixed|null
     */
    public function __get ( $field )
    {
        return $this->offsetGet( $field );
    }

    /**
     * Magic method __set to set variable into row fields
     *
     * @param string $field Field name
     * @param mixed  $value Field value
     */
    public function __set ( $field, $value )
    {
        $this->offsetSet( $field, $value );
    }

    /**
     * Offset Get
     *
     * Get single field value
     *
     * @param mixed $field
     *
     * @return mixed|null
     */
    public function offsetGet ( $field )
    {
        if ( isset( $this->fields[ $field ] ) ) {

            $data = $this->fields[ $field ];

            if ( $this->isJSON( $data ) ) {
                return new Fields\DataJSON( json_decode( $data, true ) );
            } elseif ( $this->isSerialized( $data ) ) {
                return new Fields\DataSerialize( unserialize( $data ) );
            } else {
                return $data;
            }
        }

        return null;
    }

    /**
     * Is JSON
     *
     * Validate if field value is JSON format
     *
     * @param $string
     *
     * @return bool
     */
    protected function isJSON ( $string )
    {
        // make sure provided input is of type string
        if ( ! is_string( $string ) ) {
            return false;
        }

        // trim white spaces
        $string = trim( $string );

        // get first character
        $first_char = substr( $string, 0, 1 );

        // get last character
        $last_char = substr( $string, -1 );

        // check if there is a first and last character
        if ( ! $first_char || ! $last_char ) {
            return false;
        }

        // make sure first character is either { or [
        if ( $first_char !== '{' && $first_char !== '[' ) {
            return false;
        }

        // make sure last character is either } or ]
        if ( $last_char !== '}' && $last_char !== ']' ) {
            return false;
        }

        // let's leave the rest to PHP.
        // try to decode string
        json_decode( $string );

        // check if error occurred
        if ( json_last_error() === JSON_ERROR_NONE ) {
            return true;
        }

        return false;
    }

    /**
     * Is Serialize
     *
     * Validate if field value is PHP serialize format
     *
     * @param $string
     *
     * @return bool
     */
    protected function isSerialized ( $string )
    {
        // if it isn't a string, it isn't serialized
        if ( ! is_string( $string ) ) {
            return false;
        }
        $string = trim( $string );
        if ( 'N;' == $string ) {
            return true;
        }
        if ( ! preg_match( '/^([adObis]):/', $string, $matches ) ) {
            return false;
        }
        switch ( $matches[ 1 ] ) {
            case 'a' :
            case 'O' :
            case 's' :
                if ( preg_match( "/^{$matches[1]}:[0-9]+:.*[;}]\$/s", $string ) ) {
                    return true;
                }
                break;
            case 'b' :
            case 'i' :
            case 'd' :
                if ( preg_match( "/^{$matches[1]}:[0-9.E-]+;\$/", $string ) ) {
                    return true;
                }
                break;
        }

        return false;
    }

    /**
     * Offset Set
     *
     * Assign offset value into Row::$_fields[offset] = value
     *
     * @param string $field Field name
     * @param mixed  $value Field value
     */
    public function offsetSet ( $field, $value )
    {
        $this->fields[ $field ] = $value;
    }

    /**
     * Get Iterator
     *
     * Get external array iterator
     *
     * @return ArrayIterator
     */
    public function getIterator ()
    {
        return new ArrayIterator( $this->fields );
    }

    /**
     * Offset Exists
     *
     * Validate whether field exists or not
     *
     * @param mixed $field
     *
     * @return bool
     */
    public function offsetExists ( $field )
    {
        return isset( $this->fields[ $field ] );
    }

    /**
     * Offset Unset
     *
     * Unset specified row field
     *
     * @param mixed $field
     */
    public function offsetUnset ( $field )
    {
        unset( $this->fields[ $field ] );
    }

    // --------------------------------------------------------------------

    /**
     * __toSerialize
     *
     * Convert rows into PHP serialize array
     *
     * @see http://php.net/manual/en/function.serialize.php
     *
     * @param int $options JSON encode options, default JSON_PRETTY_PRINT
     * @param int $depth   Maximum depth of JSON encode. Must be greater than zero.
     *
     * @return string
     */
    public function __toSerialize ()
    {
        return serialize( $this->__toArray() );
    }

    // --------------------------------------------------------------------

    /**
     * __toJSON
     *
     * @see http://php.net/manual/en/function.json-encode.php
     *
     * @param int $options JSON encode options, default JSON_PRETTY_PRINT
     * @param int $depth   Maximum depth of JSON encode. Must be greater than zero.
     *
     * @return string
     */
    public function __toJSON ( $options = JSON_PRETTY_PRINT, $depth = 512 )
    {
        $depth = $depth == 0 ? 512 : $depth;

        return call_user_func_array( 'json_encode', [ $this->fields, $options, $depth ] );
    }

    // --------------------------------------------------------------------

    /**
     * __toString
     *
     * Convert result rows into JSON String
     *
     * @return string
     */
    public function __toString ()
    {
        return (string) json_encode( $this->fields );
    }
}
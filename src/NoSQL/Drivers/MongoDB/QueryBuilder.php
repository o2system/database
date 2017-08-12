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

use O2System\Database\NoSQL\Abstracts\AbstractQueryBuilder;

/**
 * Class QueryBuilder
 *
 * @package O2System\Database\Drivers\MySQL
 */
class QueryBuilder extends AbstractQueryBuilder
{
    /**
     * AbstractQueryBuilder::countAll
     *
     * Returns numbers of query result.
     *
     * @access  public
     * @return int|string
     * @throws \O2System\Spl\Exceptions\RuntimeException
     */
    public function countAll()
    {
        $totalDocuments = 0;

        $result = $this->conn->query( $this->builderCache );

        if( $result->count() ) {
            $totalDocuments = $result->count();
        }

        return $totalDocuments;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::countAllResult
     *
     * Returns numbers of total documents.
     *
     * @param bool $reset Whether perform reset Query Builder or not
     *
     * @return int
     * @throws \O2System\Spl\Exceptions\RuntimeException
     * @access   public
     */
    public function countAllResults( $reset = true )
    {
        $cursor = $this->conn->server->executeCommand( 'neo_app',
            new \MongoDB\Driver\Command( [ 'count' => 'posts' ] ) );

        $result = current( $cursor->toArray() );

        $totalDocuments = 0;

        if( isset( $result->n ) ) {
            $totalDocuments = (int) $result->n;
        }

        return $totalDocuments;
    }

    //--------------------------------------------------------------------
}
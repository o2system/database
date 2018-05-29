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

namespace O2System\Database\NoSql\Drivers\MongoDb;

// ------------------------------------------------------------------------

use O2System\Database\NoSql\Abstracts\AbstractQueryBuilder;
use O2System\Database\NoSql\Datastructures\QueryBuilderCache;

/**
 * Class QueryBuilder
 *
 * @package O2System\Database\Drivers\MySql
 */
class QueryBuilder extends AbstractQueryBuilder
{
    /**
     * QueryBuilder::countAll
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

        $result = $this->conn->query($this->builderCache);

        if ($result->count()) {
            $totalDocuments = $result->count();
        }

        return $totalDocuments;
    }

    //--------------------------------------------------------------------

    /**
     * QueryBuilder::countAllResult
     *
     * Returns numbers of total documents.
     *
     * @param bool $reset Whether perform reset Query Builder or not
     *
     * @return int
     * @throws \O2System\Spl\Exceptions\RuntimeException
     * @access   public
     */
    public function countAllResults($reset = true)
    {
        $cursor = $this->conn->server->executeCommand('neo_app',
            new \MongoDb\Driver\Command(['count' => 'posts']));

        $result = current($cursor->toArray());

        $totalDocuments = 0;

        if (isset($result->n)) {
            $totalDocuments = (int)$result->n;
        }

        return $totalDocuments;
    }

    //--------------------------------------------------------------------

    /**
     * QueryBuilder::prepareWhereIn
     *
     * @param string|array $field
     * @param null|mixed   $value
     * @param string       $cacheKey
     */
    protected function prepareWhere($field, $value = null, $cacheKey)
    {
        if (is_array($field)) {
            foreach ($field as $name => $value) {
                $this->prepareWhere($name, $value, $cacheKey);
            }
        } elseif (isset($value)) {

            if ($field === '_id') {
                $value = new \MongoDb\BSON\ObjectID($value);
            }

            $this->builderCache->store($cacheKey, [$field => $value]);
        }
    }

    //--------------------------------------------------------------------

    /**
     * QueryBuilder::prepareWhereIn
     *
     * @param       $field
     * @param array $values
     * @param       $cacheKey
     */
    protected function prepareWhereIn($field, array $values = [], $cacheKey)
    {
        if (is_array($field)) {
            foreach ($field as $name => $values) {
                $this->prepareWhereIn($name, $values, $cacheKey);
            }
        } elseif (count($values)) {

            if ($field === '_id') {
                foreach ($values as $key => $value) {
                    $values[ $key ] = new \MongoDb\BSON\ObjectID($value);
                }
            }

            $this->builderCache->store($cacheKey, [$field => $values]);
        }
    }

    //--------------------------------------------------------------------

    /**
     * QueryBuilder::platformInsertHandler
     *
     * @param \O2System\Database\NoSql\Datastructures\QueryBuilderCache $queryBuilderCache
     *
     * @return bool
     */
    protected function platformInsertHandler(QueryBuilderCache $queryBuilderCache)
    {
        if ($queryBuilderCache->from) {
            return $this->conn->execute($queryBuilderCache, ['method' => 'insert']);
        }

        return false;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::platformInsertBatchHandler
     *
     * @param \O2System\Database\NoSql\Datastructures\QueryBuilderCache $queryBuilderCache
     *
     * @return bool
     */
    protected function platformInsertBatchHandler(QueryBuilderCache $queryBuilderCache)
    {
        if ($queryBuilderCache->from) {
            return $this->conn->execute($queryBuilderCache, ['method' => 'insert']);
        }

        return false;
    }

    //--------------------------------------------------------------------

    /**
     * QueryBuilder::platformUpdateHandler
     *
     * @param \O2System\Database\NoSql\Datastructures\QueryBuilderCache $queryBuilderCache
     *
     * @return bool
     */
    protected function platformUpdateHandler(QueryBuilderCache $queryBuilderCache)
    {
        if ($queryBuilderCache->from) {

            // New sets document
            $collection = $queryBuilderCache->from;
            $sets = $queryBuilderCache->sets;

            // Get old document
            $document = $this->get()->first();
            $newQueryBuilderCache = new QueryBuilderCache();
            $newQueryBuilderCache->store('from', $collection);
            $newQueryBuilderCache->store('where', ['_id' => $document->_id]);
            $document = $document->getArrayCopy();
            unset($document[ '_id' ]);

            $newQueryBuilderCache->store('sets', array_merge($document, $sets));

            return $this->conn->execute($newQueryBuilderCache, ['method' => 'update']);
        }

        return false;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::platformUpdateBatchHandler
     *
     * @param \O2System\Database\NoSql\Datastructures\QueryBuilderCache $queryBuilderCache
     *
     * @return bool
     */
    protected function platformUpdateBatchHandler(QueryBuilderCache $queryBuilderCache)
    {
        if ($queryBuilderCache->from) {

            // New sets document
            $collection = $queryBuilderCache->from;
            $sets = $queryBuilderCache->sets;

            // Get all old documents
            $result = $this->get();

            $newQueryBuilderCache = new QueryBuilderCache();
            $newQueryBuilderCache->store('from', $collection);

            $documentIds = [];
            foreach ($result as $document) {
                $document = $this->get()->first();
                $documentIds[] = $document->_id;
                $document = $document->getArrayCopy();
                unset($document[ '_id' ]);

                $documents[] = array_merge($document, $sets);
            }

            $newQueryBuilderCache->store('whereIn', ['_id' => $documentIds]);
            $newQueryBuilderCache->store('sets', $documents);

            return $this->conn->execute($newQueryBuilderCache, ['method' => 'update']);
        }

        return false;
    }

    //--------------------------------------------------------------------

    /**
     * QueryBuilder::platformReplaceHandler
     *
     * @param \O2System\Database\NoSql\Datastructures\QueryBuilderCache $queryBuilderCache
     *
     * @return bool
     */
    protected function platformReplaceHandler(QueryBuilderCache $queryBuilderCache)
    {
        if ($queryBuilderCache->from) {

            return $this->conn->execute($queryBuilderCache, ['method' => 'update']);
        }

        return false;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::platformReplaceBatchHandler
     *
     * @param \O2System\Database\NoSql\Datastructures\QueryBuilderCache $queryBuilderCache
     *
     * @return bool
     */
    protected function platformReplaceBatchHandler(QueryBuilderCache $queryBuilderCache)
    {
        if ($queryBuilderCache->from) {

            return $this->conn->execute($queryBuilderCache, ['method' => 'update']);
        }

        return false;
    }

    //--------------------------------------------------------------------

    /**
     * QueryBuilder::platformDeleteHandler
     *
     * @param \O2System\Database\NoSql\Datastructures\QueryBuilderCache $queryBuilderCache
     *
     * @return bool
     */
    protected function platformDeleteHandler(QueryBuilderCache $queryBuilderCache)
    {
        if ($queryBuilderCache->from) {
            return $this->conn->execute($queryBuilderCache, ['method' => 'delete']);
        }

        return false;
    }

    //--------------------------------------------------------------------

    /**
     * AbstractQueryBuilder::platformDeleteBatchHandler
     *
     * @param \O2System\Database\NoSql\Datastructures\QueryBuilderCache $queryBuilderCache
     *
     * @return bool
     */
    protected function platformDeleteBatchHandler(QueryBuilderCache $queryBuilderCache)
    {
        if ($queryBuilderCache->from) {
            return $this->conn->execute($queryBuilderCache, ['method' => 'delete']);
        }

        return false;
    }
}

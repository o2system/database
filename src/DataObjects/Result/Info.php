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

namespace O2System\Database\DataObjects\Result;

// ------------------------------------------------------------------------

use O2System\Spl\Datastructures\SplArrayObject;

/**
 * Class Info
 * @package O2System\Database\DataObjects\Result
 */
class Info
{
    public $limit = 5;
    public $total;
    public $numbering;

    // ------------------------------------------------------------------------

    /**
     * Info::__construct
     *
     * @param array $total
     */
    public function __construct(array $total)
    {
        $this->total = new SplArrayObject(array_merge([
            'rows'   => 0,
            'founds' => 0,
            'pages'  => 0,
        ], $total));

        if(isset($total['limit'])) {
            $this->limit = $total['limit'];
        }

        $this->setLimit($this->limit);
    }

    // ------------------------------------------------------------------------

    /**
     * Info::getEntries
     *
     * @return \O2System\Spl\Datastructures\SplArrayObject
     */
    public function getLimit()
    {
        return $this->limit;
    }

    // ------------------------------------------------------------------------

    /**
     * Info::setEntries
     *
     * @param int $limit
     *
     * @return static
     */
    public function setLimit($limit)
    {
        $this->limit = (int)$limit;
        $this->total->pages = 1;

        if($this->limit > 0) {
            $this->total->pages = @ceil($this->total->rows / $this->limit);
        }

        $activePage = $this->getActivePage();

        $this->numbering = new SplArrayObject([
            'start' => $start = ($activePage == 1 ? 1 : ($activePage - 1) * $this->limit + 1),
            'end'   => ($start + $this->total->founds) - 1,
        ]);

        return $this;
    }

    // ------------------------------------------------------------------------

    /**
     * Info::getTotal
     *
     * @return \O2System\Spl\Datastructures\SplArrayObject
     */
    public function getTotal($offset = null)
    {
        if (isset($offset)) {
            return $this->total->offsetGet($offset);
        }

        return $this->total;
    }

    // ------------------------------------------------------------------------

    /**
     * Info::getNumbering
     *
     * @return \O2System\Spl\Datastructures\SplArrayObject
     */
    public function getNumbering()
    {
        return $this->numbering;
    }

    public function getActivePage()
    {
        return (input()->get('page') ? input()->get('page') : 1);
    }
}
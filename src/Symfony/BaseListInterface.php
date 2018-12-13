<?php

/**
 * This file is part of the CoobixUtil package.
 *
 * (c) Coobix <https://github.com/coobix/util>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Coobix\Util\Symfony;

interface BaseListInterface 
{
    /**
     * Create the query to execute before filters
     * @param mixed $startQuery The query
     * @param string $orderBy
     */
    public function setStartQuery($startQuery = NULL, string $orderBy = 'createdAt');
    
    /**
     * Return the query to execute before filters
     * @return mixed The query
     */
    public function getStartQuery();
    
    /**
     * Execute list query 
     * @return mixed records
     */
    public function getResult();  

    /**
     * Get the current Page number from the list
     * @return int Page number
     */
    public function getListPage(): int;

    /**
     * Get max results per list page
     * @return int the max results per page
     */
    public function getListMaxResults(): int;
    
    /**
     * Get the url on each list column tittle to make ordering
     * @param  string $fieldName the column name
     * @return string            The url
     */
    public function getColFilterUrl(string $fieldName): string;
}
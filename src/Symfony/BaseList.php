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

use Symfony\Component\HttpFoundation\Request;
use Coobix\Util\Symfony\SfClassShortCuts;

/**
 * Create a query to manage a list of entities.
 *
 * @author Nicolás Rizo <nicolas@coobix.com>
 */
class BaseList implements BaseListInterface
{

    private $request;
    private $doctrine;
    private $class;
    private $qb;
    private $startQuery;
    private $listMaxResults = 10;
    private $form = null;
  

    /**
     * 
     * @param type $doctrine
     * @param string $class
     * @param \Doctrine\ORM\QueryBuilder $startQuery
     */
    public function __construct($doctrine, string $class, ?\Doctrine\ORM\QueryBuilder $startQuery = null) {
        $this->request = Request::createFromGlobals();
        $this->doctrine = $doctrine;
        $this->class = $class;
        $this->setStartQuery($startQuery);
    }

    /**
     * Create the query to execute before filters
     * @param mixed $startQuery The query
     * @param string $orderBy
     */
    public function setStartQuery($startQuery = NULL, string $orderBy = 'createdAt') {
        if (null === $startQuery) {
            $startQuery = $this->doctrine->createQueryBuilder();
            $startQuery->select('e')->from(SfClassShortCuts::getEntityShortcutName($this->class), 'e');
            $startQuery->orderBy('e.' . $orderBy, 'DESC');
        }

        $this->qb = clone $startQuery;
        $this->startQuery = $startQuery;
    }

    /**
     * Return the query to execute before filters
     * @return \Doctrine\ORM\QueryBuilder The query
     */
    public function getStartQuery() {
        return $this->startQuery;
    }

    /**
     * Execute list query 
     * @return $this
     */
    public function getResult() {
        if ($this->form && $this->form->isSubmitted()) {
            if ($this->form->isValid()) {
                $this->applyFilters();
            }
        }

        $this->applyOrder();
        $this->applyLimits();

        return $this->qb->getQuery()->getResult();
    }

    /**
     * Set the list form
     * @param $this
     */
    public function setForm($form) {
        $this->form = $form;
        return $this;
    }

    /**
     * Get the list form
     * @return Form
     */
    public function getForm() {
        return $this->form;
    }

    /**
     * Get the Query Builder
     * @return QueryBuilder
     */
    public function getQb() {
        return $this->qb;
    }

    /**
     * Apply list filters
     * @return $this
     */
    public function applyFilters() {
        $this->createFormFiltersClause();
        $this->createJoinClause();
        //$this->createLeftJoinClause();
        //$this->createOrderClause();
        return $this;
    }

    /**
     * Apply list order
     * @return $this
     */
    public function applyOrder() {
        $this->createOrderClause();
        return $this;
    }

    /**
     * Apply list limits
     * @return $this
     */
    public function applyLimits() {
        $this->qb->setFirstResult($this->getListOffSet());
        $this->qb->setMaxResults($this->getListMaxResults());
        return $this;
    }

    /**
     * Create query filters if the advanced search form has been used
     * @param  string $listSearchFormName The form name
     * @return [type]                     [description]
     */
    public function createFormFiltersClause(string $listSearchFormName = 'list_search') {

        if (!$this->request->query->has($listSearchFormName)) {
            return true;
        }

        //Get request filters
        $formFilters = $this->request->query->get($listSearchFormName);

        //Get class mapping information
        $classMetaData = $this->doctrine->getClassMetadata($this->class);

        //Loop into the request filters.
        //Ej: ?age=10
        //k: age, v: 10
        foreach ($formFilters as $k => $v) {
            //If is it empty.
            if ($v == "") {
                continue;
            }
            //The filter name has to match with the object properties
            //Get the field mapping information.
            try {
                $fieldMapping = $classMetaData->getFieldMapping($k);
            } catch (\Doctrine\ORM\Mapping\MappingException $exc) {
                continue;
            }

            switch ($fieldMapping['type']) {
                case 'string':
                case 'text':
                    $cs = 'e.' . $k . ' LIKE :e_' . $k . '';
                    $v = '%' . $v . '%';
                    $this->qb->andWhere($cs);
                    $this->qb->setParameter('e_' . $k, $v);
                    break;
                case 'integer':
                case 'float':
                    $cs = 'e.' . $k . ' = :e_' . $k;
                    $this->qb->andWhere($cs);
                    $this->qb->setParameter('e_' . $k, $v);
                    break;
                case 'datetime':
                    $cs = ' e.' . $k . ' >= :e_desde_' . $k;
                    //31-01-2015 = dd-mm-aaaa
                    $fechaString = $v;

                    $desde = new \DateTime($fechaString . ' 00:00:00');
                    $this->qb->andWhere($cs);
                    $this->qb->setParameter('e_desde_' . $k, $desde);

                    $cs = ' e.' . $k . ' <= :e_hasta_' . $k;
                    //31-01-2015 = dd-mm-aaaa
                    $hasta = new \DateTime($fechaString . ' 23:59:59');
                    //$hasta->setDate($fechaArray[2], $fechaArray[0], $fechaArray[1]);
                    //$hasta->add(new \DateInterval('PT23H59M59S'));
                    $this->qb->andWhere($cs);
                    $this->qb->setParameter('e_hasta_' . $k, $hasta);

                    break;

                case 'date':

                    //La fecha viene separada en 3 campos.var_dump($v);
                    $year = (isset($v['year'])) ? $v['year'] : "00";
                    $month = (isset($v['month'])) ? $v['month'] : "00";
                    $day = (isset($v['day'])) ? $v['day'] : "0000";
                    //var_dump($this->request->query->all());
                    //Me fijo si existe el campo dateTo (fechaTo)
                    //comprobantefe[id]

                    try {
                        $dateTo = $this->request->query->get('list_search[' . $fieldMapping['fieldName'] . 'To]', null, true);

                        if ($dateTo) {
                            $v = new \DateTime($year . '-' . $month . '-' . $day);
                            $cs = ' e.' . $k . ' >= :e_' . $k;
                            $this->qb->andWhere($cs);
                            $this->qb->setParameter('e_' . $k, $v);

                            $yearTo = (isset($dateTo['year'])) ? $dateTo['year'] : "00";
                            $monthTo = (isset($dateTo['month'])) ? $dateTo['month'] : "00";
                            $dayTo = (isset($dateTo['day'])) ? $dateTo['day'] : "0000";
                            $vTo = new \DateTime($yearTo . '-' . $monthTo . '-' . $dayTo);
                            $cs = ' e.' . $k . ' <= :e_' . $k . 'To';
                            $this->qb->andWhere($cs);
                            $this->qb->setParameter('e_' . $k . 'To', $vTo);
                        } else {
                            $v = new \DateTime($year . '-' . $month . '-' . $day);
                            $cs = ' e.' . $k . ' = :e_' . $k;
                            $this->qb->andWhere($cs);
                            $this->qb->setParameter('e_' . $k, $v);
                            /*                             *
                             */
                        }
                    } catch (\Exception $exc) {
                        
                    }

                    break;
            }
        }
    }

    protected function createJoinClause() {
        $classMetaData = $this->doctrine->getClassMetadata($this->class);

        $rfClass = $classMetaData->getReflectionClass();

        $listSearchFormName = strtolower('list_search');
        if ($this->request->query->has($listSearchFormName)) {
            $formFilters = $this->request->query->get($listSearchFormName);

            //$aliasAscii es la letra "a" pero en codigo ascii
            //es decir el 97 = a. Esto es para ir cambiando a->b->c con el fin
            //de que no sean iguales los identificadores de los paramtros
            $aliasAscii = 97;
            foreach ($formFilters as $k => $v) {
                if ($v == "") {
                    continue;
                }
                try {
                    $field = $classMetaData->getAssociationMapping($k);

                    $this->qb->join('e.' . $field['fieldName'], chr($aliasAscii), 'WITH', chr($aliasAscii) . '.id = :' . chr($aliasAscii) . '_id', chr($aliasAscii) . '.id');
                    $this->qb->setParameter(chr($aliasAscii) . '_id', $v);

                    $aliasAscii++;
                    if ($aliasAscii == 101) {
                        $aliasAscii++;
                    }
                } catch (\Doctrine\ORM\Mapping\MappingException $exc) {
                    //continue;
                }
            }
        }

        return true;
    }

    /**
     * [createOrderClause description]
     * @return [type] [description]
     */
    protected function createOrderClause() {
        //ME FIJO SI ESTAN ORDENANDO CON LOS LINKS DEL LISTADO
        if ($this->request->query->has("_sortBy")) {
            $sortBy = $this->request->query->get("_sortBy");

            //ME FIJO SI EL CAMPO QUE MANDARON POR GET ES ALGUNO DE LOS QUE FILTRA
            /*
              foreach ($this->fields as $f) {
              if ($f->getName() === $sortBy) {
              $orderByField = $f;
              break;
              }
              }

             */

            $orderByField = $sortBy;

            //SI EXISTE EL CAMPO
            if (isset($orderByField)) {

                //ME FIJO SI EXISTE EL ORDEN EN QUE SE ORDENA
                if ($this->request->query->has("_sortOrd")) {
                    $sortOrd = $this->request->query->get("_sortOrd");
                    //ME FIJO SI ES ASC O DESC

                    if ($sortOrd != "ASC") {
                        $sortOrd = "DESC";
                    }
                }

                //AGREGO EL ORDER BY
                /*
                  if ($orderByField->getType() == 'entity') {
                  $this->qb->orderBy('f.' . 'name', $sortOrd);
                  } else {
                  $this->qb->orderBy('e.' . $orderByField->getName(), $sortOrd);
                  }
                 *
                 */
                $this->qb->orderBy('e.' . $orderByField, $sortOrd);
            }
        }

        return $this;
    }

    /**
     * Get the list offset
     * @return int the list offset
     */
    protected function getListOffSet(): int {
        $listOffSet = ($this->getListPage() * $this->getListMaxResults()) - $this->getListMaxResults();

        return $listOffSet;
    }

    /**
     * Get the current Page number from the list
     * @return int Page number
     */
    public function getListPage(): int {
        $page = 1;
        if ($this->request->query->has('_page')) {
            $page = $this->request->query->get('_page');
        }

        return $page;
    }

    /**
     * Get max results per list page
     * @return int the max results per page
     */
    public function getListMaxResults(): int {
        if ($this->request->query->has('_limit')) {
            $this->listMaxResults = $this->request->query->get('_limit');
        }
        return $this->listMaxResults;
    }

    /**
     * Get the url en each list column tittle to make ordering
     * @param  string $fieldName the column name
     * @return string            The url
     */
    public function getColFilterUrl(string $fieldName): string {
        $urlGetParams = $this->request->query->all();

        if (isset($urlGetParams["_sortBy"]) && $urlGetParams["_sortBy"] === $fieldName) {
            $urlGetParams["_sortOrd"] = ($urlGetParams["_sortOrd"] == "ASC") ? 'DESC' : 'ASC';
        } else {
            $urlGetParams["_sortBy"] = $fieldName;
            $urlGetParams["_sortOrd"] = "ASC";
        }

        return '?' . http_build_query($urlGetParams, '', '&', PHP_QUERY_RFC3986);
        //return '?' . http_build_query($urlGetParams, '', '&');
    }

}

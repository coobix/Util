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
 * Create and manage a list of entities.
 *
 * @author Nicolás Rizo <nicolas@coobix.com>
 */
class BaseList
{
    private $request;
    private $doctrine;
    private $class;
    private $qb;
    private $startQuery;
    private $em;
    private $listMaxResults = 10;
    private $entities;
    private $url;
    private $form = null;

    public function __construct($doctrine, $class, $startQuery = null)
    {
        $this->request = Request::createFromGlobals();
        $this->doctrine = $doctrine;
        $this->class = $class;
        $this->setStartQuery($startQuery);
    }

    /**
     * [setStartQuery description]
     * @param [type] $startQuery [description]
     */
    public function setStartQuery($startQuery)
    {
        if (null === $startQuery) {
            $startQuery = $this->doctrine->createQueryBuilder();
            $startQuery->select('e')->from(SfClassShortCuts::getEntityShortcutName($this->class), 'e');
            $startQuery->orderBy('e.createdAt', 'DESC');
            }

        $this->qb = clone $startQuery;
        $this->startQuery = $startQuery;
    }

    public function getStartQuery()
    {
        return $this->startQuery;
    }

    

    //RETORNA LAS ENTIDADES DE LA CONSULTA
    public function getResult()
    {
        if ($this->form && $this->form->isSubmitted()) {
            
            if ($this->form->isValid()) {
                $this->applyFilters();
            }
        }

        $this->applyOrder();
        $this->applyLimits();

        $this->entities = $this->qb->getQuery()->getResult();
        return $this;
    }

    public function setForm($form)
    {
        $this->form = $form;
        return $this;
    }

    public function getQueryString()
    {
        return $this->request->getQueryString();
    }

    public function setEm()
    {
        $this->em = $this->doctrine->getManager();
        return $this;
    }

    public function setListUrl($url)
    {
        $this->url = $url;
    }
    
    public function getListUrl()
    {
        return $this->url;
    }

    public function getQb()
    {
        return $this->qb;
    }

    public function getForm() {
        return $this->form;
    }

    public function getEntities()
    {
        $this->getResult();
        return $this->entities;
    }

    //APLICA FILTROS EN LA CONSULTA
    public function applyFilters()
    {
        $this->createFormFiltersClause();
        $this->createJoinClause();
        //$this->createLeftJoinClause();
        $this->createOrderClause();
    }

    //APLICA ORDEN EN LA CONSULTA
    public function applyOrder()
    {
        $this->createOrderClause();
    }

    //APLICA LIMITES EN LA CONSULTA
    public function applyLimits()
    {
        $this->qb->setFirstResult($this->getListOffSet());
        $this->qb->setMaxResults($this->getListMaxResults());
    }

    public function createJoinClause()
    { 
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

    /*
     * Crea los filtros de la consulta del listado,
     * cuando utilizan el formulario de
     * busqueda avanzada.
     */

    public function createFormFiltersClause()
    {

        //Si no enviaron el formulario
        $listSearchFormName = strtolower('list_search');
        if (!$this->request->query->has($listSearchFormName)) {
            return true;
        }


        //Si lo utilizaron
        //Guardo los filtros
        $formFilters = $this->request->query->get($listSearchFormName);

        //Traigo el Entity Manager
        $classMetaData = $this->doctrine->getClassMetadata($this->class);
        $rfClass = $classMetaData->getReflectionClass();


        //Empiezo a recorrer los filtros que enviaron.
        //Ej: ?edad=10
        //k: edad, v: 10
        foreach ($formFilters as $k => $v) {
            //Si el filtro no tiene valor, sigue.
            if ($v == "") {
                continue;
            }

            //Si tiene una valor el filtro
            //intento recuperar la propiedad del objeto.
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

    public function createOrderClause()
    {
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

    public function getListOffSet()
    {
        $listOffSet = ($this->getListPage() * $this->getListMaxResults()) - $this->getListMaxResults();

        return $listOffSet;
    }

    public function getListPage()
    {
        $page = 1;
        if ($this->request->query->has('_page')) {
            $page = $this->request->query->get('_page');
        }

        return $page;
    }

    public function getListMaxResults()
    {
        if ($this->request->query->has('_limit')) {
            $this->listMaxResults = $this->request->query->get('_limit');
        }
        return $this->listMaxResults;
    }

    public function createLeftJoinClause()
    {
        $sortBy = $this->request->query->get('_sortBy');
        //ME FIJO SI EL CAMPO QUE MANDARON POR GET ES ALGUNO DE LOS QUE FILTRA
        foreach ($this->fields as $f) {
            if ($f->getName() === $sortBy) {
                //ME FIJO SI ES UN CAMPO DE TIPO ENTIDAD
                if ($f->getType() === 'entity') {
                    $orderByField = $f;
                    break;
                }
            }
        }
        //SI EXISTE EL CAMPO
        if (isset($orderByField)) {
            $this->qb->leftJoin('e.' . $orderByField->getName(), 'f');
        }
    }

    public function getColFilterUrl($fieldName)
    {
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

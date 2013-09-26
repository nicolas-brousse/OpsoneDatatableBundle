<?php

namespace Opsone\Datatable\Query;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\Query,
    Doctrine\ORM\QueryBuilder,
    Doctrine\ORM\Tools\Pagination\Paginator;

class DoctrineBuilder implements QueryInterface
{

    /**
     * @var Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @var Doctrine\ORM\QueryBuilder
     */
    protected $queryBuilder;
    protected $entity_name;
    protected $entity_alias;
    protected $fields       = array('_identifier_' => 'id');
    protected $searchFields = array();

    private $entities            = null;
    private $totalRecords        = null;
    private $totalDisplayRecords = null;

    /**
     * class constructor 
     * 
     * @param ContainerInterface $container 
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em = $this->container->get('doctrine.orm.entity_manager');
        $this->request = $this->container->get('request');
        $this->queryBuilder = $this->em->createQueryBuilder();
    }

    /**
     * Get total records
     * 
     * @return integer 
     */
    public function getTotalRecords($force=false)
    {
        if ($force || $this->totalRecords === null)
        {
            $qb = clone $this->queryBuilder;
            $entities = new Paginator($qb, true);
            $this->totalRecords = $entities->count();
        }

        return $this->totalRecords;
    }

    /**
     * Get total display records
     * 
     * @return integer 
     */
    public function getTotalDisplayRecords($force=false)
    {
        if (!$this->request->get("sSearch")) {
            return $this->getTotalRecords($force);
        }
        if ($force || $this->totalDisplayRecords === null)
        {
            $qb = clone $this->queryBuilder;
            $this->applySearch($qb);
            $entities = new Paginator($qb, true);
            $this->totalDisplayRecords = $entities->count();
        }

        
        return $this->totalDisplayRecords;
    }

    public function getResults($force=false)
    {
        if ($force || $this->entities === null)
        {
            $request = $this->request;

            $qb = clone $this->queryBuilder;

            $this->applySearch($qb);
            $this->applyOrder($qb);
            $query = $qb->getQuery();

            $query
                ->setMaxResults($request->get('iDisplayLength', 50))
                ->setFirstResult($request->get('iDisplayStart', 0))
            ;
            $this->entities = new Paginator($query, true);
        }
        return $this->entities;
    }

    /**
     * get data
     * 
     * @return array 
     */
    public function getData()
    {
        return $this->getResults()->getIterator();
    }

    /**
     * get entity name
     * 
     * @return string
     */
    public function getEntityName()
    {
        return $this->entity_name;
    }

    /**
     * get entity alias
     * 
     * @return string
     */
    public function getEntityAlias()
    {
        return $this->entity_alias;
    }

    /**
     * set doctrine query builder
     * 
     * @param Doctrine\ORM\QueryBuilder $queryBuilder
     * 
     * @return DoctrineBuilder 
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
        return $this;
    }

    /**
     * get doctrine query builder
     * 
     * @return Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * set entity
     * 
     * @param type $entity_name
     * @param type $entity_alias
     * 
     * @return DoctrineBuilder
     */
    public function setEntity($entity_name, $entity_alias)
    {
        $this->entity_name = $entity_name;
        $this->entity_alias = $entity_alias;
        $this->queryBuilder->addSelect($this->entity_alias);
        $this->queryBuilder->from($entity_name, $entity_alias);
        return $this;
    }

    /**
     * set fields
     * 
     * @param array $fields
     * 
     * @return DoctrineBuilder
     */
    public function setFields(array $fields)
    {
        $select_fields = array();
        $clean_fields  = array();

        foreach ($fields as $k=>$v)
        {
            if ($k == '_identifier_') {
                continue;
            }

            if (is_array($v))
            {
                $select_fields[$k] = $v['ope'] . ' AS ' . $v['alias'];
                $clean_fields[$k]  = $v['alias'];
            }
            else if (is_string($v))
            {
                $select_fields[$k] = $v;
                $clean_fields[$k]  = $v;
            }
        }

        $this->fields = $clean_fields;
        $this->queryBuilder->addSelect(array_values($select_fields));
        return $this;
    }

    /**
     * get fields
     * 
     * @return array 
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * set search fields
     * 
     * @param array $searchFields
     * 
     * @return DoctrineBuilder 
     */
    public function setSearchFields(array $searchFields)
    {
        $this->searchFields = $searchFields;
        return $this;
    }

    /**
     * get search fields
     * 
     * @return array
     */
    public function getSearchFields()
    {
        return !empty($this->searchFields) ? $this->searchFields : array_values($this->getFields());
    }

    /**
     * get the search dql
     * 
     * @return string
     */
    protected function applySearch(QueryBuilder $queryBuilder)
    {
        if ($search = $this->request->get("sSearch"))
        {
            $orx = $queryBuilder->expr()->orx();

            foreach (array_values($this->getSearchFields()) as $search_field)
            {
                $andx = $queryBuilder->expr()->andx();
                foreach (preg_split('/\s+/', $search, null, PREG_SPLIT_NO_EMPTY) as $i=>$word)
                {
                    $andx->add($queryBuilder->expr()->like("$search_field", ":search_" . $i));
                    $queryBuilder->setParameter('search_' . $i, '%' . $word . '%');
                }

                $orx->add($andx);
            }

            $queryBuilder->andWhere($orx);
        }
    }

    /**
     * get the order dql
     * 
     * @return string
     */
    protected function applyOrder(QueryBuilder $queryBuilder)
    {
        $request = $this->request;
        $dql_fields = array_values($this->fields);

        $sort_cols = array_filter(array_keys($request->query->all()), function($k) {
            return preg_match("#^iSortCol_#", $k);
        });

        if (!empty($sort_cols)) {
            $queryBuilder->resetDQLPart('orderBy');
        }

        foreach ($sort_cols as $key)
        {
            $i = explode('_', (string) $key);
            $i = array_pop($i);

            $order_field = $request->get($key) ? current(explode(' AS ', $dql_fields[$request->get($key)])) : null;

            if (!is_null($order_field)) {
                $queryBuilder->addOrderBy($order_field, mb_strtoupper($request->get('sSortDir_' . $i, 'ASC')));
            }
        }
    }
}
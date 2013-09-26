<?php

namespace Opsone\Datatable;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Symfony\Component\HttpFoundation\Response;
use Opsone\Datatable\Query\DoctrineBuilder,
    Opsone\Datatable\Formatter\Renderer;

abstract class Datatable
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
     * @var Opsone\Datatable\Factory\Query\QueryInterface
     */
    protected $qb;

    protected $template;

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
        $this->qb = new DoctrineBuilder($container);
    }

    /**
     * Get the datas
     *
     * @param int $hydration_mode
     *
     * @return array
     */
    public function getDatas()
    {
        $this->buildDatatable();
        $request = $this->request;
        $data = $this->qb->getData();
        
        if (!is_null($this->template))
        {
            $render = new Renderer($this->container, $this->qb->getFields(), $this->template);
            $data = $render->applyTo($data);
        }

        return array(
            "sEcho"                => intval($request->get('sEcho')),
            "iTotalRecords"        => $this->qb->getTotalRecords(),
            "iTotalDisplayRecords" => $this->qb->getTotalDisplayRecords(),
            "aaData"               => $data,
        );
    }

    /**
     * Get the render into a Response
     *
     * @param int $hydration_mode
     *
     * @return Response 
     */
    public function getRender()
    {
        return new Response(json_encode( $this->getDatas() ));
    }

    /**
     * set query builder
     *
     * @param QueryInterface $qb
     *
     * @return Datatable 
     */
    public function setDatatableQueryBuilder(\Opsone\Datatable\Query\QueryInterface $qb)
    {
        $this->qb = $qb;
        return $this;
    }

    /**
     * get query builder
     *
     * @return QueryInterface 
     */
    public function getDatatableQueryBuilder()
    {
        return $this->qb;
    }

    /**
     * set the doctrine query builder
     *
     * @param Doctrine\ORM\QueryBuilder $qb
     *
     * @return Datatable 
     */
    public function setDoctrineQueryBuilder(\Doctrine\ORM\QueryBuilder $qb)
    {
        $this->qb->setQueryBuilder($qb);
        return $this;
    }

    /**
     * get the doctrine query builder
     * 
     * @return QueryInterface 
     */
    public function getDoctrineQueryBuilder()
    {
        return $this->qb->getQueryBuilder();
    }

    /**
     * set query builder
     * 
     * @param QueryInterface $queryBuilder 
     */
    public function setQueryBuilder(QueryInterface $qb)
    {
        $this->qb = $qb;
    }

    /**
     * set fields
     * 
     * @param array $fields
     * 
     * @return Datatable 
     */
    public function setFields(array $fields)
    {
        $this->qb->setFields($fields);
        return $this;
    }

    /**
     * set search fields
     * 
     * @param array $fields
     * 
     * @return Datatable 
     */
    public function setSearchFields(array $searchFields)
    {
        $this->qb->setSearchFields($searchFields);
        return $this;
    }

    /**
     * Set the template name for rendering fields
     *
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * set entity
     * 
     * @param type $entity_name
     * @param type $entity_alias
     * 
     * @return Datatable 
     */
    public function setEntity($entity_name, $entity_alias)
    {
        $this->qb->setEntity($entity_name, $entity_alias);
        return $this;
    }

    /**
     * get the doctrine entity manager
     * 
     * @return Doctrine\ORM\EntityManager 
     */
    public function getEntityManager()
    {
        return $this->em;
    }

}

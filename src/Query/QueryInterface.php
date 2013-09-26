<?php

namespace Opsone\Datatable\Query;

interface QueryInterface
{

    /**
     * get total records
     * 
     * @return integer 
     */
    function getTotalRecords();

    /**
     * get data
     * 
     * @return array
     */
    function getData();

    /**
     * set entity
     * 
     * @param string $entity_name
     * @param string $entity_alias
     * 
     * @return Datatable 
     */
    function setEntity($entity_name, $entity_alias);

    /**
     * set fields
     * 
     * @param array $fields
     * 
     * @return Datatable 
     */
    function setFields(array $fields);

    /**
     * get entity name
     * 
     * @return string
     */
    function getEntityName();

    /**
     * get entity alias
     * 
     * @return string
     */
    function getEntityAlias();

    /**
     * get fields
     * 
     * @return array
     */
    function getFields();
}
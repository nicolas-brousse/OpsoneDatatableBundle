<?php

namespace Opsone\Datatable\Formatter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Util\Inflector;

class Renderer
{

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var string
     */
    protected $template = "";

    /**
     * @var array
     */
    protected $fields = array();

    /**
     * @var int
     */
    protected $identifier_index = '_identifier_';

    /**
     * class constructor
     * 
     * @param ContainerInterface $container
     * @param array $fields 
     * @param string $template
     */
    public function __construct(ContainerInterface $container, array $fields, $template)
    {
        $this->container = $container;
        $this->template = $template;
        $this->fields = $fields;
        $this->prepare();
    }

    /**
     * return the rendered view using the given content
     * 
     * @param array     $params
     * 
     * @return string
     */
    public function applyView(array $params)
    {
        $out = $this->container
                        ->get('templating')
                        ->render($this->template, $params);
        $out = html_entity_decode($out);
        return $out;
    }

    /**
     * prepare the renderer :
     *  - guess the identifier index
     * 
     * @return void
     */
    protected function prepare()
    {
        $this->identifier_index = array_search("_identifier_", array_keys($this->fields));
    }

    /**
     * apply foreach given cell content the given (if exists) view
     * 
     * @param \Iterator $data 
     * 
     * @return array
     */
    public function applyTo($data)
    {
        $fields = array();
        foreach ($this->fields as $k=>$v) {
            $fields[] = array($k, $v);
        }

        $formated_datas = array();
        foreach ($data as $row_index => $entity)
        {
            if (is_array($entity)) {
                $entity = current($entity);
            }

            $rowID = trim($this->applyView(array(
                'key'    => 'DT_RowId',
                'entity' => $entity,
            )));
            $rowClass = trim($this->applyView(array(
                'key'    => 'DT_RowClass',
                'entity' => $entity,
            )));

            if ($rowID) {
                $formated_datas[$row_index]['DT_RowId'] = $rowID;
            }
            if ($rowClass) {
                $formated_datas[$row_index]['DT_RowClass'] = $rowClass;
            }

            foreach ($fields as $column_index => $field)
            {
                $method = "get" . Inflector::classify( substr($field[1], strpos($field[1], '.') + 1) );

                $params = array(
                    'index'  => $column_index,
                    'key'    => $field[0],
                    'value'  => method_exists($entity, $method) ? call_user_func(array($entity, $method)) : null,
                    'entity' => $entity,
                );
                $formated_datas[$row_index][$column_index] = $this->applyView($params);
            }
        }
        return $formated_datas;
    }
}
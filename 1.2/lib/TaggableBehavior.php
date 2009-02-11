<?php

/**
 * TaggableBehavior 
 *
 * @author      Dylan Oliver
 */
class TaggableBehavior extends Doctrine_Record_Generator
{
    /**
     * Array of TaggableBehavior
     *
     * @var string
     */
    protected $_options = array(
                                'generateFiles'  => false,
                                'generatePath'   => false,
                                'builderOptions' => array(),
                                'identifier'     => false,
                                'table'          => false,
                                'pluginTable'    => false,
                                'children'       => array(),
		                            'className'     => '%CLASS%Tagging'
                            );

    /**
     * Accepts array of options to configure the AuditLog
     *
     * @param   array $options An array of options
     * @return  void
     */
    public function __construct(array $options = array())
    {
        $this->_options = Doctrine_Lib::arrayDeepMerge($this->_options, $options);
    }
    
    /**
     * initialize
     *
     * Initialize the plugin. Call in Doctrine_Template setTableDefinition() in order to initiate a generator in a template
     * SEE: Doctrine_Template_I18n for an example
     *
     * @param  Doctrine_Table $table 
     * @return void
     */
    public function initialize(Doctrine_Table $table)
    {
        if ($this->_initialized) {
            return false;
        }
        
        $this->_initialized = true;

        $this->initOptions();
        
//        $tagging_table->addGenerator($this, get_class($this));

        $tagging = Doctrine::getTable('Tagging');
//        $this->_table = $table;
        $map = $tagging->getOption('inheritanceMap');
        
        $this->_options['table'] = $table;
        $tableName = $table->getComponentName();

        $this->_options['className'] = str_replace('%CLASS%', $tableName, $this->_options['className']);

        // check that class doesn't exist (otherwise we cannot create it)
        if ($this->_options['generateFiles'] === false && class_exists($this->_options['className'], false)) {
            return false;
        }

//        $this->buildTable();
        $this->_table = $tagging;

        $fk = $this->buildForeignKeys($this->_options['table']);
        $this->buildLocalRelation();

        $this->_table->setColumns($fk);

        $this->setTableDefinition();
        $this->setUp();

        $definition = array();
//        $definition['columns'] = $this->_table->getColumns();
//        $definition['tableName'] = $this->_table->getTableName();
        
        $inheritance = array();
        $definition['inheritance']['extends'] = 'Tagging';
        $definition['inheritance']['type'] = 'column_aggregation';
        $definition['inheritance']['keyField'] = 'taggable_model';
        $definition['inheritance']['keyValue'] = $this->_options['table']->getComponentName();
        
        $this->generateClass($definition);

        $this->buildChildDefinitions();

//        $this->_table->initIdentifier();
    }
    
    public function initOptions() {
      $this->setOption('className', '%CLASS%Tagging');
    }
    
    /**
     * Set table definition to extend Tagging through column aggregation inheritance
     *
     * @return  void
     */
    /*
    public function setTableDefinition()
    {
//        $name = $this->_options['table']->getComponentName();
//        $columns = $this->_options['table']->getColumns();
    }
    */

    /**
     * Get array of information for the passed record and the specified version
     *
     * @param   Doctrine_Record $record
     * @param   integer         $version
     * @return  array           An array with version information
     */
    
    //eg getTags?
    public function getVersion(Doctrine_Record $record, $version)
    {
        $className = $this->_options['className'];

        $q = new Doctrine_Query();

        $values = array();
        foreach ((array) $this->_options['table']->getIdentifier() as $id) {
            $conditions[] = $className . '.' . $id . ' = ?';
            $values[] = $record->get($id);
        }

        $where = implode(' AND ', $conditions) . ' AND ' . $className . '.' . $this->_options['versionColumn'] . ' = ?';

        $values[] = $version;

        $q->from($className)
          ->where($where);

        return $q->execute($values, Doctrine::HYDRATE_ARRAY);
    }

    /**
     * Get the max version number for a given Doctrine_Record
     *
     * @param Doctrine_Record $record
     * @return Integer $versionnumber
     */
    public function getMaxVersion(Doctrine_Record $record)
    {
        $className = $this->_options['className'];
        $select = 'MAX(' . $className . '.' . $this->_options['versionColumn'] . ') max_version';

        foreach ((array) $this->_options['table']->getIdentifier() as $id) {
            $conditions[] = $className . '.' . $id . ' = ?';
            $values[] = $record->get($id);
        }

        $q = Doctrine_Query::create()
                ->select($select)
                ->from($className)
                ->where(implode(' AND ',$conditions));

        $result = $q->execute($values, Doctrine::HYDRATE_ARRAY);

        return isset($result[0]['max_version']) ? $result[0]['max_version']:0;
    }
}
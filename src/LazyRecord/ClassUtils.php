<?php
namespace LazyRecord;
use Exception;
use ReflectionClass;
use LazyRecord\Inflector;

class ClassUtils
{

    static public function get_declared_dynamic_schema_classes_from_models()
    {
        $classes = get_declared_classes();
        $classes = array_filter($classes, function($class) {
            return is_a($class,'LazyRecord\BaseModel',true) && method_exists($class,'schema');
        });
        return array_map(function($class) {
            $model = new $class;
            $schema = new \LazyRecord\Schema\DynamicSchemaDeclare($model);
            return $schema;
        },$classes);
    }

    static public function get_declared_schema_classes() 
    {
        $classes = get_declared_classes();
        return self::filter_schema_classes($classes);
    }

    /**
     * Get referenced schema classes and put them in order.
     *
     * @param classes[]
     */
    static public function expand_schema_classes($classes)
    {
        $schemas = array();
        foreach( $classes as $class ) {
            $schema = new $class; // declare schema
            $refs = $schema->getReferenceSchemas();
            foreach( $refs as $refClass => $v )
                $schemas[] = $refClass;
            $schemas[] = $class;
        }
        $schemaClasses = array_unique($schemas);
        return array_map(function($class) { 
            return new $class; }, 
                $schemaClasses);
    }


    /**
     * Filter non-dynamic schema declare classes.
     *
     * @param array $classes class list.
     */
    static public function filter_schema_classes($classes)
    {
        $list = array();
        foreach( $classes as $class ) {
            // skip abstract classes.
            if ( 
              ! is_subclass_of($class, 'LazyRecord\Schema\SchemaDeclare',true)
              || is_a($class, 'LazyRecord\Schema\DynamicSchemaDeclare',true)
              || is_a($class, 'LazyRecord\Schema\MixinSchemaDeclare',true)
              || is_subclass_of($class, 'LazyRecord\Schema\MixinSchemaDeclare',true)
            ) { 
                continue; 
            }
            $rf = new ReflectionClass( $class );
            if ( $rf->isAbstract() )
                continue;

            $list[] = $class;
        }
        return $list;
    }

    static public function convert_class_to_table($class)
    {
        if( preg_match( '/(\w+?)(?:Model)?$/', $class ,$reg) ) 
        {
            $table = @$reg[1];
            if( ! $table )
                throw new Exception( "Table name error: $class" );

            /* convert BlahBlah to blah_blah */
            $table =  strtolower( preg_replace( 
                '/(\B[A-Z])/e' , 
                "'_'.strtolower('$1')" , 
                $table ) );
            return Inflector::getInstance()->pluralize($table);
        } 
        else 
        {
            throw new Exception('Table name convert error');
        }
    }

}



<?php

namespace App\Doctrine;

use Doctrine\DBAL\Platforms\MySQLPlatform;

class CustomMySQLPlatform extends MySQLPlatform
{
    /**
     * Override to prevent using FULL_COLLATION_NAME which is causing issues
     */
    public function getListTableColumnsSQL($table, $database = null)
    {
        $sql = parent::getListTableColumnsSQL($table, $database);
        
        // Replace any problematic table joins or column references
        $sql = str_replace('JOIN information_schema.COLLATION_CHARACTER_SET_APPLICABILITY ccsa', '', $sql);
        $sql = str_replace('ccsa.COLLATION_NAME = c.COLLATION_NAME', '1=1', $sql);
        $sql = str_replace('AND ccsa.FULL_COLLATION_NAME', '-- AND ccsa.FULL_COLLATION_NAME', $sql);
        
        return $sql;
    }
    
    /**
     * Add JSON type support
     */
    public function getJsonTypeDeclarationSQL(array $column): string
    {
        return 'JSON';
    }
    
    /**
     * Returns the SQL used to create a table.
     */
    protected function _getCreateTableSQL($name, array $columns, array $options = [])
    {
        // Remove problematic options that might cause issues with older MySQL versions
        if (isset($options['uniqueConstraints'])) {
            foreach ($options['uniqueConstraints'] as $key => $constraint) {
                // Remove length definitions for unique constraints on older MySQL versions
                if (isset($constraint['lengths'])) {
                    unset($options['uniqueConstraints'][$key]['lengths']);
                }
            }
        }

        return parent::_getCreateTableSQL($name, $columns, $options);
    }

    /**
     * Map JSON to appropriate type
     */
    public function initializeDoctrineTypeMappings()
    {
        parent::initializeDoctrineTypeMappings();
        $this->doctrineTypeMapping['json'] = 'json';
    }

    /**
     * Handle JSON comparison expressions
     */
    public function getJsonSearchOperatorExpression($fieldName, $operator, $value): string
    {
        return $fieldName . '->"$.' . $value . '"';
    }
}

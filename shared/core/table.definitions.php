<?
//core table definitions
$tableDefs = array(
    'xml_templates_list' => array(
        'name' => 'xml_templates_list',
        'fields' => array(
            array(
                'name' => 'template_path',
                'type' => 'VARCHAR(255)'
            ),
            array(
                'name' => 'name',
                'type' => 'VARCHAR(255)'
            ),
            array(
                'name' => 'icon_path',
                'type' => 'VARCHAR(255)'
            ),
            array(
                'name' => 'table',
                'type' => 'VARCHAR(255)'
            ),
            array(
                'name' => 'fields_index',
                'type' => 'TEXT'
            ),
            array(
                'name' => 'alias',
                'type' => 'VARCHAR(255)'
            ),
            array(
                'name' => 'lastModifiedMethod',
                'type' => 'VARCHAR(255)',
            ),
        ),
        'keys' => array(
            'PRIMARY' => array(
                'type' => 'PRIMARY',
                'name' => 'PRIMARY',
                'fields' => array(
                    array(
                        'name' => 'template_path',
                    )
                )
            ),
        )
    ),
    'object_rules' => array(
        'name' => 'object_rules',
        'fields' => array(
            array(
                'name' => 'object',
                'type' => 'VARCHAR(255)'
            ),
            array(
                'name' => 'child',
                'type' => 'VARCHAR(255)'
            ),
            array(
                'name' => 'max',
                'type' => 'TINYINT',
            ),
        ),
        'keys' => array(
            'object' => array(
                'type' => 'INDEX',
                'name' => 'object',
                'fields' => array(
                    array(
                        'name' => 'object',
                        'length' => 128
                    ),
                    array(
                        'name' => 'child',
                        'length' => 128
                    )
                )
            ),
        )
    ),
    'system_values' => array(
        'name' => 'system_values',
        'fields' => array(
            array(
                'name' => 'name',
                'type' => 'VARCHAR(255)'
            ),
            array(
                'name' => 'value',
                'type' => 'TEXT'
            ),
            array(
                'name' => 'modified',
                'type' => 'TIMESTAMP'
            ),
        ),
        'keys' => array(
            'PRIMARY' => array(
                'type' => 'PRIMARY',
                'name' => 'PRIMARY',
                'fields' => array(
                    array(
                        'name' => 'name',
                    )
                )
            ),
        )
    ),
    'image_text_info' => array(
        'name' => 'image_text_info',
        'fields' => array(
            array(
                'name' => 'filename',
                'type' => 'VARCHAR(255)'
            ),
            array(
                'name' => 'width',
                'type' => 'INT(11)'
            ),
            array(
                'name' => 'height',
                'type' => 'INT(11)'
            ),
            array(
                'name' => 'type',
                'type' => 'VARCHAR(255)'
            ),
            array(
                'name' => 'created',
                'type' => 'DATETIME'
            ),
        ),
        'keys' => array(
            'PRIMARY' => array(
                'type' => 'PRIMARY',
                'name' => 'PRIMARY',
                'fields' => array(
                    array(
                        'name' => 'filename',
                    )
                )
            )
        )
    ),
    'objects_rewrite_cache' => array(
        'name' => 'objects_rewrite_cache',
        'fields' => array(
            array(
                'name' => 'object_id',
                'type' => 'INT(11)'
            ),
            array(
                'name' => 'url',
                'type' => 'TEXT'
            ),
            array(
                'name' => 'file_name',
                'type' => 'VARCHAR(255)'
            ),
        ),
        'keys' => array(
            'PRIMARY' => array(
                'type' => 'PRIMARY',
                'name' => 'PRIMARY',
                'fields' => array(
                    array(
                        'name' => 'object_id',
                    )
                )
            ),
        )
    ),
    'objects_cache' => array(
        'name' => 'objects_cache',
        'fields' => array(
            array(
                'name' => 'id',
                'type' => 'VARCHAR(32)'
            ),
            array(
                'name' => 'data',
                'type' => 'LONGTEXT'
            ),
            array(
                'name' => 'timestamp',
                'type' => 'DATETIME'
            ),
        ),
        'keys' => array(
            'id' => array(
                'type' => 'UNIQUE',
                'name' => 'id',
                'fields' => array(
                    array(
                        'name' => 'id',
                    )
                )
            ),
        )
    ),

    'objects_url_history' => array(
        'name' => 'objects_url_history',
        'fields' => array(
            array(
                'name' => 'path',
                'type' => 'VARCHAR(255)'
            ),
            array(
                'name' => 'object_id',
                'type' => 'INT(11)'
            ),
            array(
                'name' => 'params',
                'type' => 'longtext'
            ),
        ),
        'keys' => array(
            'PRIMARY' => array(
                'type' => 'PRIMARY',
                'name' => 'PRIMARY',
                'fields' => array(
                    array(
                        'name' => 'path',
                    )
                )
            ),
        )
    ),

    'bannerCache' => array(
        'name' => 'bannerCache',
        'fields' => array(
            array(
                'name' => 'cacheKey',
                'type' => 'CHAR(40)'
            ),
            array(
                'name' => 'cacheDate',
                'type' => 'DATETIME'
            ),
            array(
                'name' => 'html',
                'type' => 'LONGTEXT'
            ),
        ),
        'keys' => array(
            'cacheKey' => array(
                'type' => 'PRIMARY',
                'name' => 'cacheKey',
                'fields' => array(
                    array(
                        'name' => 'cacheKey',
                    ),
                )
            ),
            'cacheDate' => array(
                'type' => 'INDEX',
                'name' => 'cacheDate',
                'fields' => array(
                    array(
                        'name' => 'cacheDate',
                    )
                )
            ),
        )
    ),
    'embedObjects' => array
    (
        'name' => 'embedObjects',
        'fields' => array
        (
            array
            (
                'name' => 'id',
                'type' => 'INT(11)',
                'auto_increment' => true
            ),
            array
            (
                'name' => 'add_date',
                'type' => 'DATETIME'
            ),
            array
            (
                'name' => 'embedCode',
                'type' => 'TEXT',
            ),
            array
            (
                'name' => 'objectId',
                'type' => 'INT(11)',
            ),
            array
            (
                'name' => 'source',
                'type' => 'VARCHAR(255)',
                'default' => 'embedCode'
            ),

        ),
        'keys' => array
        (
            'id' => array
            (
                'type' => 'PRIMARY',
                'name' => 'id',
                'fields' => array
                (
                    array
                    (
                        'name' => 'id',
                    ),
                )
            ),
        )
    ),


    'objects' => array
    (
        'name' => 'objects',
        'fields' => array
        (
            array
            (
                'name' => 'id',
                'type' => 'INT(11)',
                'auto_increment' => true
            ),
            array
            (
                'name' => 'name',
                'type' => 'VARCHAR(255)',
            ),
            array
            (
                'name' => 'type',
                'type' => 'TINYINT',
            ),
            array
            (
                'name' => 'parent_id',
                'type' => 'INT',
            ),
            array
            (
                'name' => 'create_date',
                'type' => 'DATETIME',
                'default' => '0000-00-00 00:00:00'
            ),
            array
            (
                'name' => 'last_edit',
                'type' => 'DATETIME',
                'default' => '0000-00-00 00:00:00'
            ),
            array
            (
                'name' => 'createdby',
                'type' => 'INT',
                'default' => '0'
            ),
            array
            (
                'name' => 'visible',
                'type' => 'TINYINT',
                'default' => '1'
            ),
            array
            (
                'name' => 'protected',
                'type' => 'TINYINT',
                'default' => '0',
            ),
            array
            (
                'name' => 'order_nr',
                'type' => 'INT',
                'default' => '0'
            ),
            array
            (
                'name' => 'rewrite_name',
                'type' => 'VARCHAR(255)',
            ),
            array
            (
                'name' => 'template',
                'type' => 'VARCHAR(255)',
            ),
            array(
                'name' => 'data',
                'type' => 'LONGTEXT',
            ),
        ),
        'keys' => array
        (
            'PRIMARY' => array
            (
                'type' => 'PRIMARY',
                'name' => 'PRIMARY',
                'fields' => array
                (
                    array
                    (
                        'name' => 'id',
                    ),
                )
            ),

            'create_date' => array
            (
                'type' => 'INDEX',
                'name' => 'create_date',
                'fields' => array(
                    array(
                        'name' => 'create_date'
                    )
                )
            ),

            'order_nr' => array
            (
                'type' => 'INDEX',
                'name' => 'order_nr',
                'fields' => array(
                    array(
                        'name' => 'order_nr'
                    )
                )
            ),

            'visible' => array
            (
                'type' => 'INDEX',
                'name' => 'visible',
                'fields' => array(
                    array(
                        'name' => 'visible'
                    )
                )
            ),

            'last_edit' => array
            (
                'type' => 'INDEX',
                'name' => 'last_edit',
                'fields' => array(
                    array(
                        'name' => 'last_edit'
                    )
                )
            ),


            'template' => array
            (
                'type' => 'INDEX',
                'name' => 'template',
                'fields' => array(
                    array(
                        'name' => 'template'
                    )
                )
            ),


            'parent_type' => array
            (
                'type' => 'INDEX',
                'name' => 'parent_type',
                'fields' => array(
                    array(
                        'name' => 'parent_id'
                    ),
                    array(
                        'name' => 'type'
                    )
                )
            ),


        )
    ),




    'translations' => array
    (
        'name' => 'translations',
        'fields' => array
        (
            array
            (
                'name' => 'id',
                'type' => 'INT(11)',
                'auto_increment' => true
            ),
            array
            (
                'name' => 'group_id',
                'type' => 'INT(11)'
            ),
            array
            (
                'name' => 'name',
                'type' => 'VARCHAR(100)'
            ),
            array
            (
                'name' => 'type',
                'type' => 'TINYINT(1)',
            ),
        ),

        'keys' => array
        (
            'id' => array
            (
                'type' => 'PRIMARY',
                'name' => 'id',
                'fields' => array
                (
                    array
                    (
                        'name' => 'id',
                    ),
                )
            ),
            'name' => array
            (
                'type' => 'index',
                'name' => 'name',
                'fields' => array
                (
                    array
                    (
                        'name' => 'name',
                    ),
                )
            ),
            'group_id' => array
            (
                'type' => 'INDEX',
                'name' => 'group_id',
                'fields' => array
                (
                    array
                    (
                        'name' => 'group_id'
                    )
                )
            ),
        ),
    ),


    'translations_groups' => array
    (
        'name' => 'translations_groups',
        'fields' => array
        (
            array
            (
                'name' => 'id',
                'type' => 'INT(11)',
                'auto_increment' => true
            ),
            array
            (
                'name' => 'name',
                'type' => 'VARCHAR(255)'
            ),

        ),

        'keys' => array
        (
            'id' => array
            (
                'type' => 'PRIMARY',
                'name' => 'id',
                'fields' => array
                (
                    array
                    (
                        'name' => 'id',
                    ),
                )
            ),
        ),
    ),


    'translations_data' => array
    (
        'name' => 'translations_data',
        'fields' => array
        (
            array
            (
                'name' => 'translation_id',
                'type' => 'INT(11)'
            ),
            array
            (
                'name' => 'language_id',
                'type' => 'SMALLINT(6)'
            ),
            array
            (
                'name' => 'translation',
                'type' => 'TEXT',
            ),
            array
            (
                'name' => 'machineTranslated',
                'type' => 'TINYINT(1)',
            ),
        ),

        'keys' => array
        (
            'id' => array
            (
                'type' => 'PRIMARY',
                'name' => 'id',
                'fields' => array
                (
                    array
                    (
                        'name' => 'translation_id',
                    ),
                    array
                    (
                        'name' => 'language_id',
                    ),
                )
            ),
        ),
    ),


    'object_ancestors' => array
    (
        'name' => 'object_ancestors',
        'fields' => array
        (
            array
            (
                'name' => 'object_id',
                'type' => 'INT(11)',
            ),
            array
            (
                'name' => 'ancestor_id',
                'type' => 'INT(11)',
            ),
            array
            (
                'name' => 'level',
                'type' => 'INT(11)',
                'default' => '0'
            ),
        ),

        'keys' => array
        (
            'id' => array
            (
                'type' => 'PRIMARY',
                'name' => 'id',
                'fields' => array
                (
                    array
                    (
                        'name' => 'object_id',
                    ),
                    array
                    (
                        'name' => 'ancestor_id',
                    ),
                )
            ),
        ),
    ),

    'files' => array
    (
        'name' => 'files',
        'fields' => array
        (
            array
            (
                'name' => 'object_id',
                'type' => 'INT(11)',
            ),
            array
            (
                'name' => 'file_name',
                'type' => 'VARCHAR(255)',
            ),
            array
            (
                'name' => 'extra_info',
                'type' => 'TEXT',
            ),
            array
            (
                'name' => 'original_name',
                'type' => 'VARCHAR(255)',
            ),
            array
            (
                'name' => 'extension',
                'type' => 'VARCHAR(12)',
            ),
            array
            (
                'name' => 'secure',
                'type' => 'TINYINT(1)',
            ),
        ),

        'keys' => array
        (
            'object_id' => array
            (
                'type' => 'unique',
                'name' => 'object_id',
                'fields' => array
                (
                    array
                    (
                        'name' => 'object_id',
                    ),
                )
            ),
        ),
    ),

);

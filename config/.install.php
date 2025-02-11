<?php
/**
 * Этот файл является частью модуля веб-приложения GearMagic.
 * 
 * Файл конфигурации установки модуля.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

return [
    'use'         => BACKEND,
    'id'          => 'gm.be.mediafiles',
    'name'        => 'Media files',
    'description' => 'Manager for working with media data',
    'namespace'   => 'Gm\Backend\MediaFiles',
    'path'        => '/gm/gm.be.mediafiles',
    'route'       => 'mediafiles',
    'routes'      => [
        [
            'type'    => 'crudSegments',
            'options' => [
                'module'      => 'gm.be.mediafiles',
                'route'       => 'mediafiles',
                'prefix'      => BACKEND,
                'constraints' => ['id'],
                'defaults'    => [
                    'controller' => 'desk'
                ]
            ]
        ]
    ],
    'locales'     => ['ru_RU', 'en_GB'],
    'permissions' => ['any', 'view', 'read', 'info', 'settings'],
    'events'      => [],
    'required'    => [
        ['php', 'version' => '8.2'],
        ['app', 'code' => 'GM MS'],
        ['app', 'code' => 'GM CMS'],
        ['app', 'code' => 'GM CRM'],
        ['extension', 'id' => 'gm.be.references.media_folders'],
        ['extension', 'id' => 'gm.be.references.folder_profiles'],
        ['extension', 'id' => 'gm.be.references.media_dialogs']
    ]
];

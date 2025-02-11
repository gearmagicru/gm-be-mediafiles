<?php
/**
 * Этот файл является частью расширения модуля веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru/framework/
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

 namespace Gm\Backend\MediaFiles\Widget;

use Gm;
use Gm\Config\Config;
use Gm\Panel\Widget\DataPanel;

/**
 * Виджет отображения папок / файлов в виде списка.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Widget
 * @since 1.0
 */
class FileList extends DataPanel
{
    /**
     * {@inheritdoc}
     */
    protected function init(): void
    {
        parent::init();

        // маршрутизатор запросов (Gm.ActionRouter GmJS)
        $this->router->rules = [
            'paste'      => '{route}/paste',
            'delete'     => '{route}/delete',
            'data'       => '{route}/data',
            'deleteRow'  => '{route}/delete/{id}'
        ];
        $this->router->route = Gm::alias('@match', '/files');

        // источник данных (Ext.data.Store Sencha ExtJS)
        $this->store->model = [
            'fields' => [
                ['name' => 'name', 'type' => 'string'],
                ['name' => 'size', 'type' => 'string'],
                ['name' => 'type', 'type' => 'string'],
                ['name' => 'mimeType', 'type' => 'string'],
                ['name' => 'permissions', 'type' => 'string'],
                ['name' => 'accessTime', 'type' => 'date'],
                ['name' => 'changeTime', 'type' => 'date'],
                ['name' => 'isFolder', 'type' => 'bool'],
                ['name' => 'isImage', 'type' => 'bool'],
                ['name' => 'isArchive', 'type' => 'bool']
            ]
        ];
        $this->store->proxy['extraParams'] = ['view' => 'list'];
        // для локального поиска и сортировки записей
        $this->store->sorters = [
            ['property' => 'type', 'direction' => 'DESC']
        ];
        $this->store->autoLoad = false;

        // шаблон данных (Gm.view.data.View GmJS)
        $this->dataView->cls = 'gm-mediafiles-listview';
        $this->dataView->tpl = [
            '<tpl for=".">',
                '<div class="gm-mediafiles-listview__item {cls}" title="{name}">',
                    '<tpl if="isImage">',
                        '<div class="gm-mediafiles-listview__preview">',
                            '<img src="{preview}">',
                        '</div>',
                    '<tpl else>',
                        '<div class="gm-mediafiles-listview__wrap">',
                            '<div class="gm-mediafiles-listview__icon">',
                                '<img src="{icon}">',
                                '<tpl if="overlay">',
                                    '<div class="gm-mediafiles-listview__overlay" style="background-image: url({overlay})"></div>',
                                '</tpl>',        
                            '</div>',
                        '</div>',
                    '</tpl>',
                    '<div class="gm-mediafiles-listview__title">{name}</div>',
                '</div>',
            '</tpl>',
            '<div class="x-clear"></div>'
        ];
        $this->dataView->overItemCls = 'gm-mediafiles-listview__item_over';
        $this->dataView->selectedItemCls = 'gm-mediafiles-listview__item_selected';
        $this->dataView->itemSelector = 'div.gm-mediafiles-listview__item';
        $this->dataView->selectionModel = [
            'mode' => 'MULTI'
        ];
        $this->dataView->listeners = ['itemclick' => 'onFileClick'];
    }

    /**
     * Применение настроек модуля к интерфейсу виджета.
     * 
     * @param Config $settings Конфигуратор настроек модуля.
     * 
     * @return void
     */
    public function applySettings(Config $settings): void
    {
        // идентификатор корневой папки дерева
        $folderRootId = $settings->folderRootId ?: '';
        if ($folderRootId) {
            $this->store->proxy['extraParams']['path'] = $folderRootId;
        }

        // двойной клик на папке / файле
        if ($settings->dblClick) {
            $this->dataView->listeners ['itemdblclick'] = 'onFileDblClick';
        }

        // количество файлов и папок на странице
        $this->store->pageSize = $settings->gridPageSize;
    }

    /**
     * {@inheritdoc}
     */
    public function beforeRender(): bool
    {
        $this->makeViewID();
        return true;
    }
}

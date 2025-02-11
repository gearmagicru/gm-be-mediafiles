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
use Gm\Panel\Widget\Grid;
use Gm\Panel\Helper\ExtGrid;
use Gm\Panel\Helper\HtmlGrid;

/**
 * Виджет отображения папок / файлов в виде сетки.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Widget
 * @since 1.0
 */
class FileGrid extends Grid
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

        // для локального поиска и сортировки записей
        $this->store->sorters = [
            ['property' => 'type', 'direction' => 'DESC']
         ];
        $this->store->remoteFilter = false;
        $this->store->fields = [
            ['name' => 'name', 'type' => 'string'],
            ['name' => 'relName', 'type' => 'string'],
            ['name' => 'size', 'type' => 'string'],
            ['name' => 'type', 'type' => 'string'],
            ['name' => 'mimeType', 'type' => 'string'],
            ['name' => 'permissions', 'type' => 'string'],
            ['name' => 'accessTime', 'type' => 'date'],
            ['name' => 'changeTime', 'type' => 'date'],
            ['name' => 'isFolder', 'type' => 'bool'],
            ['name' => 'isImage', 'type' => 'bool'],
            ['name' => 'isArchive', 'type' => 'bool']
        ];
        $this->store->proxy['extraParams'] = ['view' => 'grid', 'path' => '', 'extraFilter' => []];

        // поле аудита записи
        $this->logField = 'name';
        // плагины сетки
        $this->plugins = 'gridfilters';
        // класс CSS применяемый к элементу body сетки
        $this->bodyCls = 'g-grid_background';

        // контекстное меню записи (Gm.view.grid.Grid.popupMenu GmJS)
        // добавляем каждому пункту меню свойство "viewEvent", определяющие имя обработчика
        // в контроллере. Обработчики будут связаны после рендера сетки.
        $this->popupMenu = [
            'width' => 200,
            'items' => [
                [
                    'text'      => '#View',
                    'iconCls'   => 'g-icon-svg gm-mediafiles__icon-view g-icon-m_color_neutral-dark',
                    'viewEvent' => 'onViewClick',
                ],
                [
                    'text'      => '#Edit',
                    'iconCls'   => 'g-icon-svg gm-mediafiles__icon-edit g-icon-m_color_neutral-dark',
                    'viewEvent' => 'onEditClick',
                ],
                [
                    'text'      => '#Download',
                    'iconCls'   => 'g-icon-svg gm-mediafiles__icon-download g-icon-m_color_neutral-dark',
                    'viewEvent' => 'onDownloadClick',
                ],
                [
                    'text'      => '#Rename',
                    'iconCls'   => 'g-icon-svg gm-mediafiles__icon-rename g-icon-m_color_neutral-dark',
                    'viewEvent' => 'onRenameClick',
                ],
                '-',
                [
                    'text'      => '#Archive',
                    'iconCls'   => 'g-icon-svg gm-mediafiles__icon-compress g-icon-m_color_neutral-dark',
                    'viewEvent' => 'onCompressClick',
                ],
                [
                    'text'      => '#Extract from archive',
                    'iconCls'   => 'g-icon-svg gm-mediafiles__icon-extract g-icon-m_color_neutral-dark',
                    'viewEvent' => 'onExtractClick',
                ],
                '-',
                [
                    'text'      => '#Permissions',
                    'iconCls'   => 'g-icon-svg gm-mediafiles__icon-permissions g-icon-m_color_neutral-dark',
                    'viewEvent' => 'onPermissionsClick',
                ],
                [
                    'text'      => '#Information',
                    'iconCls'   => 'g-icon-svg gm-mediafiles__icon-attributes g-icon-m_color_neutral-dark',
                    'viewEvent' => 'onAttributesClick',
                ],
                '-',
                [
                    'text'      => '#Delete',
                    'iconCls'   => 'g-icon-svg gm-mediafiles__icon-delete g-icon-m_color_neutral-dark',
                    'viewEvent' => 'onDeleteClick',
                ]
            ]
        ];

        // т.к. двойной клик на записи обрабатывается контроллером, то запрещаем
        $this->rowDblClickConfig = ['allow' => false];

        // т.к. в некоторых элементах (например, контекстное меню записи) сетки нельзя 
        // указать обработчики, то обработчики будут указаны самим контроллером после
        // рендера сетки
        $this->listeners = [
            'afterRender' => 'afterRenderGrid',
            'rowclick'    => 'onFileClick'
        ];
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

        // показывать линии между столбцами
        $this->columnLines  = $settings->showGridColumnLines;

        // показывать линии между строками
        $this->rowLines = $settings->showGridRowLines;

        // чередование строк
        $this->viewConfig = [
            'blockRefresh' => false, 
            'stripeRows'   => $settings->stripeGridRows
        ];

        // количество файлов и папок на странице
        $this->store->pageSize = $settings->gridPageSize;

        // показывать всплывающие меню
        if (!$settings->showPopupMenu) {
            $this->popupMenu = null;
        }

        // двойной клик на папке / файле
        if ($settings->dblClick) {
            $this->listeners['rowdblclick'] = 'onFileDblClick';
        }

        // столбцы
        $this->columns = [];
        $cellTipTags = [
            HtmlGrid::header('{name}'),
            HtmlGrid::tplIf(
                'isImage', 
                HtmlGrid::tag('div', '', [
                    'class' => 'gm-mediafiles__celltip-preview', 
                    'style' => 'background-image: url({preview})'
                ]), 
                ''
            ),
            HtmlGrid::tplIf(
                'relName',
                HtmlGrid::fieldLabel($this->creator->t('Full name'), '{relName}'),
                ''
            )
        ];
        // столбец "Размер"
        if ($settings->showSizeColumn) {
            $cellTipTags[] = HtmlGrid::fieldLabel($this->creator->t('Size'), '{size}');
            $this->columns[] = [
                'text'      => '#Size',
                'dataIndex' => 'size',
                'filter'    => ['type' => 'string'],
                'sortable'  => false,
                'width'     => 130
            ];
        }
        // столбец "Тип"
        if ($settings->showTypeColumn) {
            $cellTipTags[] = HtmlGrid::fieldLabel(
                $this->creator->t('Type'), 
                HtmlGrid::tplIf("type=='folder'", $this->creator->t('Folder'), $this->creator->t('File'))
            );
            $this->columns[] = [
                'xtype'     => 'templatecolumn',
                'text'      => '#Type',
                'dataIndex' => 'type',
                'tpl'       => HtmlGrid::tplIf("type=='folder'", $this->creator->t('Folder'), $this->creator->t('File')),
                'width'     => 130
            ];
        }
        // столбец "MIME-тип"
        if ($settings->showMimeTypeColumn) {
            $cellTipTags[] = HtmlGrid::fieldLabel($this->creator->t('MIME type'), '{mimeType}');
            $this->columns[] = [
                'text'      => '#MIME type',
                'dataIndex' => 'mimeType',
                'filter'    => ['type' => 'string'],
                'sortable'  => false,
                'width'     => 130
            ];
        }
        // столбец "Права доступа"
        if ($settings->showPermissionsColumn) {
            $cellTipTags[] = HtmlGrid::fieldLabel($this->creator->t('Permissions'), '{permissions}');
            $this->columns[] = [
                'text'      => '#Permissions',
                'dataIndex' => 'permissions',
                'cellTip'   => '{permissions}',
                'filter'    => ['type' => 'string'],
                'sortable'  => false,
                'width'     => 125
            ];
        }
        // столбец "Последний доступ"
        if ($settings->showAccessTimeColumn) {
            $cellTipTags[] = HtmlGrid::fieldLabel(
                $this->creator->t('Access time'), 
                '{accessTime:date("' . Gm::$app->formatter->formatWithoutPrefix('dateTimeFormat') . '")}'
            );
            $this->columns[] = [
                'xtype'     => 'datecolumn',
                'text'      => '#Access time',
                'tooltip'   => '#File last accessed time',
                'dataIndex' => 'accessTime',
                'filter'    => ['type' => 'date', 'dateFormat' => 'Y-m-d'],
                'format'    => Gm::$app->formatter->formatWithoutPrefix('dateTimeFormat'),
                'width'     => 155
            ];
        }
        // столбец "Последнее обновление"
        if ($settings->showChangeTimeColumn) {
            $cellTipTags[] = HtmlGrid::fieldLabel(
                $this->creator->t('Change time'), 
                '{changeTime:date("' . Gm::$app->formatter->formatWithoutPrefix('dateTimeFormat') . '")}'
            );
            $this->columns[] = [
                'xtype'     => 'datecolumn',
                'text'      => '#Change time',
                'tooltip'   => '#File last modified time',
                'dataIndex' => 'changeTime',
                'filter'    => ['type' => 'date', 'dateFormat' => 'Y-m-d'],
                'format'    => Gm::$app->formatter->formatWithoutPrefix('dateTimeFormat'),
                'width'     => 155
            ];
        }

        array_unshift($this->columns, 
        // столбец "Имя"
        [
            'xtype'     => 'templatecolumn',
            'text'      => ExtGrid::columnInfoIcon($this->creator->t('Name')),
            'cellTip'   => HtmlGrid::tags($cellTipTags),
            'tpl'       => $settings->showGridIcons ? 
                HtmlGrid::tplIf(
                    'iconCls', 
                    HtmlGrid::img(
                        HtmlGrid::imgDataSrc(), 
                        ['class' => 'g-icon-svg g-icon_size_16 {iconCls}', 'align' => 'absmiddle'], 
                        false
                    ), 
                    HtmlGrid::img(
                        '{icon}', 
                        ['class' => 'g-icon-svg g-icon_size_16', 'align' => 'absmiddle'], 
                        false
                    )
                ) . ' {name}' : '{name}',
            'dataIndex' => 'name',
            'filter'    => ['type' => 'string'],
            'width'     => 200
            ], 
            // столбец "Полное имя"
            [
                'text'      => '#Full name',
                'dataIndex' => 'relName',
                'cellTip'   => '{relName}',
                'tooltip'   => '#The full name includes the file name and its local path relative to the current folder',
                'filter'    => ['type' => 'string'],
                'sortable'  => false,
                'width'     => 125
            ]);

        // столбец управления меню
        if ($settings->showPopupMenu) {
            array_unshift($this->columns, ExtGrid::columnAction());
        }
    }
}

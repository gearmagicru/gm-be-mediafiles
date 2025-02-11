<?php
/**
 * Этот файл является частью модуля веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru/framework/
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

 namespace Gm\Backend\MediaFiles\Widget;

use Gm\Config\Config;
use Gm\Stdlib\Collection;

/**
 * Виджет панели дерева папок.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Widget
 * @since 1.0
 */
class FolderTree extends \Gm\Panel\Widget\Widget
{
    /**
     * {@inheritdoc}
     */
    public array $requires = [
        'Gm.view.tree.Tree',
        'Gm.view.plugin.PageSize'
    ];

    /**
     * Показывать кнопки управления медиапапками.
     * 
     * @var bool
     */
    public bool $useMediaTools = true;

    /**
     * {@inheritdoc}
     */
    public array $passParams = ['useMediaTools'];

    /**
     * {@inheritdoc}
     */
    public Collection|array $params = [
        /**
         * @var string Короткое название класса виджета.
         */
        'xtype' => 'g-tree',
        /**
         * @var bool false, чтобы скрыть корневой узел.
         */
        'rootVisible' => true,
        /**
         * @var string CSS класс панели.
         */
        'cls' => 'g-tree gm-mediafiles-tree ',
        /**
         * @var array Корневой узел дерева (Ext.data.Model | Ext.data.TreeModel).
         */
        'root' => [
            'id'       => '@media',
            'expanded' => false,
            'leaf'     => false
        ],
        /**
         * @var array|Collection Конфигурация маршрутизатора узлов дерева.
         */
        'router' => [
            'rules' => [
                'data' => '{route}/{id}'
            ],
            'route' => ''
        ],
        /**
         * @var array|Collection Конфигурация хранения записей сетки (Ext.data.Store).
         */
        'store' => [
            'nodeParam' => 'path',
            'autoLoad'  => true,
            'proxy'     => [
                'type'   => 'ajax',
                'url'    => '',
                'method' => 'POST',
                'reader' => [
                    'rootProperty'    => 'data',
                    'successProperty' => 'success'

                ]
            ]
        ],
        'singleExpand' => false
    ];

    /**
     * {@inheritdoc}
     */
    protected function init(): void
    {
        parent::init();

        $this->store  = Collection::createInstance($this->store);
        $this->root   = Collection::createInstance($this->root);
        $this->router = Collection::createInstance($this->router);
        $this->tbar   = Collection::createInstance([
            'cls'      => 'gm-mediafiles-toolbar',
            'padding'  => 8,
            'defaults' => [
                'xtype'  => 'button',
                'cls'    => 'gm-mediafiles-toolbar__btn',
                'width'  => 32,
                'height' => 32,
                'margin' => 1
            ],
            'items' => [
                [
                    'iconCls' => 'g-icon-svg gm-mediafiles__icon-expand g-icon-m_color_neutral-dark',
                    'tooltip' => '#Expand all folders',
                    'handler' => 'onExpandFolders'
                ],
                [
                    'iconCls' => 'g-icon-svg gm-mediafiles__icon-collapse g-icon-m_color_neutral-dark',
                    'tooltip' => '#Collapse all folders',
                    'handler' => 'onCollpaseFolders'
                ],
                [
                    'iconCls' => 'g-icon-svg gm-mediafiles__icon-refresh g-icon-m_color_neutral-dark',
                    'tooltip' => '#Refresh',
                    'handler' => 'onRefreshFolders'
                ]
            ]
        ]);
    }

    /**
     * Показывать кнопки управления медиапапками.
     * 
     * @return void
     */
    protected function addMediaTools(): void
    {
        $items = $this->tbar->items;
        $this->tbar->items = array_merge($items, [
            '->',
            [
                'iconCls' => 'g-icon-svg gm-mediafiles__icon-settings g-icon-m_color_neutral-dark',
                'tooltip' => '#Setting up media folders',
                'handler' => 'onFolderSettings'
            ],
            [
                'iconCls' => 'g-icon-svg gm-mediafiles__icon-edit g-icon-m_color_neutral-dark',
                'tooltip' => '#Edit media folder',
                'handler' => 'onEditFolder',
                'msgMustSelect' => '#You must select a media folder'
            ],
            [
                'iconCls' => 'g-icon-svg gm-mediafiles__icon-attributes g-icon-m_color_neutral-dark',
                'tooltip' => '#Edit profile media folder',
                'handler' => 'onEditFolderProfile',
                'msgMustSelect' => '#You must select a media folder',
                'msgNoProfile'  => '#Media folder does not have a profile',
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function beforeRender(): bool
    {
        $this->makeViewID();

        if ($this->useMediaTools) {
            $this->addMediaTools();
        }
        return true;
    }

    /**
     * Устанавливает положение.
     * 
     * @param string $position Положение, например: 'left', 'right'.
     * 
     * @return void
     */
    public function setPosition(string $position): void
    {
        if ($position === 'left') {
            $this->region = 'west';
            $this->margin = ' 5px 0 0 0';
        } else
        if ($position === 'right') {
            $this->region = 'east';
            $this->margin = ' 0 0 5px 0';
        }
    }

    /**
     * Применить настройки к параметрам виджета.
     * 
     * @param Config $settings Настройки.
     * 
     * @return void
     */
    public function applySettings(Config $settings): void
    {
        //  положение панели
        $this->setPosition($settings->treePosition);
        // размер панели
        $this->width = $settings->treeWidth;
        // показывать стрелочки
        $this->useArrows = $settings->useTreeArrows;
        // сортировать папки
        $this->folderSort = $settings->sortTreeFolders;
        // изменять размер панели
        if (!$settings->resizeTree) {
            $this->split = false;
        }
        // показывать панель инструментов
        if (!$settings->showTreeToolbar) {
            $this->tbar->hidden = true;
        }
        // показывать корень дерева
        if (!$settings->showTreeRoot) {
            $this->root->visible = false;
        }
        // показывать панель
        if ($settings->showTree) {
            /** @var \Gm\Backend\References\MediaFolders\Model\MediaFolder|null $folderRoot */
            $folderRoot = $this->creator->createMediaFolder()->getByAlias($settings->folderRootId);
            if ($folderRoot && $folderRoot->isVisible()) {
                // название медиапапки
                $this->root->text = $folderRoot->name;
                // CSS класс значка
                if ($folderRoot->iconCls) {
                    $this->root->iconCls = $folderRoot->iconCls;
                }
                // значок
                if ($folderRoot->smallIcon) {
                    $this->root->icon = $folderRoot->smallIcon;
                }
            // если корневая папка не отображается, то все скрыть и не загружать
            } else {
                $this->hidden = true;
                $this->store->autoLoad = false;
            }
        } else {
            $this->hidden = true;
            $this->store->autoLoad = false;
        }
    }
}

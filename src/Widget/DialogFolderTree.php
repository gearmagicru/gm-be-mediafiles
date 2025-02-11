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
class DialogFolderTree extends \Gm\Panel\Widget\Widget
{
    /**
     * {@inheritdoc}
     */
    public array $requires = [
        'Gm.view.tree.Tree',
        'Gm.view.plugin.PageSize'
    ];

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
        'xtype' => 'treepanel',
        /**
         * @var string CSS класс панели.
         */
        'cls' => 'g-tree gm-mediafiles-tree ',
        /**
         * @var array|Collection Конфигурация хранения записей сетки (Ext.data.Store).
         */
        'store' => [
            'root' => []
        ],
        /**
         * @var array Разделитель панели.
         */
        'split' => ['size' => 1]
    ];

    /**
     * {@inheritdoc}
     */
    protected function init(): void
    {
        parent::init();

        $this->store  = Collection::createInstance($this->store);
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
                ]
            ]
        ]);
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
            $this->rootVisible = false;
        }
        // показывать панель
        if (!$settings->showTree) {
            $this->hidden = true;
        }
    }
}

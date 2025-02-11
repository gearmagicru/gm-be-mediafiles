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
use Gm\Panel\Widget\TabWidget;

/**
 * Виджет основной панели файлового менеджера.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Widget
 * @since 1.0
 */
class Desk extends TabWidget
{
    /**
     * {@inheritdoc}
     */
    public array $css = [
        '/desk.css',
        '@module::/gm/gm.be.references.media_folders/assets/css/folders.css'
    ];

    /**
     * Виджет панели дерева папок.
     * 
     * @var FolderTree
     */
    public FolderTree $folderTree;

    /**
     * Виджет панели отображения файлов.
     * 
     * @var FilePanel
     */
    public FilePanel $filePanel;

    /**
     * {@inheritdoc}
     */
    public array $requires = [
        'Gm.be.mediafiles.FilePanel',
        'Gm.be.mediafiles.FolderTreeController',
        'Gm.be.mediafiles.FilePanelController'
    ];

    /**
     * {@inheritdoc}
     */
    protected function init(): void
    {
        parent::init();

        $this->initFolderTree();
        $this->initFilePanel();

        // окно компонента (Ext.window.Window Sencha ExtJS)
        $this->layout = 'border';
        $this->title = '#{name}';
        $this->tooltip = [
            'icon'  => $this->imageSrc('/icon.svg'),
            'title' => '#{name}',
            'text'  => '#{description}'
        ];
        $this->icon = $this->imageSrc('/icon_small.svg');
        $this->items = [$this->folderTree, $this->filePanel];

        $this->setNamespaceJS('Gm.be.mediafiles');
    }

    /**
     * Инициализация панели отображения медиафайлов.
     *
     * @return void
     */
    protected function initFilePanel(): void
    {
        // панель отображения медиафайлов (Ext.tree.Panel Sencha ExtJS)
        $this->filePanel = new FilePanel([
            'id'         => 'filepanel', // view => gm-mediafiles-filepanel
            'controller' => 'gm-be-mediafiles-filepanel',
            'baseRoute'  => Gm::alias('@match')
        ]);
        $this->filePanelId = $this->filePanel->makeViewID();

        /** @var \Gm\Backend\References\MediaFolders\Model\MediaFolder|null $mediaFolder */
        $mediaFolder = $this->creator->createMediaFolder();
        // указываем для навигации названия медиапапок
        $this->filePanel->breadcrumbs->folders = $mediaFolder->getBreadcrumbs();
    }

    /**
     * Инициализация панели дерева папок.
     *
     * @return void
     */
    protected function initFolderTree(): void
    {
        // панель дерева папок (Gm.view.tree.Tree GmJS)
        $this->folderTree = new FolderTree([
            'id'        => 'tree', // tree => gm-mediafiles-tree
            'split'     => ['size' => 2],
            'useArrows' => true,
            'router' => [
                'rules' => [
                    'data' => '{route}/data'
                ],
                'route' => Gm::alias('@route', '/folders')
            ],
            'controller' => 'gm-be-mediafiles-foldertree'
        ]);
        $this->folderTreeId = $this->folderTree->makeViewID();
    }
}

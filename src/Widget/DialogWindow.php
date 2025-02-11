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
use Gm\Stdlib\Collection;
use Gm\Panel\Widget\Widget;
use Gm\Backend\References\MediaDialogs\Model\MediaDialog;

/**
 * Виджет для формирования интерфейса диалогового окна выбора медиафайлов.
 * 
 * @see Gm\Panel\Widget\Window
 * @see https://docs.sencha.com/extjs/5.1.2/api/Ext.window.Window.html
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Widget
 * @since 1.0
 */
class DialogWindow extends Widget
{
    /**
     * Медиа диалог.
     * 
     * @see DialogWindow::$passParams
     * 
     * @var MediaDialog
     */
    protected MediaDialog $mediaDialog;

    /**
     * Короткий путь к вызываемой папке дилога.
     * 
     * @see DialogWindow::$passParams
     * 
     * @var array
     */
    protected string $mediaPath;

    /**
     * {@inheritdoc}
     */
    public array $passParams = ['mediaDialog', 'mediaPath'];

    /**
     * {@inheritdoc}
     */
    public Collection|array $params = [
        /**
         * @var string Уникальный идентификатор окна (dialog => gm-mediafiles-dialog).
         */
        'id' => 'dialog',
        /**
         * @var string Короткое название класса диалога.
         */
        'xtype' => 'g-window',
        /**
         * @var string Класс CSS, который будет добавлен к диалогу.
         */
        'cls'=> 'g-window',
        /**
         * @var string Макет диалогового окна.
         */
        'layout' => 'border',
        /**
         * @var string Класс CSS, который будет добавлен к телу окна диалога.
         */
        'bodyCls' => 'gm-mediafiles-dialog',
        /**
         * @var bool Позволяет закрыть окно.
         */
        'closable' => true,
        /**
         * @var bool Возможность развернуть окно.
         */
        'maximizable' => true,
        /**
         * @var string Заголовок диалогового окна.
         */
        'title' => '#{name}',
        /**
         * @var string Имя контроллера диалога.
         */
        'controller' => 'gm-be-mediafiles-dialog',
        /**
         * @var bool Делает окно модальным.
         */
        'modal' => true,
        /**
         * @var array Массив виджетов окна.
         */
        'items' => [],
        /**
         * @var int|string Ширина окна.
         */
        'width' => 1200,
        /**
         * @var int|string Высота окна.
         */
        'height' => '80%',
        /**
         * @var array Параметры адаптивного размера.
         */
        'responsiveConfig' => [
            'height < 800' => ['height' => '99%'],
            'width < 1200' => ['width' => '99%'],
        ],
        /**
         * @var string Пседвоним диалога, например: 'article-image', 'article-doc'. 
         * Для получения настроек диалога из базы данных.
         */
        'dialogAlias' => '',
        /**
         * @var string Параметры выбора файла.
         */
        'browse' => [
            'applyTo'    => null, // идент. поля для подстановки выбранного файла
            'field'      => 'browse', // идент. поля для отображения выбранного файла
            'selectType' => '', // выбирать из панели файлов элементы только с типом: 'image'
            'stripe'     => '' // убрать часть пути ("/public") при подстановке значения в поле
        ],
        /**
         * @var string Сообщение о выборе файла.
         */
        'msgEmptyDialogFile' => '#You must select a file',
        /**
         * @var array Кнопки.
         */
        'buttons' => [
            [
                'text'    => '#Choose',
                'handler' => 'onChoose'
            ],
            [
                'ui'      => 'form-close',
                'text'    => '#Cancel',
                'handler' => 'onCancel'
            ]
        ]
    ];

    /**
     * Виджет панели дерева папок.
     * 
     * @var DialogFolderTree
     */
    public DialogFolderTree $folderTree;

    /**
     * Виджет панели отображения файлов.
     * 
     * @var FilePanel
     */
    public FilePanel $filePanel;

    /**
     * Конфигурация маршрутизатора формы.
     * 
     * @var array|Collection
     */
    public Collection $footer;

    /**
     * Конфигурация поля выбора файла.
     * 
     * @var Collection
     */
    public Collection $browseField;

    /**
     * {@inheritdoc}
     */
    public array $css = [
        '/desk.css',
        '@module::/gm/gm.be.references.media_folders/assets/css/folders.css'
    ];

    /**
     * {@inheritdoc}
     */
    public array $requires = [
        'Gm.view.window.Window',
        'Gm.be.mediafiles.FilePanel',
        'Gm.be.mediafiles.FolderTreeController',
        'Gm.be.mediafiles.FilePanelController',
        'Gm.be.mediafiles.DialogController'
    ];

    /**
     * {@inheritdoc}
     */
    protected function init(): void
    {
        parent::init();

        $this->makeViewID();

        $this->initFolderTree();
        $this->initFilePanel();
        $this->initFooter();

        // окно компонента (Ext.window.Window Sencha ExtJS)
        $this->tooltip = [
            'icon'  => $this->imageSrc('/icon.svg'),
            'title' => '#{name}',
            'text'  => '#{description}'
        ];
        // значок диалога
        $icon = $this->mediaDialog->getIcon();
        if ($icon) {
            $this->iconCls = $icon;//$this->imageSrc('/icon_small.svg');
        }
        $this->items = [
            $this->folderTree, 
            $this->filePanel, 
            $this->footer
        ];
        $this->browse['field'] = $this->id . '-browse';

        $this->setNamespaceJS('Gm.be.mediafiles');
    }

    /**
     * Инициализация панели отображения файлов.
     *
     * @return void
     */
    protected function initFilePanel(): void
    {
        // панель отображения файлов (Ext.tree.Panel Sencha ExtJS)
        $this->filePanel = new FilePanel([
            'id'         => 'filepanel-d', // view => gm-mediafiles-filepanel-d
            'controller' => 'gm-be-mediafiles-filepanel',
            'baseRoute'  => Gm::alias('@match')
        ]);
        $this->filePanel->list->id = 'filelist-d'; // filelist => gm-mediafiles-filelist
        $this->filePanel->grid->id = 'filegrid-d'; // filegrid => gm-mediafiles-filegrid
        // панель инструментов
        $this->filePanel->toolbar = $this->mediaDialog->toolbar ?: '';
        // убрать заливку фона
        $this->filePanel->grid->bodyCls = '';
        // выбирать только одну запись
        $this->filePanel->grid->selModel = ['mode' => 'SINGLE'];
        $this->filePanel->list->dataView->selModel = ['mode' => 'SINGLE'];
        // передавать каждым запросом псевдоним диалога
        $this->filePanel->grid->store->proxy['extraParams']['dialog'] = $this->mediaDialog->alias;
        $this->filePanel->list->store->proxy['extraParams']['dialog'] = $this->mediaDialog->alias;
        // идентификатор панели файлов (применяет Gm.be.mediafiles.FilePanelController)
        $this->filePanelId = $this->filePanel->makeViewID();

        /** @var \Gm\Backend\References\MediaFolders\Model\MediaFolder|null $mediaFolder */
        $mediaFolder = $this->creator->createMediaFolder();
        /** @var array<string, array> $breadcrumbs Навигационная цепочка */
        $breadcrumbs = $mediaFolder->getBreadcrumbs();
        /** @var array|null $dialogFolder Параметры папки диалога (если она есть) */
        $dialogFolder = $this->mediaDialog->getDialogFolder(['mediaPath' => $this->mediaPath]);
        if ($dialogFolder) {
            $breadcrumbs[$dialogFolder['id']] = [
                'alias' => $dialogFolder['id'],
                'title' => $dialogFolder['text']
            ];
        }
        // указываем для навигации названия медиапапок
        $this->filePanel->breadcrumbs->folders = $breadcrumbs;
    }

    /**
     * Инициализация панели дерева папок.
     *
     * @return void
     */
    protected function initFolderTree(): void
    {
        // панель дерева папок (Gm.view.tree.Tree GmJS)
        $this->folderTree = new DialogFolderTree([
            'id'         => 'foldertree-d', // foldertree-d => gm-mediafiles-foldertree-d
            'controller' => 'gm-be-mediafiles-foldertree',
            'store'      => [
                // дерево медиапапок доступные диалогу
                'root' => $this->mediaDialog->getDialogFolderTree(['mediaPath' => $this->mediaPath])
            ]
        ]);
        // идентификатор панели дерева папок (применяет Gm.be.mediafiles.FilePanelController)
        $this->folderTreeId = $this->folderTree->makeViewID();
    }

    /**
     * @return void
     */
    protected function initFooter(): void
    {
        $this->browseField = Collection::createInstance([
            'id'         => $this->id . '-browse',
            'xtype'      => 'textfield',
            'fieldLabel' => '#Image file',
            'labelAlign' => 'right',
            'labelWidth' => 300,
            'readOnly'   => true
        ]);
        $this->footer = Collection::createInstance([
            'xtype'      => 'container',
            'cls'        => 'gm-mediafiles-footer',
            'layout'     => 'form',
            'region'     => 'south',
            'margin'     => '0 0 0 10',
            'autoHeight' => true,
            'items'      => [
                $this->browseField
            ]
        ]);
    }
}

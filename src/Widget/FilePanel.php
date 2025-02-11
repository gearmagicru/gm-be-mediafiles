<?php
/**
 * Этот файл является частью модуля веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru/framework/
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

 namespace Gm\Backend\MediaFiles\Widget;

use Gm;
use Gm\Helper\Url;
use Gm\Config\Config;
use Gm\Stdlib\Collection;
use Gm\Panel\Widget\Widget;
use Gm\Panel\Helper\HtmlGrid;
use Gm\Panel\Widget\Navigator;
use Gm\Panel\Helper\HtmlNavigator as HtmlNav;

/**
 * Виджет панели отображения папок и файлов в виде сетки и списка.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Widget
 * @since 1.0
 */
class FilePanel extends Widget
{
    /**
     * {@inheritdoc}
     */
    public Collection|array $params = [
        /**
         * @var string Короткое название класса виджета.
         */
        'xtype'  => 'gm-be-mediafiles-filepanel',
        'region' => 'center',
        'layout' => 'fit',
        'msgMustSelect'        => '#You must select a file or folder',
        'msgMustSelectFile'    => '#You must select a file',
        'msgMustSelectOne'     => '#Only one file or folder needs to be selected',
        'msgMustSelectArchive' => '#You only need to select the archive file',
        'msgUnselectMFolders'  => '#You need to remove selections from elements - media folder',
        'msgDelConfirm'        => '#Are you sure you want to delete the selected files / folders ({0} pcs)? {1}',
        'msgDelConfirmFolders' => '#Are you sure you want to delete the selected folders ({0} pcs)? {1}',
        'msgDelConfirmFolder'  => '#Are you sure you want to delete the folder "{0}"?',
        'msgDelConfirmFiles'   => '#Are you sure you want to delete the selected files ({0} pcs)? {1}',
        'msgDelConfirmFile'    => '#Are you sure you want to delete the file "{0}"?',
        'msgCannotPasteFiles'  => '#Cannot paste files where they were copied or cut from',
        'msgCopyClipboard'     => '#Files / folders copied to clipboard',
        'msgCutClipboard'      => '#Files / folders cut to clipboard',
        'titleClipboard'       => '#Clipboard',
    ];

    /**
     * Виджет отображения папок / файлов в виде сетки.
     * 
     * @var FileGrid
     */
    public FileGrid $grid;

    /**
     * Виджет отображения папок / файлов в виде списка.
     * 
     * @var FileList
     */
    public FileList $list;

    /**
     * @see FilePanel::initBreadcrumbs()
     * 
     * @var Collection
     */
    public Collection $breadcrumbs;

    /**
     * Панель инструментов.
     * 
     * @var string
     */
    public string $toolbar = 'home,goup,-,creatFolder,delete,-,refresh,search,profile,-,selectAll,unselect,
inselect,-,upload,download,-,compress,extract,-,rename,view,perms,attr,-,grid,list,-,help,settings';

    /**
     * {@inheritdoc}
     */
    protected function init(): void
    {
        parent::init();

        $this->initGrid();
        $this->initList();

        $this->makeViewID(); // для того, чтобы сразу использовать `$this->id`
        $this->items = [$this->grid, $this->list];

        $this->initBreadcrumbs();

        // панель навигации (Gm.view.navigator.Info GmJS)
        $this->navigator = new Navigator();
        $this->navigator->show = ['g-navigator-modules', 'g-navigator-info'];
        $this->navigator->info['tpl'] = HtmlNav::tags([
            HtmlNav::header('{name}'),
            HtmlNav::tplIf(
                'isImage', 
                HtmlGrid::tag('div', '', [
                    'class' => 'gm-mediafiles__celltip-preview', 
                    'style' => 'background-image: url({preview})'
                ]), 
                ''
            ),
            HtmlNav::tplIf(
                'relName',
                HtmlNav::fieldLabel($this->creator->t('Full name'), '{relName}'),
                ''
            ),
            HtmlNav::fieldLabel($this->creator->t('Size'), '{size}'),
            HtmlNav::fieldLabel(
                 $this->creator->t('Type'), 
                 HtmlNav::tplIf("type=='folder'", $this->creator->t('Folder'), $this->creator->t('File'))
            ),
            HtmlNav::fieldLabel($this->creator->t('MIME type'), '{mimeType}'),
            HtmlNav::fieldLabel($this->creator->t('Permissions'), '{permissions}'),
            HtmlNav::fieldLabel(
                $this->creator->t('Access time'), 
                '{accessTime:date("' . Gm::$app->formatter->formatWithoutPrefix('dateTimeFormat') . '")}'
            ),
            HtmlNav::fieldLabel(
                $this->creator->t('Change time'), 
                '{changeTime:date("' . Gm::$app->formatter->formatWithoutPrefix('dateTimeFormat') . '")}'
            )
        ]);
    }

    /**
     * Инициализация панели инструментов виджета.
     * 
     * @return void
     */
    protected function initToolbar(): void
    {
        if (empty($this->toolbar)) return;

        $items = [];
        $buttons = explode(',', $this->toolbar);
        foreach ($buttons as $button) {
            if ($button === '-')
                $items[] = '-';
            else {
                $name = 'button' . $button;
                if (method_exists($this, $name)) {
                    $items[] = $this->$name();
                }
            }
        }

        // панель инструментов (Ext.toolbar.Toolbar Sencha ExtJS)
        $this->dockedItems = [
            $this->breadcrumbs,
            [
                'id'    => $this->id . '__toolbar',
                'xtype' => 'toolbar',
                'dock'  => 'top',
                'cls'      => 'gm-mediafiles-toolbar',
                'defaults' => [
                    'xtype'  => 'button',
                    'cls'    => 'gm-mediafiles-toolbar__btn',
                    'width'  => 32,
                    'height' => 32,
                    'margin' => 1
                ],
                'items' => $items
            ]
        ];
    }

    /**
     * Инициализация виджета отображения папок / файлов в виде списка.
     * 
     * @return void
     */
    protected function initList(): void
    {
        $this->list = new FileList([
            'id' => 'filelist', // filelist => gm-mediafiles-filelist
        ]);
    }

    /**
     * Инициализация виджета отображения папок / файлов в виде сетки.
     * 
     * @return void
     */
    protected function initGrid(): void
    {
        $this->grid = new FileGrid([
            'id' => 'filegrid' // filegrid => gm-mediafiles-filegrid
        ]);
    }

    /**
     * @return void
     */
    protected function initBreadcrumbs(): void
    {
        $this->breadcrumbs = Collection::createInstance([
            'id'    => $this->id . '__breadcrumbs',
            'xtype' => 'gm-be-mediafiles-breadcrumbs',
            'dock'  => 'top',
            'listeners' => [
                'itemClick' => 'onBreadcrumbClick'
            ]
        ]);

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
        $this->grid->applySettings($settings);
        $this->list->applySettings($settings);

        // идентификатор корневой папки дерева
        $this->folderRootId = $settings->folderRootId;
        $this->path = $this->folderRootId;
        $this->fbar['path'] = $this->folderRootId;

        // панель навигации (Gm.view.navigator.Info GmJS)
        $infoTpl = [
            HtmlNav::header('{name}'),
            HtmlNav::tplIf(
                'isImage', 
                HtmlNav::tag('div', '', [
                    'class' => 'gm-mediafiles__celltip-preview', 
                    'style' => 'background-image: url({preview})'
                ]), 
                HtmlNav::tag('div', '', [
                    'class' => 'gm-mediafiles__celltip-icon', 
                    'style' => 'background-image: url({icon})'
                ]), 
            ),
            HtmlNav::tplIf(
                'relName',
                HtmlNav::fieldLabel($this->creator->t('Full name'), '{relName}'),
                ''
            )
        ];
        // столбец "Размер"
        if ($settings->showSizeColumn) {
            $infoTpl[] = HtmlNav::fieldLabel($this->creator->t('Size'), '{size}');
        }
        // столбец "Тип"
        if ($settings->showTypeColumn) {
            $infoTpl[] = HtmlNav::fieldLabel(
                $this->creator->t('Type'), 
                HtmlNav::tplIf("type=='folder'", $this->creator->t('Folder'), $this->creator->t('File'))
            );
        }
        // столбец "MIME-тип"
        if ($settings->showMimeTypeColumn) {
            $infoTpl[] = HtmlNav::fieldLabel($this->creator->t('MIME type'), '{mimeType}');
        }
        // столбец "Права доступа"
        if ($settings->showPermissionsColumn) {
            $infoTpl[] = HtmlNav::fieldLabel($this->creator->t('Permissions'), '{permissions}');
        }
        // столбец "Последний доступ"
        if ($settings->showAccessTimeColumn) {
            $infoTpl[] = HtmlNav::fieldLabel(
                $this->creator->t('Access time'), 
                '{accessTime:date("' . Gm::$app->formatter->formatWithoutPrefix('dateTimeFormat') . '")}'
            );
        }
        // столбец "Последнее обновление"
        if ($settings->showChangeTimeColumn) {
            $infoTpl[] = HtmlNav::fieldLabel(
                $this->creator->t('Change time'), 
                '{changeTime:date("' . Gm::$app->formatter->formatWithoutPrefix('dateTimeFormat') . '")}'
            );
        }
        $this->navigator->info['tpl'] = HtmlNav::tags($infoTpl);
    }

    /**
     * {@inheritdoc}
     */
    public function beforeRender(): bool
    {
        $this->initToolbar();
        return true;
    }

    /**
     * Возвращает конфигурацию кнопки "Корневая папка" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonHome(): array
    {
        return  [
            'id'      => $this->id . '__btnHome',
            'iconCls' => 'g-icon-svg gm-mediafiles__icon-home g-icon-m_color_neutral-dark',
            'tooltip' => '#Home',
            'handler' => 'onHomeClick'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Перейти на уровень выше" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonGoUp(): array
    {
        return  [
            'id'      => $this->id . '__btnGoUp',
            'iconCls' => 'g-icon-svg gm-mediafiles__icon-up g-icon-m_color_neutral-dark',
            'tooltip' => '#Go up one level',
            'handler' => 'onGoUpClick'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Создать папку" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonCreateFolder(): array
    {
        return  [
            'id'      => $this->id . '__btnCreateFolder',
            'iconCls' => 'g-icon-svg gm-mediafiles__icon-create_folder',
            'tooltip' => '#Create folder',
            'handler' => 'onCreateFolderClick'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Создать файл" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonCreateFile(): array
    {
        return  [
            'id'      => $this->id . '__btnCreateFile',
            'iconCls' => 'g-icon-svg gm-mediafiles__icon-create_file',
            'tooltip' => '#Create file',
            'handler' => 'onCreateFileClick'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Удалить выбранные файлы и папки" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonDelete(): array
    {
        return  [
            'id'      => $this->id . '__btnDelete',
            'iconCls' => 'g-icon-svg gm-mediafiles__icon-delete g-icon-m_color_neutral-dark',
            'tooltip' => '#Delete selected folders / files',
            'handler' => 'onDeleteClick'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Обновить" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonRefresh(): array
    {
        return  [
            'id'      => $this->id . '__btnRefresh',
            'iconCls' => 'g-icon-svg gm-mediafiles__icon-refresh g-icon-m_color_neutral-dark',
            'tooltip' => '#Refresh',
            'handler' => 'onRefreshClick'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Поиск папки / файла" (Ext.button.Split ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonSearch(): array
    {
        return  [
            'id'      => $this->id . '__btnSearch',
            'xtype'   => 'splitbutton',
            'iconCls' => 'g-icon-svg gm-mediafiles__icon-find g-icon-m_color_neutral-dark',
            'tooltip' => '#Search for folder / file',
            'width'   => 50,
            'menu'    => [
                'items' => [
                    'xtype'       => 'form',
                    'action'      =>  Url::toMatch('files/filter'),
                    'cls'         => 'g-form-filter',
                    'flex'        => 1,
                    'width'       => 400,
                    'height'      => 180,
                    'bodyPadding' => 8,
                    'defaults'    => [
                        'labelAlign' => 'right',
                        'labelWidth' => 100,
                        'width'      => '100%'
                    ],
                    'items' => [
                        [
                            'xtype'      => 'textfield',
                            'name'       => 'name',
                            'fieldLabel' => '#Search name',
                        ],
                        [
                            'xtype'      => 'radio',
                            'name'       => 'type',
                            'inputValue' => 'file',
                            'padding'    => '0 0 0 100px',
                            'boxLabel'   => '#find File',
                            'checked'    => true
                        ],
                        [
                            'xtype'      => 'radio',
                            'name'       => 'type',
                            'inputValue' => 'folder',
                            'padding'    => '0 0 0 100px',
                            'boxLabel'   => '#find Path'
                        ]
                    ],
                    'buttons' => [
                        [
                            'text'    => '#Find',
                            'handler' => 'onSearch'
                        ], 
                        [
                            'text'    => '#Reset',
                            'handler' => 'onSearchReset'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Профилирование папки / файла" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonProfile(): array
    {
        return [
            'iconCls'         => 'g-icon-svg gm-mediafiles__icon-profile_on g-icon-m_color_neutral-dark',
            'activeIconCls'   => 'g-icon-svg gm-mediafiles__icon-profile_on g-icon-m_color_neutral-dark',
            'inactiveIconCls' => 'g-icon-svg gm-mediafiles__icon-profile_off g-icon-m_color_neutral-dark',
            'tooltip'         => '#Profiling a folder / file',
            'enableToggle'    => true,
            'toggleHandler'   => 'onProfilingClick'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Выделить всё" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonSelectAll(): array
    {
        return  [
            'iconCls' => 'g-icon-svg gm-mediafiles__icon-select g-icon-m_color_neutral-dark',
            'tooltip' => '#Select all',
            'handler' => 'onSelectAllClick'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Инвертировать выделение" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonInSelect(): array
    {
        return  [
            'iconCls' => 'g-icon-svg gm-mediafiles__icon-inselect g-icon-m_color_neutral-dark',
            'tooltip' => '#Invert selection',
            'handler' => 'onInvertSelectionClick'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Убрать выделение" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonUnSelect(): array
    {
        return  [
            'iconCls' => 'g-icon-svg gm-mediafiles__icon-unselect g-icon-m_color_neutral-dark',
            'tooltip' => '#Remove selection',
            'handler' => 'onRemoveSelectionClick'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Загрузить" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonUpload(): array
    {
        return  [
            'id'      => $this->id . '__btnUpload',
            'iconCls' => 'g-icon-svg gm-mediafiles__icon-upload g-icon-m_color_neutral-dark',
            'tooltip' => '#Upload file',
            'handler' => 'onUploadClick'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Скачать" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonDownload(): array
    {
        return [
            'id'      => $this->id . '__btnDonwload',
            'iconCls' => 'g-icon-svg gm-mediafiles__icon-download g-icon-m_color_neutral-dark',
            'tooltip' => '#Download selected folders / files',
            'handlerArgs' => [
                'route' => Gm::getAlias('@match/download/prepare')
            ],
            'handler' => 'onDownloadClick'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Архивировать" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonCompress(): array
    {
        return [
            'id'      => $this->id . '__btnCompress',
            'iconCls' => 'g-icon-svg gm-mediafiles__icon-compress g-icon-m_color_neutral-dark',
            'tooltip' => '#Archive',
            'handler' => 'onCompressClick'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Разархивировать" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonExtract(): array
    {
        return [
            'id'      => $this->id . '__btnExtract',
            'iconCls' => 'g-icon-svg gm-mediafiles__icon-extract g-icon-m_color_neutral-dark',
            'tooltip' => '#Extract from archive',
            'handler' => 'onExtractClick'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Переименовать" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonRename(): array
    {
        return [
            'id'      => $this->id . '__btnRename',
            'iconCls' => 'g-icon-svg gm-mediafiles__icon-rename g-icon-m_color_neutral-dark',
            'tooltip' => '#Rename',
            'handler' => 'onRenameClick'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Редактировать" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonEdit(): array
    {
        return [
            'id'      => $this->id . '__btnEdit',
            'iconCls' => 'g-icon-svg gm-mediafiles__icon-edit g-icon-m_color_neutral-dark',
            'tooltip' => '#Edit file',
            'handler' => 'onEditClick'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Просмотреть файл" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonView(): array
    {
        return [
            'id'      => $this->id . '__btnView',
            'iconCls' => 'g-icon-svg gm-mediafiles__icon-view g-icon-m_color_neutral-dark',
            'tooltip' => '#View file',
            'handler' => 'onViewClick'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Права доступа" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonPerms(): array
    {
        return [
            'id'      => $this->id . '__btnPerms',
            'iconCls' => 'g-icon-svg gm-mediafiles__icon-permissions g-icon-m_color_neutral-dark',
            'tooltip' => '#Permissions',
            'handler' => 'onPermissionsClick'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Информация о выбранной папке / файле" (Ext.button.Button 
     * ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonAttr(): array
    {
        return [
            'id'      => $this->id . '__btnAttr',
            'iconCls' => 'g-icon-svg gm-mediafiles__icon-attributes g-icon-m_color_neutral-dark',
            'tooltip' => '#Information about the selected folder/file',
            'handler' => 'onAttributesClick'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Переместить выделенные папки / файлы в буфер 
     * обмена" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonCut(): array
    {
        return [
            'id'      => $this->id . '__btnCut',
            'iconCls' => 'g-icon-svg gm-mediafiles__icon-cut g-icon-m_color_neutral-dark',
            'tooltip' => '#Move selected folders / files to clipboard',
            'handler' => 'onCutClick'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Копировать выделенные папки / файлы" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonCopy(): array
    {
        return [
            'id'      => $this->id . '__btnCopy',
            'iconCls' => 'g-icon-svg gm-mediafiles__icon-copy g-icon-m_color_neutral-dark',
            'tooltip' => '#Copy selected folders / files',
            'handler' => 'onCopyClick'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Вставить" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonPaste(): array
    {
        return [
            'id'       => $this->id . '__btnPaste',
            'iconCls'  => 'g-icon-svg gm-mediafiles__icon-paste',
            'tooltip'  => '#Paste the contents of the buffer into the current folder',
            'handler'  => 'onPasteClick',
            'disabled' => true
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Сетка" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonGrid(): array
    {
        return  [
            'iconCls'      => 'g-icon-svg gm-mediafiles__icon-grid g-icon-m_color_neutral-dark',
            'tooltip'      => '#Grid',
            'pressed'      => true,
            'enableToggle' => true,
            'toggleGroup'  => 'view',
            'handler'      => 'onToggleGrid'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Сетка" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonList(): array
    {
        return  [
            'iconCls'      => 'g-icon-svg gm-mediafiles__icon-list g-icon-m_color_neutral-dark',
            'tooltip'      => '#List',
            'enableToggle' => true,
            'toggleGroup'  => 'view',
            'handler'      => 'onToggleList'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Справка" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonHelp(): array
    {
        return [
            'iconCls'     => 'g-icon-svg gm-mediafiles__icon-info g-icon-m_color_neutral-dark',
            'tooltip'     => '#Help',
            'handlerArgs' => [
                'route' => Gm::alias('@backend', '/guide/modal/view?component=module:' . $this->creator->getId() . '&subject=index')
            ],
            'handler'     => 'onHelpClick'
        ];
    }

    /**
     * Возвращает конфигурацию кнопки "Настройки" (Ext.button.Button ExtJS).
     * 
     * @return array<string, mixed>
     */
    protected function buttonSettings(): array
    {
        return [
            'iconCls'     => 'g-icon-svg gm-mediafiles__icon-settings g-icon-m_color_neutral-dark',
            'tooltip'     => '#Settings',
            'handlerArgs' => ['route' => Gm::alias('@match', '/settings/view')],
            'handler'     => 'onSettingsClick'
        ];
    }
}
<?php
/**
 * Этот файл является частью модуля веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Backend\MediaFiles\Model;

use Gm;
use SplFileInfo;
use Gm\Helper\Json;
use Gm\Config\Mimes;
use Gm\Stdlib\Collection;
use Gm\Filesystem\Finder;
use Gm\Mvc\Module\BaseModule;
use Gm\Panel\Data\Model\FilesGridModel;
use Gm\Backend\References\MediaFolders\Model\MediaFolder;
use Gm\Backend\References\FolderProfiles\Model\FolderProfile;

/**
 * Модель данных сетки / списка отображения файлов.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Model
 * @since 1.0
 */
class Files extends FilesGridModel
{
    /**
     * @var int Отображение в виде сетки.
     */
    public const GRID_VIEW = 'grid';

    /**
     * @var int Отображение в виде списка.
     */
    public const LIST_VIEW = 'list';

    /**
     * {@inheritdoc}
     * 
     * @var BaseModule|\Gm\Backend\MediaFiles\Module
     */
    public ?BaseModule $module;

    /**
     * Псевдоним (виртуальная папка) пути.
     * 
     * Псевдоним установлен, если значение свойства {@see FileGridModel::$path} имеет символ '@'.
     * Псевдоним это виртуальная папка и может иметь ссылку на реальную папку.
     * 
     * @see Files::definePath()
     * 
     * @var MediaFolder|null
     */
    public ?MediaFolder $mediaFolder = null;

    /**
     * @see Files::definePath()
     * 
     * @var FolderProfile|null
     */
    public ?FolderProfile $folderProfile = null;

    /**
     * @see Files::definePath()
     * 
     * @var Collection|null
     */
    protected ?Collection $folderOptions = null;

    /**
     * {@inheritdoc}
     */
    public array $attributes = [
        self::ATTR_ID     => 'id',
        self::ATTR_NAME   => 'name',
        self::ATTR_RNAME  => 'relName',
        self::ATTR_TYPE   => 'type',
        self::ATTR_ACTIME => 'accessTime',
        self::ATTR_CHTIME => 'changeTime',
        self::ATTR_PERMS  => 'permissions',
        self::ATTR_SIZE   => 'size',
        self::ATTR_MIME   => 'mimeType',
    ];

    /**
     * {@inheritdoc}
     */
    public array $defaultOrder = ['type' => self::SORT_DESC];

    /**
     * {@inheritdoc}
     */
    public string $defaultPath = '@media';

    /**
     * Вид отображения файлов и папок.
     * 
     * @var string
     */
    public string $view;

    /**
     * Параметр передаваемый HTTP-запросом для отображения файлов и папок.
     * 
     * Параметр передаётся с помощью метода POST и определяется {@see BaseGridModel::defineView()}.
     * Если значение параметра `false`, тогда будет применяться значение {@see BaseGridModel::$defaultView}.
     * 
     * @var string|false
     */
    public string|false $viewParam = 'view';

    /**
     * Определяет, что параметр $view получен из HTTP-запроса.
     * 
     * @see BaseGridModel::defineView()
     * 
     * @var bool
     */
    protected bool $hasView = false;

    /**
     * Значение отображения файлов и папок по умолчанию.
     * 
     * Используется в том случаи, если значение параметра {@see BaseGridModel::$viewParam} 
     * отсутствует в HTTP-запросе.
     * 
     * @var string
     */
    public $defaultView = self::GRID_VIEW;

    /**
     * @var Mimes
     */
    protected Mimes $mimes;

    /**
     * Значки файлов
     * 
     * @var array
     */
    protected array $icons = [];

    /**
     * Перекрытие значка папок.
     * 
     * @var array
     */
    protected array $overlays = [];

    /**
     * Указан ли путь.
     * 
     * @see Files::definePath()
     * 
     * @var bool
     */
    protected bool $isEmptyPath = false;

    /**
     * Псевдоним диалога. 
     * 
     * @see Files::defineDialogAlias()
     * 
     * @var string
     */
    protected string $dialogAlias;

    /**
     * Атрибуты диалога. 
     * 
     * @see Files::defineDialogAttr()
     * 
     * @var array
     */
    protected ?array $dialogAttr = null;

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        // настройки
        $this->settings = $this->getSettings();
        $this->defaultPath   = $this->settings->getValue('folderRootId', $this->defaultPath);
        $this->showVCSFiles  = $this->settings->getValue('showVCSFiles', $this->showVCSFiles);
        $this->showDotFiles  = $this->settings->getValue('showDotFiles', $this->showDotFiles);
        $this->showOnlyFiles = $this->settings->getValue('showOnlyFiles', $this->showOnlyFiles);
        $this->usePermsAttr  = $this->settings->getValue('showPermissionsColumn', $this->usePermsAttr);
        $this->useSizeAttr   = $this->settings->getValue('showSizeColumn', $this->useSizeAttr);
        $this->showUnreadableDirs = $this->settings->getValue('showUnreadableDirs', $this->showUnreadableDirs);
        $this->useAccessTimeAttr  = $this->settings->getValue('showAccessTimeColumn', $this->useAccessTimeAttr);
        $this->useChangeTimeAttr  = $this->settings->getValue('showChangeTimeColumn', $this->useChangeTimeAttr);
        $this->icons    = $this->settings->getValue('icons', []);
        $this->overlays = $this->settings->getValue('overlays', []);    
        $this->view     = $this->defineView();
        $this->dialogAttr = $this->defineDialogAttr();

        parent::init();

        $this
            ->on(self::EVENT_AFTER_DELETE, function ($someRows, $result, $message) {
                // обновить панель файлов
                if ($this->dialogAlias)
                    $filePanelId = $this->module->viewId('filepanel-d'); // filepanel-d => gm-mediafiles-filepanel-d
                else
                    $filePanelId = $this->module->viewId('filepanel'); // filepanel => gm-mediafiles-filepanel

                /** @var \Gm\Panel\Http\Response\JsongMetadata $meta */
                $meta = $this->response()->meta;
                // всплывающие сообщение
                $meta->cmdPopupMsg($message['message'], $message['title'], $message['type']);
                // обновляем список файлов
                $meta->cmdComponent($filePanelId, 'reload');
            })
            ->on(self::EVENT_AFTER_SET_FILTER, function ($filter) {
                // обновить панель файлов
                if ($this->dialogAlias)
                    $filePanelId = $this->module->viewId('filepanel-d'); // filepanel-d => gm-mediafiles-filepanel-d
                else
                    $filePanelId = $this->module->viewId('filepanel'); // filepanel => gm-mediafiles-filepanel-d
//                die($filePanelId);
                $this->response()
                    ->meta
                        ->cmdComponent($filePanelId, 'reload');
            });
    }

    /**
     * {@inheritdoc}
     */
    public function getDataManagerConfig(): array
    {
        return [
            'filter' => [
                'name'   => ['operator' => '='],
                'path'   => ['operator' => '='],
                'type'   => ['operator' => '='],
                'custom' => ['operator' => '='],
            ]
        ];
    }

    /**
     * {@inheritdoc}
     * 
     * Т.к. указанный путь из HTTP-запроса может быть идентификатор корневой папки 
     * из дерева, то определяем его как ''. 
     * Избавляемся от идентификатора для получения абсолютного пути через defineRealPath().
     */
    protected function definePath(): string
    {
        // если значение указано в параметрах конфиграции
        if ($this->path !== null) {
            return $this->path;
        }

        $path = parent::definePath();

        /** @var MediaFolder|null $mediaFolder */
        $this->mediaFolder = $this->getMediaFolder($path);
        // если медиапапка найдена
        if ($this->mediaFolder) {
            if ($this->mediaFolder->hasPath()) {
                // устанавливать путь в том случае, если выбранный путь найден из
                // указанного псевдонима пути медиапапки
                if ($this->mediaFolder->alias === $path) {
                    $path = $this->mediaFolder->path;
                }
            } else
                $this->isEmptyPath = true;
            // профиль медиапапки
            $this->folderProfile = $this->mediaFolder->getFolderProfile();
            if ($this->folderProfile) {
                $this->folderOptions = $this->folderProfile->getOptions();
            }
        } else
            $this->isEmptyPath = true;

        return $this->path = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function defineRealPath(): false|string
    {
        return $this->isEmptyPath ? false : $this->getSafePath($this->definePath());
    }

    /**
     * {@inheritdoc}
     */
    protected function defineRowsId(): array
    {
        // если значение указано в параметрах конфиграции
        if (isset($this->rowsId)) {
            return $this->rowsId;
        }

        // если запрещено получать значение из HTTP-запроса
        if ($this->rowsIdParam === false) {
            return [];
        }

        $rowsId = Gm::$app->request->getPost($this->rowsIdParam);
        if ($rowsId) {
            $rowsId = Json::tryDecode($rowsId);
            if (Json::error()) {
                // TODO: debug
                $rowsId = [];
            }
        } else
            return [];
        // параметр был получен из запроса
        $this->hasRowsId = true;
        return $rowsId;
    }

    /**
     * Определяет вид отображения файлов и папок.
     * 
     * - если значение указано в параметрах конфигурации, тогда возвратит {@see BaseGridModel::$view};
     * - если не указан параметр запроса или сам параметр отсутствует в запросе, 
     * тогда возвратит {@see BaseGridModel::$defaultView};
     * - если значение параметра является не допустимым, 
     * тогда возвратит {@see BaseGridModel::$defaultView}.
     * 
     * @see BaseGridModel::$view
     * 
     * @return int
     */
    protected function defineView(): string
    {
        // если значение указано в параметрах конфиграции
        if (isset($this->view)) {
            return $this->view;
        }

        // если запрещено получать значение из HTTP-запроса
        if ($this->viewParam === false) {
            return $this->defaultView;
        }

        $view = Gm::$app->request->getPost($this->viewParam, null);
        if ($view === null) {
            return $this->defaultView;
        }
        return $view;
    }

    /**
     * Определяет псевдоним диалога, который обратился к модели файлов.
     * 
     * - если значение указано в параметрах конфигурации, тогда возвратит {@see Files::$dialogAlias};
     * - если значение не передано параметром POST, тогда возвратит ''.
     * 
     * @see Files::$dialogAlias
     * 
     * @return string
     */
    protected function defineDialogAlias(): string
    {
        // если значение указано в параметрах конфиграции
        if (isset($this->dialogAlias)) {
            return $this->dialogAlias;
        }
        return $this->dialogAlias = Gm::$app->request->getPost('dialog', '');
    }

    /**
     * Определяет атрибуты диалога, который обратился к модели файлов.
     * 
     * @see Files::$dialogAttr
     * 
     * @return array|null Возвращает значение `null` если диалог не указан или его 
     *     атрибуты отсутствуют.
     */
    protected function defineDialogAttr(): ?array
    {
        $alias = $this->defineDialogAlias();
        return $this->dialogAttr = $alias ? $this->module->dialogFromStorage($alias) : null;
    }

    /**
     * Создаёт (находит) медиапапку по указанному пседониму или пути.
     * 
     * @param string $pathOrAlias Псевдоним или путь, например: '@media/images', 'public/images'.
     * 
     * @return MediaFolder|null
     */
    protected function getMediaFolder(string $pathOrAlias): ?MediaFolder
    {
        /** @var MediaFolder $mediaFolder */
        $mediaFolder = $this->module->createMediaFolder();
        if ($mediaFolder::isAliasPath($pathOrAlias))
            /** @var MediaFolder|null $mediaFolder */
            return $mediaFolder->getByAlias($pathOrAlias);
        else
            /** @var MediaFolder|null $mediaFolder */
            return $mediaFolder->getByPath($pathOrAlias, true);
    }

    /**
     * Если отображение в виде сетки.
     * 
     * @return bool
     */
    public function isGridView(): bool
    {
        return $this->view === self::GRID_VIEW;
    }

    /**
     * Если отображение в виде списка.
     * 
     * @return bool
     */
    public function isListView(): bool
    {
        return $this->view === self::LIST_VIEW;
    }

    /**
     * {@inheritdoc}
     */
    public function getSafePath(string $path): false|string
    {
        return $this->module->getSafePath($path);
    }

    /**
     * @see Files::getUrl()
     * 
     * @var string
     */
    protected string $url;

    /**
     * Возвращает URL-адрес для выбранной из дерева папки (текущий путь).
     *
     * @return string
     */
    public function getUrl(): string
    {
        if (!isset($this->url)) {
            $this->url = $this->module->getSafeUrl($this->path);
        }
        return $this->url;
    }

    /**
     * @see Files::getIconUrl()
     * 
     * @var string
     */
    protected string $iconUrl;

    /**
     * Возвращает URL-адрес к значкам файлов.
     *
     * @return string
     */
    public function getIconUrl(): string
    {
        if (!isset($this->iconUrl)) {
            $this->iconUrl = $this->module->getFileIconsUrl() . $this->view . '/';
        }
        return $this->iconUrl;
    }

    /**
     * @see Files::getOverlaysUrl()
     * 
     * @var string
     */
    protected string $overlaysUrl;

    /**
     * Возвращает URL-адрес к перекрытием значков папок.
     *
     * @return string
     */
    public function getOverlaysUrl(): string
    {
        if (!isset($this->overlaysUrl)) {
            $this->overlaysUrl = $this->module->getFileOverlaysUrl();
        }
        return $this->overlaysUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function beforeFetchRows(): void
    {
        $this->mimes = new Mimes();
        $this->getUrl();
        $this->getIconUrl();
        $this->getOverlaysUrl();
    }

    /**
     * Возвращает подпапки текущей медиапапки, полученные в результате запроса и 
     * приведение их к нужному формату.
     * 
     * @return array
     */
    public function fetchMediaFolderRows(): array
    {
        /** @var bool $isGridView Если отображается в виде сетки */ 
        $isGridView = $this->isGridView();

        $rows = [];
        $index = 0;

        /** @var array $folders */
        $folders = $this->mediaFolder->getChildren();
        
        /** @var array $availableFolders Доступные идент. медиапапок */
        $availableFolders = [];
        if ($this->dialogAttr) {
            $availableFolders = $this->dialogAttr['folders'];
        }

        foreach ($folders as $folder) {
            // если медиапапка доступа
            if ($availableFolders && !isset($availableFolders[$folder['id']])) continue;

            $rows[] = [
                self::ATTR_ID   => $folder['alias'] ?: $folder['path'],
                self::ATTR_TYPE => 'folder',
                'name'          => $folder['name'],
                'preview'       => null,
                'overlay'       => null,
                'iconCls'       => $folder['icon_cls'],
                'isFolder'      => true,
                'isMediaFolder' => true,
                'isImage'       => false,
                'isArchive'     => false,
                'icon'          => $folder[$isGridView ? 'icon_small' : 'icon'] ?: $this->iconUrl . 'folder.svg'
            ];

            $index++;
            if ($index <= $this->rangeBegin) continue;
            if ($index > $this->rangeEnd) break;
        }
        return $rows;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchRows($receiver): array
    {
        // если указана медиапапка
        if ($this->mediaFolder) {
            if ($this->mediaFolder->isVisible()) {
                // если имеет подпапки
                if ($this->mediaFolder->hasChildren())
                    return $this->fetchMediaFolderRows();
                else
                // если у медиапапки указан путь
                if ($this->mediaFolder->hasPath()) {
                    return parent::fetchRows($receiver);
                }
            }
            return [];
        }
        return parent::fetchRows($receiver);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchRow(array $row, SplFileInfo $file): array
    {
        $row = parent::fetchRow($row, $file);

        $isDir  = $file->isDir();
        // только имя файла
        $name = $row['name'];

        $row['preview'] = null;
        $row['overlay'] = null;
        $row['icon'] = $this->iconUrl . $row['type'] . '.svg';
        $row['isFolder'] = $isDir;
        $row['isImage'] = false;
        $row['isArchive'] = false;
        $row['isMediaFolder'] = false;
        $row['popupMenuItems'] = [
            [0, $isDir ? 'disabled' : 'enabled'], // для папки запретить просмотр
            [1, $isDir ? 'disabled' : 'enabled'] // для папки запретить редактирование
        ];

        // если файл
        if (!$isDir) {
            /** @var string $extension Расширение файла */
            $extension = strtolower($file->getExtension());
            $row['icon'] = $this->iconUrl . ($this->icons[$extension] ?? 'file') . '.svg';

            // если файл - изображение
            $row['isImage'] = $this->mimes->exists($extension, null, 'image');
            if ($row['isImage']) {
                $row['preview'] = $this->url . '/' . $row[self::ATTR_RNAME];
            }
            // если файл - архив
            $row['isArchive'] = $this->mimes->exists($extension, null, 'archive');
        // если папка
        } else {
            // если список
            if ($this->view === self::LIST_VIEW) {
                // есть ли есть перекрытие значка папки
                if (isset($this->overlays[$name])) {
                    $row['overlay'] = $this->overlaysUrl . '/' . $this->overlays[$name] . '.svg';
                }
            }
        }

        if ($this->view === self::GRID_VIEW) {
            $row['popupMenuTitle'] = '<img width="13px" src="'  . $row['icon'] . '" align="absmiddle"> ' . $name;
        }
        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function filterRows(?Finder $builder)
    {
        if ($builder === null) return;

        if ($this->isEmptyPath) return;

        if ($this->directFilter) {
            $filter = [];
            // переводим параметры фильтра в пары 'ключ - значение'
            foreach ($this->directFilter as $params) {
                $filter[$params['property']] = $params['value'];
            }
            $this->isFiltered = $this->filterRowsQuery($builder, $filter);
            return;
        }

        // если фильтр не применялся, выводим текущий список файлов и папок
        $builder->in($this->realPath);

        // если есть настройки для текущей папки
        if ($this->folderOptions) {
            // если "Показывтаь файлы соответствующие папке"
            if ($this->folderOptions->showExtFiles && $this->folderOptions->allowedExtensions) {
                /** @var array $allowedExtensions Расширения файлов ('jpg,png' => ['jpg' => true, 'png' => true]) */
                $allowedExtensions = explode(',', $this->folderOptions->allowedExtensions);
                $allowedExtensions = array_fill_keys($allowedExtensions, true);
                /** @var bool $showDirs Показывать файлы подпапок */
                $showDirs = $this->folderOptions->showDirs;
                // фильтруем вручную
                $builder->filter(function (SplFileInfo $file) use ($allowedExtensions, $showDirs) {
                    if ($file->isFile())
                        return isset($allowedExtensions[$file->getExtension()]);
                    else
                        return $showDirs ? true : false;
                });
                /* не оправдал себя, фильтрует вместе с папками (папки не выводятся)
                   foreach ($allowedExtensions as $extension) { $builder->files()->name('*.' . $extension); } */
            } else {
                // если не "Показывать подпапки"
                if (!$this->folderOptions->showDirs) {
                    $builder->files();
                }
            }
            // если не "Показывать файлы подпапок"
            if (!$this->folderOptions->showFilesInDirs) {
                $builder->depth('== 0');
            }
        // если нет настроеек для текущей папки
        } else {
            // только в выбранной папке
            $builder->depth('== 0');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function filterRowsQuery(Finder $builder, array $filter): bool
    {
        // название папки или файла
        $name = $filter['name'] ?? '';
        // название пользовательского фильтра
        $custom = $filter['custom'] ?? '';

        // если указано, что искать
        if ($name) {
            $builder->in($this->realPath);
            // вид поиска
            $type = $filter['type'] ?? 'file';
            // если папка
            if ($type === 'folder') {
                $builder->directories()->name($name);
            // если файл
            } else
                $builder->files()->name($name);
            return true;
        } else
        // если применить пользовательский фильтр
        if ($custom) {
            $this->customFilter($builder, $custom);
            return true;
        }
        return false;
    }

    /**
     * Пользовательский фильтр файлов / папок.
     * 
     * Устанавливается через параметры "прямого" фильтра {@see Files::$directFilter}.
     * Например: `$this->directFilter['custom'] = ['property' => 'custom', 'value' => 'search'];`.
     * 
     * @see Filers::filterRowsQuery()
     * 
     * @param Finder $builder Поисковик файлов и папок.
     * @param string $search Что искать, например: 'image', 'document', 'archive', 
     *     'script', 'template' (один из параметров настроек {@see Files::$settings}, 
     *     содержащий расширение файлов).
     * 
     * @return void
     */
    protected function customFilter(Finder $builder, string $search): void
    {
        $builder->in($this->realPath)->depth('== 0');

        /** @var string $name Название параметра */
        $name = $search . 'Ext';
        /** @var string|null $extensions Расширения файлов */
        $extensions = $this->settings->$name;
        if (empty($extensions)) return;

        /** @var array $allowed Расширения файлов ('jpg,png' => ['jpg' => true, 'png' => true]) */
        $allowed = explode(',', $extensions);
        $allowed = array_fill_keys($allowed, true);

        $builder->filter(function (SplFileInfo $file) use ($allowed) {
            if ($file->isFile()) {
                return isset($allowed[$file->getExtension()]);
            }
            return true;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMessage(bool $someRows, int $result): array
    {
        $type     = 'accept';
        $message  = '';
        $selected = $this->getSelectedCount();
        $missed   = $selected - $result;
        // файлы / папки удалены
        if ($result > 0) {
            // файлы / папки удалены частично
            if ($missed > 0) {
                $message = $this->deleteMessageText(
                    'partiallySome',
                    [
                        'deleted' => $result, 'nDeleted' => $result,
                        'selected' => $selected, 'nSelected' => $selected
                    ]
                );
                $type = 'warning';
            // файлы / папки удалены полностью
            } else
                $message = $this->deleteMessageText(
                    'successfullySome',
                    ['n' => $result, 'N' => $result]
                );
        // файлы / папки не удалены
        } else {
            $message = $this->deleteMessageText(
                'unableSome',
                ['n' => $selected, 'N' => $selected]
            );
            $type = 'error';
        }
        return [
            'selected' => $selected, // количество выделенных файлов / папок
            'deleted'  => $result, // количество удаленных файлов / папок
            'missed'   => $missed, // количество пропущенных файлов / папок
            'success'  => $missed == 0, // успех удаления файлов / папок
            'message'  => $message, // сообщение
            'title'    => Gm::t(BACKEND, 'Deletion'), // загаловок сообщения
            'type'     => $type // тип сообщения
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteMessageText(string $type, array $params): string
    {
        switch ($type) {
            // выбранные файлы / папки удалены частично
            case 'partiallySome':
                return $this->module->t(
                    'The records were partially deleted, from the selected {nSelected} {selected, plural, =1{record} other{records}}, {nDeleted} were deleted, the rest were omitted',
                    $params
                );
            // выбранные файлы / папки удалены полностью
            case 'successfullySome':
                return $this->module->t(
                    'Successfully deleted {N} {n, plural, =1{record} other{records}}',
                    $params
                );
            // выбранные файлы / папки не удалены
            case 'unableSome':
                return $this->module->t(
                    'Unable to delete {N} {n, plural, =1{record} other{records}}, no records are available',
                    $params
                );
            // все файлы / папки удалены частично
            case 'partiallyAll':
                return $this->module->t(
                    'Records have been partially deleted, {nDeleted} deleted, {nSkipped} {skipped, plural, =1{record} other{records}} skipped',
                    $params
                );
            // все файлы / папки удалены полностью
            case 'successfullyAll':
                return $this->module->t(
                    'Successfully deleted {N} {n, plural, =1{record} other{records}}',
                    $params
                );
            // все выбранные файлы / папки не удалены
            case 'unableAll':
                return $this->module->t(
                    'Unable to delete {n, plural, =1{record} other{records}}, no {n, plural, =1{record} other{records}} are available',
                    $params
                );
            default:
                return '';
        }
    }
}

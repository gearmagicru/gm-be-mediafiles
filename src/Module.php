<?php
/**
 * Модуль веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Backend\MediaFiles;

use Gm;
use Gm\Exception\CreateObjectException;
use Gm\Backend\References\MediaFolders\Model\MediaFolder;

/**
 * Модуль Медиафйалы.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles
 * @since 1.0
 */
class Module extends \Gm\Panel\Module\Module
{
    /**
     * {@inheritdoc}
     */
    public string $id = 'gm.be.mediafiles';

    /**
     * {@inheritdoc}
     */
    public function controllerMap(): array
    {
        return [
            'attributes'  => 'AttributesForm',
            'create'      => 'CreateForm',
            'rename'      => 'RenameForm',
            'permissions' => 'PermissionsForm',
            'preview'     => 'PreviewForm',
            'edit'        => 'EditForm',
            'upload'      => 'UploadForm',
            'compress'    => 'CompressForm',
            'extract'     => 'ExtractForm',
            'folders'     => 'FoldersTree',
            'dialog'      => 'Dialog',
            'modal'       => 'Modal'
        ];
    }

    /**
     * Возвращает абсолютный путь для указанной папки или файла.
     * 
     * Например: 
     *     - 'uploads/images' => '/home/www/site/public/uploads/images'; 
     *     - 'uploads/images/image.jpg' => '/home/www/site/public/uploads/images/image.jpg'.
     * 
     * @param string $path Папка или файл.
     * 
     * @return false|string Возвращает `false`, если указанная директории или файл 
     *     не существует.
     */
    public function getSafePath(string $path): false|string
    {
        static $homePath;

        if ($homePath === null) {
            /** @var \Gm\Config\Config $settings */
            $settings = $this->getSettings();

            $homePath = $settings->getValue('homePath', false);
            if ($homePath) {
                $homePath = Gm::getAlias($homePath);
            }
        }

        if ($homePath) {
            // если указанный путь является идентификатором корневой папки
            if ($path === '') return $homePath;

            if (OS_WINDOWS) {
                $path = str_replace('/', DS, $path);
            }

            $path = ltrim($path, DS);
            /** @var false|string $realPath */
            $realPath = realpath($path);
            if (!$realPath) return false;

            /** @var false|string $path */
            $path = $homePath . DS . $path;
            // если есть лишние символы в указанной папке или файле
            if ($path !== $realPath) return false;
            return $realPath;
        }
        return false;
    }

    /**
     * Возвращает URL адрес из указанного URL-пути.
     * 
     * @param string $path URL-путь.
     * 
     * @return false|string Возвращает `false`, если невозможно получить URL адрес.
     */
    public function getSafeUrl(string $path): false|string
    {
        /** @var \Gm\Config\Config $settings */
        $settings = $this->getSettings();
        if ($settings->homeUrl && $settings->folderRootId) {
            if ($settings->folderRootId === $path)
                $alias = $settings->homeUrl;
            else
                $alias = $settings->homeUrl . '/' . $path;
            $url = Gm::getAlias($alias);
            if ($url === false) {
                return false;
            }
            return $url;
        }
        return false;
    }

    /**
     * @see Module::getFileIconsUrl()
     * 
     * @var string
     */
    protected string $iconUrl;

    /**
     * Возвращает URL-путь к значкам файлов.
     *
     * @return string
     */
    public function getFileIconsUrl(): string
    {
        if (!isset($this->iconUrl)) {
            $this->iconUrl = $this->getAssetsUrl() . '/images/files/';
        }
        return $this->iconUrl;
    }

    /**
     * Возвращает URL-путь к перекрытиям значков папок.
     *
     * @return string
     */
    public function getFileOverlaysUrl(): string
    {
        return $this->getAssetsUrl() . '/images/overlays/';
    }

    /**
     * Возвращает путь или имя файла (включая путь) из названия папки.
     * 
     * Например: 
     *     - 'home/public/themes' => '/home/user/public/themes';
     *     - 'home/public/themes/file.php' => '/home/user/public/themes/file.php'.
     * 
     * @param string $folder Название папки, например: 'foo/bar', 'foo/bar/file.php'.
     * @param bool $basePath Если значение `true`, возвращаемый путь или имя файла 
     *     будут содержать базовый (абсолютный) путь (по умолчанию `false`).
     * @param string $folderRoot Название корневой папки в дереве папок (по умолчанию 'home').
     * 
     * @return string
     */
    public function getPathFromFolder(string $folder, bool $basePath = false, string $folderRoot = 'home'): string
    {
        if (empty($folder) || ($folder === $folderRoot))
            $path = '';
        else {
            $path = ltrim($folder, $folderRoot);
            $path = trim($path, '/');
        }

        // TODO: safe path
        if ($basePath) {
            return Gm::alias('@path', $path === '' ? '' : '/' . $path);
        }
        return $path;
    }

    /**
     * Возвращает абсолютный URL-путь (с именем файла) из названия папки.
     * 
     * Например: 
     *     - 'home/public/themes' => '/public/themes';
     *     - 'home/public/themes/image.jpg' => '/public/themes/image.jpg'.
     * 
     * @param string $folder Название папки, например: 'foo/bar', 'foo/bar/image.jpg'.
     * @param string $folderRoot Название корневой папки в дереве папок (по умолчанию 'home').
     * 
     * @return string
     */
    public function getUrlFromFolder(string $folder, string $folderRoot = 'home'): string
    {
        if (empty($folder) || ($folder === $folderRoot))
            $url = '';
        else {
            $url = ltrim($folder, $folderRoot);
            $url = trim($url, '/');
        }

        // TODO: safe path
        return Gm::alias('@home::', $url === '' ? '' : '/' . $url);
    }

    /**
     * Возвращает активную запись медиапапки.
     *
     * @return MediaFolder
     * 
     * @throws CreateObjectException
     */
    public function createMediaFolder(): MediaFolder
    {
        /** @var MediaFolder|null $model */
        $model = Gm::$app->extensions->getModel('MediaFolder', 'gm.be.references.media_folders');
        if ($model === null) {
            throw new CreateObjectException(
                Gm::t('app', 'Could not defined data model "{0}"', [MediaFolder::class])
            );
        }
        return $model;
    }

    /**
     * Создаёт (находит) медиапапку по указанному пседониму или пути.
     * 
     * @param string $pathOrAlias Псевдоним или путь, например: '@media/images', 
     *     'public/images'.
     * 
     * @return MediaFolder|null
     */
    public function getMediaFolder(string $pathOrAlias): ?MediaFolder
    {
        /** @var MediaFolder $mediaFolder */
        $mediaFolder = $this->createMediaFolder();
        if ($mediaFolder::isAliasPath($pathOrAlias))
            return $mediaFolder->getByAlias($pathOrAlias);
        else
            return $mediaFolder->getByPath($pathOrAlias, true);
    }

    /**
     * Проверяет указанное разрешение для медиапапки.
     * 
     * @param string $permission Разрешение, например: 'upload', 'download', 'delete', 
     *     'createFile', 'createFolder', 'compress', 'uncompress', 'rename', 'editFile', 
     *     'viewFile', 'editPerms', 'viewAttr'.
     * @param string $pathOrAlias Псевдоним или путь, например: '@media/images', 
     *     'public/images'.
     * 
     * @return bool
     */
    public function mediaFolderCan(string $permission, string $pathOrAlias): bool
    {
        /** @var MediaFolder|null $mediaFolder */
        $mediaFolder = $this->getMediaFolder($pathOrAlias);
        return $mediaFolder ? $mediaFolder->can($permission) : false;
    }

    /**
     * Сохраняет атрибуты диалога во временный контейнер модуля.
     * 
     * @param string $alias Псевдоним диалога.
     * @param array<string, mixed> $attributes Атрибуты диалога в виде пар "ключ - значение".
     * 
     * @return void
     */
    public function dialogToStorage(string $alias, array $attributes): void
    {
        /** @var mixed $store Контейнер модуля */
        $store = $this->getStorage();
        if ($store) {
            // контейнер атрибутов диалога
            if (!isset($store->dialogs)) {
                $store->dialogs = [];
            }

            /** @var string $folders Доступные идент. медиапапок */
            $folders = $attributes['folders'] ?? '';
            if ($folders)
                // ['1' => true, '5' => true...]
                $attributes['folders'] = array_fill_keys(explode(',', $folders), true);
            else
                $attributes['folders'] = [];
            $store->dialogs[$alias] = $attributes;
        }
    }

    /**
     * Возвращает атрибуты диалога из временного контейнера модуля.
     * 
     * @param string $alias Псевдоним диалога.
     * 
     * @return array|null Возвращает `null`, если диалог с указанным псевдонимом 
     *     отсутствует.
     */
    public function dialogFromStorage(string $alias): ?array
    {
        /** @var mixed $store Контейнер модуля */
        $store = $this->getStorage();
        return $store?->dialogs[$alias] ?? null;
    }
}

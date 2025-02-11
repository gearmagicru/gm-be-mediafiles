<?php
/**
 * Этот файл является частью модуля веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Backend\MediaFiles\Controller;

use Gm;
use Gm\Panel\Http\Response;
use Gm\Mvc\Module\BaseModule;
use Gm\Panel\Controller\FormController;
use Gm\Backend\MediaFiles\Widget\ExtractWindow;

/**
 * Контроллер формы разархивирования файлов / папок.
 * 
 * Маршруты контроллера:
 * - 'extract', 'extract/view', 'extract', выводит интерфейс разархивирования файлов / папок;
 * - 'extract/perfom', выполняет разархивирование файлов / папок.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Controller
 * @since 1.0
 */
class ExtractForm extends FormController
{
    /**
     * {@inheritdoc}
     * 
     * @var BaseModule|\Gm\Backend\MediaFiles\Module
     */
    public BaseModule $module;

    /**
     * {@inheritdoc}
     */
    public bool $useAppEvents = true;

    /**
     * {@inheritdoc}
     */
    protected string $defaultModel = 'ExtractForm';

    /**
     * Идентификатор выбранной папки.
     * 
     * @var string
     */
    protected string $pathId = '';

    /**
     * Идентификатор выбранного файла архива.
     * 
     * @var string
     */
    protected string $fileId = '';

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        $this
            ->on(self::EVENT_BEFORE_ACTION, function ($controller, $action, &$result) {
                switch ($action) {
                    case 'view':
                        // идентификатор выбранного файла архива
                        $this->fileId = Gm::$app->request->getPost('id', '');
                        if ($this->fileId) {
                            /** @var \Gm\Backend\FileManager\Model\FileProperties $file */
                            $file = $this->getModel('FileProperties', ['id' => $this->fileId]);
                            if (!$file->exists()) {
                                $this->getResponse()
                                    ->meta->error(Gm::t('app', 'Parameter "{0}" not specified', ['file']));
                                $result = false;
                                return;
                            }
                            if (!$file->isArchive()) {
                                $this->getResponse()
                                    ->meta->error($this->module->t('The specified file is not an archive'));
                                $result = false;
                                return;
                            }
                        } else {
                            $this->getResponse()
                                ->meta->error(Gm::t('app', 'Parameter "{0}" not specified', ['file']));
                            $result = false;
                            return;
                        }

                        /** @var string|null $pathId Идент. пути (папки) */
                        $this->pathId = $pathId = Gm::$app->request->getPost('path');
                        if (empty($pathId)) {
                            $this->getResponse()
                                ->meta->error(Gm::t('app', 'Parameter "{0}" not specified', ['path']));
                            $result = false;
                            return;
                        }

                        // определяет медиапапку по указанному идентификатору пути 
                        if (!$this->defineMediaFolder($pathId)) {
                            $result = false;
                            return;
                        }
                }
            });
    }

    /**
     * Определяет медиапапку по указанному идентификатору пути.
     * 
     * Если медиапапка не определена или не доступна, то будет создан соответствующий HTTP-ответ.
     * 
     * @param string $pathId Идентификатор пути (папка).
     * 
     * @return bool Возвращает значение `false`, если медиапапка не определена.
     */
    protected function defineMediaFolder(string $pathId): bool
    {
        // проверка корректности идент. пути (папки)
        /** @var \Gm\Backend\MediaFiles\Model\FolderProperties $folder */
        $folder = $this->getModel('FolderProperties', ['id' => $pathId]);
        // если медиапапка
        if ($folder->hasAliasPath()) {
            /** @var \Gm\Backend\References\MediaFolders\Model\MediaFolder|null $mediaFolder */
            $mediaFolder = $this->module->getMediaFolder($pathId);
            // если медиапапка не найдена
            if ($mediaFolder === null) {
                $this->getResponse()
                    ->meta->error(
                        GM_MODE_PRO ? 
                            $this->t('Unable to compress files to current folder') :
                            Gm::t('app', 'Parameter passed incorrectly "{0}"', ['path'])
                    );
                return false;
            }
            // если медиапапка имеет подпапки
            if ($mediaFolder->hasChildren()) {
                $this->getResponse()
                    ->meta->error(
                        $this->t('This action cannot be performed for media folder "{0}"', [$mediaFolder->name])
                    );
                return false;
            }
        // если обычная папка
        } else {
            // если папка не существует
            if (!$folder->exists()) {
                $this->getResponse()
                    ->meta->error(
                        GM_MODE_PRO ? 
                            $this->t('Unable to compress files to current folder') :
                            Gm::t('app', 'Parameter passed incorrectly "{0}"', ['path'])
                    );
                return false;
            }
            /** @var \Gm\Backend\References\MediaFolders\Model\MediaFolder|null $mediaFolder */
            $mediaFolder = $this->module->getMediaFolder($pathId);
        }

        // если найдена медиапапка текущая или родительская и есть разрешение
        if ($mediaFolder && !$mediaFolder->can('extract')) {
            $this->getResponse()
                ->meta->error(
                    $this->t('No media folder permission to perform this action'), '', null, 
                    'g-icon-svg g-icon_dlg-forbidden'
                );
            return false;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function createWidget(): ExtractWindow
    {
        return new ExtractWindow([
            'fileId' => $this->fileId,
            'pathId' => $this->pathId
        ]);
    }

    /**
     * Действие "perfom" выполняет архивирование файлов / папок.
     * 
     * @return Response
     */
    public function perfomAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();
        /** @var \Gm\Http\Request $request */
        $request  = Gm::$app->request;

        /** @var \Gm\Backend\FileManager\Model\CompressForm $model */
        $model = $this->getModel($this->defaultModel);
        if ($model === null) {
            $response
                ->meta->error(Gm::t('app', 'Could not defined data model "{0}"', [$this->defaultModel]));
            return $response;
        }

        if ($this->useAppEvents) {
            Gm::$app->doEvent($this->makeAppEventName(), [$this, $model]);
        }

        $form = $model;
        // загрузка атрибутов в модель из запроса
        if (!$form->load($request->getPost())) {
            $response
                ->meta->error(Gm::t(BACKEND, 'No data to perform action'));
            return $response;
        }

        // проверка атрибутов
        if (!$form->validate()) {
            $response
                ->meta->error(Gm::t(BACKEND, 'Error filling out form fields: {0}', [$form->getError()]));
            return $response;
        }

        // проверка разрешения "сжатие" для идент. пути (папки)
        if (!$this->module->mediaFolderCan('extract', $request->getPost('path'))) {
            $response
                ->meta->error(
                    $this->t('No media folder permission to perform this action'), 
                    '', null, 'g-icon-svg g-icon_dlg-forbidden'
            );
            return $response;
        }

        // попытка выполнить действие над файлом / папкой
        if (!$form->run()) {
            $response
                ->meta->error(
                    $form->hasErrors() ? $form->getError() : $this->module->t('Error performing an action on a file / folder')
                );
            return $response;
        }

        if ($this->useAppEvents) {
            Gm::$app->doEvent($this->makeAppEventName('After'), [$this, $form]);
        }
        return $response;
    }
}

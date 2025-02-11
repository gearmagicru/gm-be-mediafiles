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
use Gm\Helper\Json;
use Gm\Mvc\Module\BaseModule;
use Gm\Panel\Http\Response;
use Gm\Panel\Controller\FormController;
use Gm\Backend\MediaFiles\Widget\CompressWindow;

/**
 * Контроллер формы архивирования файлов / папок.
 * 
 * Маршруты контроллера:
 * - 'compress', 'compress/view', выводит интерфейс архивирования файлов / папок;
 * - 'compress/perfom', выполняет архивирование файлов / папок.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Controller
 * @since 1.0
 */
class CompressForm extends FormController
{
    /**
     * {@inheritdoc}
     */
    public bool $useAppEvents = true;

    /**
     * {@inheritdoc}
     * 
     * @var BaseModule|\Gm\Backend\MediaFiles\Module
     */
    public BaseModule $module;

    /**
     * {@inheritdoc}
     */
    protected string $defaultModel = 'CompressForm';

    /**
     * Имя создаваемого архива.
     * 
     * @var string
     */
    protected string $archiveName = '';

    /**
     * Идентификатор пути (папка).
     * 
     * @var string|null
     */
    protected ?string $pathId = null;

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

                        /** @var string|null $files Выбранные идентификаторы файлов / папок */
                        $files = Gm::$app->request->getPost('files', '');
                        if (empty($files)) {
                            $this->getResponse()
                                ->meta->error(Gm::t('app', 'Parameter "{0}" not specified', ['files']));
                            $result = false;
                            return;
                        }

                        /** @var array|false $files Выбранные идентификаторы файлов / папок */
                        $files = Json::tryDecode($files);
                        if ($error = Json::error()) {
                            $this->getResponse()
                                ->meta->error($error);
                            $result = false;
                            return;
                        }

                        /** @var \Gm\Backend\FileManager\Model\CompressForm|null $model */
                        $model = $this->getModel($this->defaultModel);
                        if ($model === null) {
                            $this->getResponse()
                                ->meta->error(Gm::t('app', 'Could not defined data model "{0}"',[$this->defaultModel]));
                            $result = false;
                            return;
                        }

                        // подготовка файлов
                        if (!$model->prepare($files)) {
                            $this->getResponse()
                                ->meta->error($model->getError());
                            $result = false;
                            return;
                        }

                        $this->archiveName = $model->makeArchiveName($files, $this->pathId);
                        break;
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
        if ($mediaFolder && !$mediaFolder->can('compress')) {
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
    public function createWidget(): CompressWindow
    {
        /** @var \Gm\Backend\FileManager\Model\CompressForm|null $model */
        $model = $this->getModel($this->defaultModel);

        return new CompressWindow([
            'archiveName'    => $this->archiveName,
            'archiveFormats' => $model->getArchiveFormats(),
            'pathId'         => $this->pathId
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
        if (!$this->module->mediaFolderCan('compress', $request->getPost('path'))) {
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

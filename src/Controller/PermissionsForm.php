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
use Gm\Stdlib\BaseObject;
use Gm\Panel\Http\Response;
use Gm\Mvc\Module\BaseModule;
use Gm\Panel\Controller\FormController;
use Gm\Backend\MediaFiles\Model\FileProperties;
use Gm\Backend\MediaFiles\Model\FolderProperties;
use Gm\Backend\MediaFiles\Widget\PermissionsWindow;

/**
 * Контроллер формы установки прав доступа файлу / папки.
 * 
 * Маршруты контроллера:
 * - 'permissions/folder', выводит интерфейс формы установки прав доступа папке;
 * - 'permissions/file', выводит интерфейс формы установки прав доступа файлу;
 * - 'permissions/perfom', установливает права доступа файлу / папке.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Controller
 * @since 1.0
 */
class PermissionsForm extends FormController
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
    protected string $defaultModel = 'PermissionsForm';

    /**
     * Идентификатор выбранного файла / папки.
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
                    case 'file': 
                    case 'folder': 
                        $this->fileId = Gm::$app->request->getPost('id', '');
                        if ($this->fileId) {
                            /** @var FileProperties|FolderProperties $model */
                            $model = $this->getModel();
                            if (!$model->exists(true)) {
                                $this->getResponse()
                                    ->meta->error(Gm::t('app', 'Parameter "{0}" not specified', ['file']));
                                $result = false;
                                return;
                            }
                        } else {
                            $this->getResponse()
                                ->meta->error(Gm::t('app', 'Parameter "{0}" not specified', ['file']));
                            $result = false;
                            return;
                        }

                        // проверка папки выбранного файла, имеет ли она права на установку прав доступа
                        if (!$this->module->mediaFolderCan('editPerms', dirname($this->fileId))) {
                            $this->getResponse()
                                ->meta->error(
                                    $this->t('No media folder permission to perform this action'), 
                                    '', null, 'g-icon-svg g-icon_dlg-forbidden'
                            );
                            $result = false;
                            return;
                        }
                        break;
                }
            });
    }

    /**
     * {@inheritdoc}
     * 
     * @return FileProperties|FolderProperties|\Gm\Backend\MediaFiles\Model\Permissions
     */
    public function getModel(string $name = null, array $config = []): ?BaseObject
    {
        // определение названия модели: 'FileProperties', 'FolderProperties'
        if ($name === null) {
            $name = ucfirst($this->actionName) . 'Properties';
            // идентификатор файла / папки
            $config['id'] = $this->fileId;
        }

        return parent::getModel($name, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function createWidget(): PermissionsWindow
    {
        /** @var FileProperties|FolderProperties $model */
        $model = $this->getModel();

        /** @var false|string $permissions Права доступа */
        $permissions = $model->getPermissions(true, false);
        // если невозможно определить права доступа
        if ($permissions === false) {
            $this->getResponse()
                ->meta->error(
                    $this->t('Unable to determine permissions for "{0}"', [$model->getBaseName()])
                );
            return null;
        }

        return new PermissionsWindow([
            'filename'    => $model->getBaseName(),
            'actionName'  => $this->actionName,
            'fileId'      => $this->fileId,
            'permissions' => $this->permissions
        ]);
    }

    /**
     * Действие "folder" выводит интерфейса формы "Права доступа папке".
     * 
     * @return Response
     */
    public function folderAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();

        /** @var \Gm\Panel\Widget\EditWindow $widget */
        $widget = $this->getWidget();
        if ($widget === null) {
            return $response;
        }

        $response
            ->setContent($widget->run())
            ->meta
                ->addWidget($widget);
        return $response;
    }

    /**
     * Действие "file" выводит интерфейса формы "Права доступа файлу".
     * 
     * @return Response
     */
    public function fileAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();

        /** @var \Gm\Panel\Widget\EditWindow $widget */
        $widget = $this->getWidget();
        if ($widget === null) {
            return $response;
        }

        $response
            ->setContent($widget->run())
            ->meta
                ->addWidget($widget);
        return $response;
    }

    /**
     * Действие контроллера "perfom" выполняет установку прав доступа файлу / папке.
     * 
     * @return Response
     */
    public function perfomAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();
        /** @var \Gm\Http\Request $request */
        $request  = Gm::$app->request;

        /** @var \Gm\Backend\MediaFiles\Model\Rename $model */
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

        // проверка папки выбранного файла, имеет ли она права на установку прав доступа
        if (!$this->module->mediaFolderCan(
            'editPerms', 
            dirname($request->getPost('fileId')))
        ) {
            $this->getResponse()
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

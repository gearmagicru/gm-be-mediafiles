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
use Gm\Backend\MediaFiles\Widget\RenameWindow;

/**
 * Контроллер формы изменения имени файла или папки.
 * 
 * Маршруты контроллера:
 * - 'rename/folder', выводит интерфейс формы изменения имени папки;
 * - 'rename/file', выводит интерфейс формы изменения имени файла;
 * - 'rename/perfom', выполняет измение имени папки / файла.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Controller
 * @since 1.0
 */
class RenameForm extends FormController
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
    protected string $defaultModel = 'RenameForm';

    /**
     * Идентификатор выбранного файла / папки.
     * 
     * @see RenameForm::init()
     * 
     * @var string|null
     */
    protected ?string $fileId = '';

    /**
     * Псевдоним диалога.
     * 
     * @see RenameForm::init()
     * 
     * @var string|null
     */
    protected ?string $dialogAlias = '';

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
                        $this->fileId = Gm::$app->request->getPost('id');
                        if ($this->fileId) {
                            /** @var FileProperties|FolderProperties $model */
                            $model = $this->getModel();
                            if (!$model->exists(true)) {
                                $this->getResponse()
                                    ->meta->error(
                                        GM_MODE_DEV ? 
                                            Gm::t('app', 'Parameter "{0}" not specified', ['file']) : 
                                            $this->t('Cannot rename file or folder "{0}"', [$this->fileId])
                                    );
                                $result = false;
                                return;
                            }
                        } else {
                            $this->getResponse()
                                ->meta->error(
                                    GM_MODE_DEV ? 
                                        Gm::t('app', 'Parameter "{0}" not specified', ['file']) : 
                                        $this->t('The selected file or folder does not exist')
                                );
                            $result = false;
                            return;
                        }

                        // проверка папки выбранного файла, имеет ли она права на переименование
                        if (!$this->module->mediaFolderCan('rename', dirname($this->fileId))) {
                            $this->getResponse()
                                ->meta->error(
                                    $this->t('No media folder permission to perform this action'), 
                                    '', null, 'g-icon-svg g-icon_dlg-forbidden'
                            );
                            $result = false;
                            return;
                        }

                        // псевдоним диалога
                        $this->dialogAlias = Gm::$app->request->getPost('dialog');
                        break;
                }
            });
    }

    /**
     * {@inheritdoc}
     * 
     * @return FileProperties|FolderProperties
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
    public function createWidget(): RenameWindow
    {
        /** @var FileProperties|FolderProperties $model */
        $model = $this->getModel();
        return new RenameWindow([
            'fileId'      => $this->fileId,
            'dialogAlias' => $this->dialogAlias,
            'filename'    => $model->getBaseName(),
            'actionName'  => $this->actionName
        ]);
    }

    /**
     * Действие "folder" выводит интерфейса формы "Переименование папки".
     * 
     * @return Response
     */
    public function folderAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();

        /** @var RenameWindow $widget */
        $widget = $this->getWidget();
        $response
            ->setContent($widget->run())
            ->meta
                ->addWidget($widget);
        return $response;
    }

    /**
     * Действие "file" выводит интерфейса формы "Переименование файла".
     * 
     * @return Response
     */
    public function fileAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();

        /** @var RenameWindow $widget */
        $widget = $this->getWidget();
        $response
            ->setContent($widget->run())
            ->meta
                ->addWidget($widget);
        return $response;
    }

    /**
     * Действие "perfom" выполняет изменение названия файла / папки.
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

        // проверка папки выбранного файла, имеет ли она права на переименование
        if (!$this->module->mediaFolderCan('rename', dirname($request->getPost('oldName')))) {
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

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
use Gm\Backend\MediaFiles\Widget\EditWindow;

/**
 * Контроллер формы редактирования файла.
 * 
 * Маршруты контроллера:
 * - 'edit', 'edit/view', выводит интерфейс формы редактирования файла;
 * - 'edit/perfom', выполняет сохранение файла.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Controller
 * @since 1.0
 */
class EditForm extends FormController
{
    /**
     * {@inheritdoc}
     * 
     * @var BaseModule|\Gm\Backend\MediaFiles\Module
     */
    public BaseModule $module;

    /**
     * Идентификатор выбранного файла.
     * 
     * @var string
     */
    protected string $fileId = '';

    /**
     * {@inheritdoc}
     */
    protected string $defaultModel = 'EditForm';

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
                        $this->fileId = Gm::$app->request->getPost('id', '');
                        if ($this->fileId) {
                            /** @var FileProperties $model */
                            $model = $this->getModel();
                            if (!$model->exists(true)) {
                                $this->getResponse()
                                    ->meta->error(
                                        $this->module->t('The selected file "{0}" cannot be edited', [$this->fileId])
                                    );
                                $result = false;
                                return;
                            }
                        } else {
                            $this->getResponse()
                                ->meta->error(Gm::t('app', 'Parameter "{0}" not specified', ['file']));
                            $result = false;
                            return;
                        }

                        // проверка папки выбранного файла, имеет ли она права на редактирование
                        if (!$this->module->mediaFolderCan('editFile', dirname($this->fileId))) {
                            $this->getResponse()
                                ->meta->error(
                                    $this->t('No media folder permission to perform this action'), 
                                    '', null, 'g-icon-svg g-icon_dlg-forbidden'
                            );
                            $result = false;
                            return;
                        }

                        /** @var FileProperties|FolderProperties $model */
                        $model = $this->getModel();
                        // если скрипт / текст
                        if (!($model->isScript() || $model->isText())) {
                            $this->getResponse()
                                ->meta->error(
                                    $this->module->t('The selected file "{0}" cannot be edited', [$this->fileId])
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
     */
    public function getModel(string $name = null, array $config = []): ?BaseObject
    {
        // определение названия модели 'FileProperties'
        if ($name === null) {
            $name =  'FileProperties';
            // идентификатор файла / папки
            $config['id'] = $this->fileId;
        }
        return parent::getModel($name, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function createWidget(): EditWindow
    {
        return new EditWindow([
            'fileProperties' => $this->getModel(),
            'fileId'         => $this->fileId
        ]);
    }

    /**
     * Действие "perfom" выполняет сохранение в файл.
     * 
     * @return Response
     */
    public function perfomAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();
        /** @var \Gm\Http\Request $request */
        $request = Gm::$app->request;

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

        // проверка папки выбранного файла, имеет ли она права на редактирование
        if (!$this->module->mediaFolderCan('editFile', dirname($request->getPost('fileId')))) {
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

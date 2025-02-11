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
use Gm\Mvc\Module\BaseModule;
use Gm\Panel\Controller\FormController;
use Gm\Panel\Widget\Widget as PanelWidget;
use Gm\Backend\MediaFiles\Model\FileProperties;
use Gm\Backend\MediaFiles\Widget\ImagePreviewWindow;
use Gm\Backend\MediaFiles\Widget\ScriptPreviewWindow;

/**
 * Контроллер формы предварительного просмотра файла.
 * 
 * Маршруты контроллера:
 * - 'preview', 'preview/view', выводит интерфейс окна предварительного просмотра файла.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Controller
 * @since 1.0
 */
class PreviewForm extends FormController
{
    /**
     * {@inheritdoc}
     * 
     * @var BaseModule|\Gm\Backend\MediaFiles\Module
     */
    public BaseModule $module;

    /**
     * Идентификатор выбранного файла / папки.
     * 
     * @var string
     */
    protected string $fileId = '';

    /**
     * Если файл - изображение.
     * 
     * @var bool
     */
    protected bool $isImage = false;

    /**
     * Если файл - текст или скрипт.
     * 
     * @var bool
     */
    protected bool $isText = false;

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
                                        $this->module->t('The selected file "{0}" cannot be viewed', [$this->fileId])
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

                        // проверка папки выбранного файла, имеет ли она права на переименование
                        if (!$this->module->mediaFolderCan('viewFile', dirname($this->fileId))) {
                            $this->getResponse()
                                ->meta->error(
                                    $this->t('No media folder permission to perform this action'), 
                                    '', null, 'g-icon-svg g-icon_dlg-forbidden'
                            );
                            $result = false;
                            return;
                        }

                        /** @var FileProperties $model */
                        $model = $this->getModel();
                        $this->isImage = $model->isImage();
                        $this->isText  = $model->isScript() || $model->isText();
                        if (!($this->isImage || $this->isText)) {
                            $this->getResponse()
                                ->meta->error($this->module->t('The selected file "{0}" cannot be viewed', [$this->fileId]));
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
     * 
     * @return PanelWidget
     */
    public function createWidget(): PanelWidget
    {
        /** @var FileProperties $model */
        $model = $this->getModel();

        // если изображение
        if ($this->isImage) {
            $widget = new ImagePreviewWindow();
            $content = $model->getFilename();
        } else
        // если скрипт / текст
        if ($this->isText) {
            $widget = new ScriptPreviewWindow();
            $widget->setExtension($model->getExtension());
            $content = $model->getContent();
        } 

        /** @var null|object|\Gm\Stdlib\BaseObject $viewer */
        $viewer = $widget->getViewer();
        // добавление в ответ скриптов 
        if ($viewer) {
            if (method_exists($viewer, 'initResponse')) {
                $viewer->initResponse($this->getResponse());
            }
        }

        $widget
            ->setFileId($this->fileId)
            ->setTitle($model->getFilename())
            ->setContent($content);
        return $widget;
    }
}

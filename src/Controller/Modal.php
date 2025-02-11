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
use Gm\Panel\Controller\BaseController;
use Gm\Backend\MediaFiles\Widget\ModalDialog;

/**
 * Контроллер диалогового окна выбора изображения.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Controller
 * @since 1.0
 */
class Modal extends BaseController
{
    /**
     * Идентификатор выбранной из дерева папки (текущий путь).
     * 
     * Например, 'public/uploads'.
     * 
     * @see Modal::init()
     * 
     * @var string
     */
    protected string $path = '';

    /**
     * {@inheritdoc}
     */
    protected string $defaultAction = 'view';

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        $this
            ->on(self::EVENT_BEFORE_ACTION, function ($controller, $action, &$result) {
                // вывод интерфейса модального окна
                if ($action === 'view') {
                    $this->path = Gm::$app->request->getQuery('path', $this->path, 'string');
                    if (empty($this->path)) {
                        $this->getResponse()
                            ->meta->error(Gm::t('app', 'Parameter "{0}" not specified', ['path']));
                        $result = false;
                        return;
                    }

                    // если некорректно задан идентификатор
                    if (!$this->module->getSafePath($this->path)) {
                        $this->getResponse()
                            ->meta->error(
                                $this->t('Unable to view folder "{0}"', [$this->path])
                            );
                        $result = false;
                        return;
                    }
                }
            });
    }

    /**
     * {@inheritdoc}
     */
    public function createWidget(): ModalDialog
    {
        /** @var \Gm\Config\Config $settings */
        $settings = $this->module->getSettings();
        $settings->folderRootId   = $this->path;
        $settings->treeWidth      = 220; // ширина панели дерева папок
        $settings->dblClick       = true; // двойной клик на папке / файле
        $settings->showOnlyFiles  = false; // показывать только файлы

        /** @var ModalDialog $dialog */
        $dialog = new ModalDialog();
        $dialog->title = $this->t('{modal.title}', ['/' . $this->path]);

        // панель дерева папок
        $dialog->folderTree->applySettings($settings);
        $dialog->folderTree->root->text = basename($this->path);

        // панель отображения файлов
        $dialog->filePanel->applySettings($settings);
        $dialog->filePanel->path = $this->path;
        return $dialog;
    }

    /**
     * Действие "view" выводит интерфейс модального окна.
     * 
     * @return Response
     */
    public function viewAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();

        /** @var ModalDialog $widget */
        $widget = $this->getWidget();
        // если была ошибка при формировании виджета
        if ($widget === false) {
            return $response;
        }

        // сброс фильтра файлов
        $store = $this->module->getStorage();
        $store->directFilter = null;

        $response
            ->setContent($widget->run())
            ->meta
                ->addWidget($widget);
        return $response;
    }
}

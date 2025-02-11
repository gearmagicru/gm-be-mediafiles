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
use Gm\Backend\MediaFiles\Widget\DialogWindow;
use Gm\Backend\References\MediaDialogs\Model\MediaDialog;

/**
 * Контроллер диалогового окна выбора файла.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Controller
 * @since 1.0
 */
class Dialog extends BaseController
{
    /**
     * {@inheritdoc}
     */
    protected string $defaultAction = 'view';

    /**
     * @see Dialog::init()
     * 
     * @var MediaDialog
     */
    protected MediaDialog $mediaDialog;

    /**
     * Псевдоним вызываемого диалога.
     * 
     * @see Dialog::init()
     * 
     * @var string
     */
    protected string $alias = '';

    /**
     * Короткий путь к вызываемой папке дилога.
     * 
     * @see Dialog::init()
     * 
     * @var string
     */
    protected string $mediaPath = '';

    /**
     * Идентификатор поля, которое получит результат.
     * 
     * @see Dialog::init()
     * 
     * @var string
     */
    protected string $applyTo = '';

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        $this
            ->on(self::EVENT_BEFORE_ACTION, function ($controller, $action, &$result) {
                // интерфейс окна диалога
                if ($action === 'view') {
                    // идентификатор поля, которое получит результат
                    $this->applyTo = Gm::$app->request->getPost('applyTo', '', 'string');
                    if (empty($this->applyTo)) {
                        $this->getResponse()
                            ->meta->error(
                                GM_MODE_PRO ? 
                                    $this->t('Cannot call dialog (parameter error)') :
                                    Gm::t('app', 'Parameter passed incorrectly "{0}"', ['applyTo'])
                            );
                        $result = false;
                        return;
                    }

                    // псевдоним вызываемого медиа диалога, см. Справочники / Медиа диалоги
                    $this->alias = Gm::$app->request->getPost('alias', '', 'string');
                    if (empty($this->alias)) {
                        $this->getResponse()
                            ->meta->error(
                                GM_MODE_PRO ? 
                                    $this->t('Cannot call dialog (parameter error)') :
                                    Gm::t('app', 'Parameter passed incorrectly "{0}"', ['alias'])
                            );
                        $result = false;
                        return;
                    }

                    /** @var MediaDialog|null $mediaDialog */
                    $this->mediaDialog = Gm::getEModel('MediaDialog', 'gm.be.references.media_dialogs')
                        ->getByAlias($this->alias);
                    if ($this->mediaDialog === null || !$this->mediaDialog->enabled) {
                        $this->getResponse()
                            ->meta->error(
                                GM_MODE_PRO ? 
                                    $this->t('Cannot call dialog (parameter error)') :
                                    Gm::t('app', 'Parameter passed incorrectly "{0}"', ['alias'])
                            );
                        $result = false;
                        return;
                    }

                    // короткий путь к вызываемой папке дилога, можно не указывать если не 
                    // указана папка диалога (folder_id) в моделе данных gm.be.references.media_dialogs::MediaDialog
                    $this->mediaPath = Gm::$app->request->getPost('mediaPath', '', 'string');
                    $folderId = (int) $this->mediaDialog->folderId;
                    // т.к. указана папка диалога, то к ней должен быть короткий путь
                    if ($folderId) {
                        if (empty($this->mediaPath)) {
                            $this->getResponse()
                                ->meta->error(
                                    GM_MODE_PRO ? 
                                        $this->t('Cannot call dialog (parameter error)') :
                                        Gm::t('app', 'Parameter passed incorrectly "{0}"', ['mediaPath'])
                                );
                            $result = false;
                            return;
                        }
                    }

                    // добавляем атрибуты диалога в контейнер для избежания повторного вызова
                    $this->module->dialogToStorage($this->mediaDialog->alias, $this->mediaDialog->getAttributes());
                }
            });
    }

    /**
     * {@inheritdoc}
     */
    public function createWidget(): DialogWindow
    {
        /** @var \Gm\Config\Config $settings */
        $settings = $this->module->getSettings();
        $settings->showUnreadableDirs  = false; // показывать папки без доступа
        $settings->showVCSFiles        = false; // показывать файлы VCS
        $settings->showDotFiles        = false; // показывать файлы и папки с точкой
        $settings->showTreeSomeIcons   = true; // показывать значки системных папок
        $settings->sortTreeFolders     = true; // сортировать папки
        $settings->showOnlyFiles       = false; // показывать только файлы
        /** @var \Gm\Stdlib\Collection $options */
        $options = $this->mediaDialog->getOptions();
        $settings->showTreeFolderIcons   = $options->showTreeFolderIcons; // показывать значки папок
        $settings->totalExpandedFolders  = $options->totalExpandedFolders;// количество раскрываемых папок
        $settings->treeWidth             = $options->treeWidth;// ширина панели дерева папок
        $settings->treePosition          = $options->treePosition;// положение панели
        $settings->showTreeRoot          = $options->showTreeRoot;// показывать корневую папку
        $settings->showTreeToolbar       = $options->showTreeToolbar; // показывать панель инструментов
        $settings->resizeTree            = $options->resizeTree;// изменять размер панели
        $settings->useTreeArrows         = $options->useTreeArrows;// показывать стрелочки
        $settings->showTree              = $options->showTree;// показывать панель   
        $settings->dblClick              = $options->dblClick;// двойной клик на папке / файле
        $settings->stripeGridRows        = $options->stripeGridRows; // чередование строк
        $settings->showGridColumnLines   = $options->showGridColumnLines;// показывать линии между столбцами
        $settings->showGridRowLines      = $options->showGridRowLines; // показывать линии между строками
        $settings->showGridIcons         = $options->showGridIcons;// показывать значки
        $settings->showPopupMenu         = $options->showPopupMenu;// показывать всплывающие меню
        $settings->gridPageSize          = $options->gridPageSize;// количество файлов и папок на странице
        $settings->showSizeColumn        = $options->showSizeColumn;// показывать столбец "Размер"
        $settings->showTypeColumn        = $options->showTypeColumn; // показывать столбец "Тип"
        $settings->showMimeTypeColumn    = $options->showMimeTypeColumn;// показывать столбец "MIME-тип"
        $settings->showPermissionsColumn = $options->showPermissionsColumn;// показывать столбец "Права доступа"
        $settings->showAccessTimeColumn  = $options->showAccessTimeColumn;// показывать столбец "Последний доступ"
        $settings->showChangeTimeColumn  = $options->showChangeTimeColumn;// показывать столбец "Последнее обновление"

        /** @var DialogWindow $window */
        $window = new DialogWindow([
            'mediaDialog' => $this->mediaDialog,
            'mediaPath'   => $this->mediaPath
        ]);
        // заголовок диалога
        $window->title = $this->mediaDialog->name;
        // псевдоним диалога
        $window->dialogAlias = $this->alias;
        // идент. поля куда подставляется выбранное значение
        $window->browse['applyTo'] = $this->applyTo;
        // выбирать из панели файлов элементы с типом: '', 'image', 'archive', 'folder'
        $window->browse['selectType'] = $this->mediaDialog->browseType ?: '';
        // убрать часть пути ("/public") при подстановке значения в поле
        $window->browse['stripe'] = $this->mediaDialog->browseStripe; // Gm::$app->clientScript->localPath
        // поле выбора файла
        $window->browseField->fieldLabel = $this->mediaDialog->browseLabel ?:  $this->t('File name');

        // панель дерева папок
        $window->folderTree->applySettings($settings);        
        // панель отображения файлов
        $window->filePanel->applySettings($settings);
        return $window;
    }

    /**
     * Действие "view" выводит интерфейс диалога.
     * 
     * @return Response
     */
    public function viewAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();

        /** @var BrowseDialog $widget */
        $widget = $this->getWidget();
        // если была ошибка при формировании виджета
        if ($widget === false) {
            return $response;
        }

        // сброс фильтра файлов
        /*$store = $this->module->getStorage();
        $store->directFilter = [
            'Files' => [
                'custom' => [
                    'property' => 'custom',
                    'value'    => $this->type
                ]
            ]
        ];*/

        $response
            ->setContent($widget->run())
            ->meta
                ->addWidget($widget);
        return $response;
    }
}

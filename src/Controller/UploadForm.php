<?php
/**
 * Этот файл является частью расширения модуля веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Backend\MediaFiles\Controller;

use Gm;
use Gm\Helper\Json;
use Gm\Panel\Http\Response;
use Gm\Mvc\Module\BaseModule;
use Gm\Panel\Controller\FormController;
use Gm\Backend\MediaFiles\Widget\UploadWindow;

/**
 * Контроллер формы загрузки файла.
 * 
 * Маршруты контроллера:
 * - 'upload', 'upload/view', выводит интерфейс окна загрузки файла;
 * - 'upload/perfom', выполняет загрузку файла.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Controller
 * @since 1.0
 */
class UploadForm extends FormController
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
    protected string $defaultModel = 'UploadForm';

    /**
     * Атрибуты профиля медиапапки, которые используются при загрузке файла.
     * 
     * @see UploadForm::defineMediaFolder()
     * 
     * @var array<string, mixed>
     */
    protected array $folderProfile = [];

    /**
     * Атрибуты медиапапки, которые используются при загрузке файла.
     * 
     * @see UploadForm::defineMediaFolder()
     * 
     * @var array<string, mixed>
     */
    protected array $mediaFolder = [];

    /**
     * Идентификатор пути (папка).
     * 
     * @see UploadForm::init()
     * 
     * @var string|null
     */
    protected ?string $pathId = null;

    /**
     * Является ли выбранный путь (папка) папкой диалога.
     * 
     * @see UploadForm::init()
     * 
     * @var bool
     */
    protected bool $isDialogFolder = false;

    /**
     * Псевдоним диалога.
     * 
     * @see UploadForm::init()
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
                    case 'view': 
                        // идентификатор пути (папка)
                        $this->pathId = Gm::$app->request->getPost('path');
                        if (empty($this->pathId)) {
                            $this->getResponse()
                                ->meta->error(Gm::t('app', 'Parameter "{0}" not specified', ['path']));
                            $result = false;
                            return;
                        }

                        // является ли выбранный путь (папка) папкой диалога
                        $this->isDialogFolder = Gm::$app->request->getPost('isDialogFolder', false, 'bool');
                       
                        // определяет медиапапку по указанному идентификатору пути 
                        if (!$this->defineMediaFolder($this->pathId)) {
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
     * Определяет медиапапку по указанному идентификатору пути.
     * 
     * Если медиапапка не определена или не доступна, то будет создан соответствующий HTTP-ответ.
     * Если медиапапка найдена, то будут установлены значения свойствам `$folderProfile`,
     * `$mediaFolder`.
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
                            $this->t('Unable to upload file to current folder') :
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
            // если папка диалога, то она может не существовать, нет смысла проверять 
            // (т.к. будет создана при загрузке файла)
            if (!$this->isDialogFolder) {
                // если папка не существует
                if (!$folder->exists()) {
                    $this->getResponse()
                        ->meta->error(
                            GM_MODE_PRO ? 
                                $this->t('Unable to upload file to current folder') :
                                Gm::t('app', 'Parameter passed incorrectly "{0}"', ['path'])
                        );
                    return false;
                }
            }
            /** @var \Gm\Backend\References\MediaFolders\Model\MediaFolder|null $mediaFolder */
            $mediaFolder = $this->module->getMediaFolder($pathId);
        }

        // если найдена медиапапка текущая или родительская и есть разрешение
        if ($mediaFolder) {
            if (!$mediaFolder->can('upload')) {
                $this->getResponse()
                    ->meta->error(
                        $this->t('No media folder permission to perform this action'), '', null, 
                        'g-icon-svg g-icon_dlg-forbidden'
                    );
                return false;
            }
            $this->folderProfile = $mediaFolder->getFolderProfile()->getAttributes();
            $this->mediaFolder   = $mediaFolder->getAttributes();
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function createWidget(): UploadWindow
    {
        return new UploadWindow([
            'isDialogFolder' => $this->isDialogFolder,
            'folderProfile'  => $this->folderProfile,
            'mediaFolder'    => $this->mediaFolder,
            'dialogAlias'    => $this->dialogAlias,
            'pathId'         => $this->pathId
        ]);
    }

    /**
     * Действие "perfom" выполняет загрузку файла или подтверждает запрос.
     * 
     * @return Response
     */
    public function perfomAction(): Response
    {
        /** @var \Gm\Panel\Http\Response $response */
        $response = $this->getResponse();
        /** @var \Gm\Http\Request $request */
        $request  = Gm::$app->request;

        /** @var \Gm\Backend\MediaFiles\Model\UploadForm $form */
        $form = $this->getModel($this->defaultModel);
        if ($form === null) {
            $response
                ->meta->error(Gm::t('app', 'Could not defined data model "{0}"', [$this->defaultModel]));
            return $response;
        }

        /** @var null|string $confirm Если есть запрос на подтверждение */
        $confirm = $request->getPost('confirm');
        if ($confirm) {
            /** @var array|string $fields */
            $fields = $request->getPost('fields', []);
            if ($fields) {
                /** @var array|false $fields */
                $fields = Json::tryDecode($fields);
                // если нет ошибки в полях переданных на подтверждение
                if ($fields === false) {
                    $response
                        ->meta->error(
                            GM_MODE_PRO ?
                                Gm::t('app', 'Parameter "{0}" not specified', ['files']) :
                                Json::error()
                        );
                    return $response;
                }
            }

            /** @var null|string $message Сообщение диалога подтверждения */
            $message = $form->getConfirmMessage($fields, $confirm);
            if ($message !== null) {
                $response
                    ->meta->confirm = ['message' => $message];
                return $response;
            }
            if ($form->hasErrors()) {
                $response
                    ->meta->error($form->getError());
                return $response;
            }
            return $response;
        }

        if ($this->useAppEvents) {
            Gm::$app->doEvent($this->makeAppEventName(), [$this, $form]);
        }

        // загрузка атрибутов в модель из запроса
        if (!$form->load($request->getPost())) {
            $response
                ->meta->error(Gm::t(BACKEND, 'No data to perform action'));
            return $response;
        }

        // валидация атрибутов модели
        if (!$form->validate()) {
            $response
                ->meta->error(Gm::t(BACKEND, 'Error filling out form fields: {0}', [$form->getError()]));
            return $response;
        }

        // загрузка файла
        if (!$form->upload()) {
            $response
                ->meta->error(
                    $form->hasErrors() ? $form->getError() : $this->module->t('File uploading error')
                );
            return $response;
        }

        if ($this->useAppEvents) {
            Gm::$app->doEvent($this->makeAppEventName('After'), [$this, $form]);
        }
        return $response;
    }
}

<?php
/**
 * Этот файл является частью расширения модуля веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Backend\MediaFiles\Model;

use Gm;
use Gm\Stdlib\Collection;
use Gm\Mvc\Module\BaseModule;
use Gm\Uploader\UploadedFile;
use Gm\Panel\Data\Model\FormModel;
use Gm\Backend\References\MediaFolders\Model\MediaFolder;
use Gm\Backend\References\FolderProfiles\Model\FolderProfile;

/**
 * Модель загрузки файла.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\MediaFiles\Model
 * @since 1.0
 */
class UploadForm extends FormModel
{
    /**
     * @var string Событие, возникшее после загрузки файла.
     */
    public const EVENT_AFTER_UPLOAD = 'afterUpload';

    /**
     * {@inheritdoc}
     * 
     * @var BaseModule|\Gm\Backend\MediaFiles\Module
     */
    public BaseModule $module;

    /**
     * Медиапапка.
     * 
     * @see UploadForm::setPath()
     * 
     * @var MediaFolder|null
     */
    protected ?MediaFolder $mediaFolder = null;

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        $this
            ->on(self::EVENT_AFTER_UPLOAD, function ($result, $message) {
                /** @var \Gm\Panel\Http\Response\JsongMetadata $meta */
                $meta = $this->response()->meta;
                // всплывающие сообщение
                $meta->cmdPopupMsg($message['message'], $message['title'], $message['type']);
                // если права доступа установлены для файла / папки
                if ($result) {
                    // обновить панель файлов
                    if ($this->dialog)
                        $filePanelId = $this->module->viewId('filepanel-d'); // filepanel-d => gm-mediafiles-filepanel-d
                    else
                        $filePanelId = $this->module->viewId('filepanel'); // filepanel => gm-mediafiles-filepanel
                    // обновляем список файлов
                    $meta->cmdComponent($filePanelId, 'reload');
                }
            });
    }

    /**
     * {@inheritdoc}
     */
    public function maskedAttributes(): array
    {
        return [
            'path'           => 'path', // путь к папке
            'dialog'         => 'dialog', // псевдоним диалога
            'isDialogFolder' => 'isDialogFolder' // если текущая папки диалога
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'path' => $this->module->t('Path'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function validationRules(): array
    {
        return [
            [['path'], 'notEmpty']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function formatterRules(): array
    {
        return [
            ['isDialogFolder', 'type' => [ 'bool']]
        ]; 
    }

    /**
     * {@inheritdoc}
     */
    public function afterValidate(bool $isValid): bool
    {
        if ($isValid) {
            // проверка пути загрузки файла
            if ($this->path === false) {
                $this->setError(Gm::t('app', 'Parameter "{0}" not specified', ['path']));
                return false;
            }

            // т.к. папка диалога может не существовать до загрузки файла, то проверять ёё не будем
            if (!$this->isDialogFolder) {
                if (!is_dir($this->path)) {
                    $this->setError(Gm::t('app', 'Parameter "{0}" not specified', ['path']));
                    return false;    
                }
            }

            /** @var false|UploadedFile $file */
            $file = $this->getUploadedFile('uploadFile');
            // проверка загрузки файла
            if ($file === false) {
                $this->setError('No file selected for upload');
                return false;
            }

            // если была ошибки загрузки
            if (!$file->hasUpload()) {
                $this->setError(Gm::t('app', $file->getErrorMessage()));
                return false;
            }
        }
        return $isValid;
    }

    /**
     * Устанавливает значение атрибуту "path" (абсолютный путь к загрузке файла).
     * 
     * @param null|string $value Идентификатор папки.
     * 
     * @return void
     */
    public function setPath($value)
    {
        $oldValue = $value;
        if ($value) {
            /** @var MediaFolder|null $mediaFolder */
            $this->mediaFolder = $this->module->getMediaFolder($value);
            if ($this->mediaFolder) {
                // устанавливать путь в том случае, если выбранный путь найден из
                // указанного псевдонима пути медиапапки
                if ($this->mediaFolder->alias === $value) {
                    $value = $this->mediaFolder->path;
                // важный момент: если профиль медиапапки имеет шаблон локального 
                // пути (новой папки), то этот лольканый путь не должен добавляться
                // к текущему пути, поэтому текущий путь заменяем на путь медиапапки
                // Например: шаблон '{year/month}', путь 'foo/bar' => 'foo/bar/24/01',
                // а если не проверить условие, то получится 'foo/bar/24/01/24/07'.
                } else {
                    /** @var FolderProfile|null $folderProfile */
                    $folderProfile = $this->mediaFolder->getFolderProfile();
                    // если найден профиль медиапапки
                    if ($folderProfile) {
                        if (!empty($folderProfile->options->pathTemplate)) {
                            $value = $this->mediaFolder->path;
                        }
                    }
                }
            }
        }

        $path = $this->module->getSafePath($value) ?: '';

        // если указано, что это папка диалога, то получаем реальный путь даже 
        // если он не существует
        if ($this->isDialogFolder && $path) {
            $offsetPath = str_replace($value, '', $oldValue);
            $path .= $offsetPath;
        }

        $this->attributes['path'] = $path;
    }

    /**
     * @see UploadForm::getUploadedFile()
     * 
     * @var UploadedFile|false
     */
    private UploadedFile|false $uploadedFile;

    /**
     * Возвращает загруженный файл.
     * 
     * @return UploadedFile|false Возвращает значение `false` если была ошибка загрузки.
     */
    public function getUploadedFile()
    {
        if (isset($this->uploadedFile)) return $this->uploadedFile;

        /** @var \Gm\Uploader\Uploader $uploader */
        $uploader = Gm::$app->uploader;
        $uploader->setPath($this->path);

        /** @var \Gm\Stdlib\Collection $options */
        $options = null;
        /** @var string $type */
        $type = '';

        // если найдена медиапапка текущая или родительская
        if ($this->mediaFolder) {
            /** @var FolderProfile|null $folderProfile */
            $folderProfile = $this->mediaFolder->getFolderProfile();
            // если найден профиль медиапапки
            if ($folderProfile) {
                $type = $folderProfile->type ?: '';
                $options = $folderProfile->getOptions();
            }
        }

        /** @var \Gm\Uploader\UploadedFile $uploadedFile */
        $uploadedFile = $uploader->getFile('uploadFile', $type) ?: false;
        if ($uploadedFile && $options) {
            if ($this->isDialogFolder) {
                $options->createPath = false;
                $options->pathTemplate = '';
            }
            $uploadedFile->setOptions($options->getAll());
        }
        return $this->uploadedFile = $uploadedFile;
    }

    /**
     * Выполняет загрузку файла.
     * 
     * @param bool $useValidation Использовать проверку атрибутов (по умолчанию `false`).
     * @param array $attributes Имена атрибутов с их значениями, если не указаны - будут 
     * задействованы атрибуты записи (по умолчанию `null`).
     * 
     * @return bool Возвращает значение `false`, если ошибка загрузки файла.
     */
    public function upload(bool $useValidation = false, array $attributes = null)
    {
        if ($useValidation && !$this->validate($attributes)) {
            return false;
        }
        return $this->uploadProcess($attributes);
    }

    /**
     * Процесс подготовки загрузки файла.
     * 
     * @param null|array $attributes Имена атрибутов с их значениями (по умолчанию `null`).
     * 
     * @return bool Возвращает значение `false`, если ошибка загрузки файла.
     */
    protected function uploadProcess(array $attributes = null): bool
    {
        /** @var UploadedFile $file */
        $file = $this->getUploadedFile();
        $this->result = $file->move();
        // если файл не загружен
        if (!$this->result) {
            $this->setError(
                Gm::t('app', $file->getErrorMessage())
            );
        }

        $this->afterUpload($this->result);
        return $this->result;
    }

    /**
     * Cобытие вызывается после загрузки файла.
     * 
     * @see UploadForm::upload()
     * 
     * @param bool $result Если значение `true`, файл успешно загружен.
     * 
     * @return void
     */
    public function afterUpload(bool $result = false)
    {
        /** @var bool|int $result */
        $this->trigger(
            self::EVENT_AFTER_UPLOAD,
            [
                'result'  => $result,
                'message' => $this->lastEventMessage = $this->uploadMessage($result)
            ]
        );
    }

    /**
     * Возвращает сообщение полученное при загрузке файла.
     *
     * @param bool $result Если значение `true`, файл успешно загружен.
     * 
     * @return array Сообщение имеет вид:
     * ```php
     *     [
     *         'success' => true,
     *         'message' => 'File uploaded successfully',
     *         'title'   => 'Uploading a file',
     *         'type'    => 'accept'
     *     ]
     * ```
     */
    public function uploadMessage(bool $result): array
    {
        $messages = $this->getActionMessages();
        return [
            'success'  => $result, // успех загрузки
            'message'  => $messages[$result ? 'msgSuccessUpload' : 'msgUnsuccessUpload'], // сообщение
            'title'    => $messages['titleUpload'], // заголовок сообщения
            'type'     => $result ? 'accept' : 'error' // тип сообщения
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getActionMessages(): array
    {
        return [
            'titleUpload'        => $this->module->t('Uploading a file'),
            'msgUnsuccessUpload' => $this->getError(),
            'msgSuccessUpload'   => $this->module->t('File uploaded successfully')
        ];
    }

    /**
     * Возвращает сообщение, подтверждающие загрузку файла.
     * 
     * @param array $fields Поля, переданные в HTTP-запросе.
     * @param string $confirm Тип подтверждения.
     * 
     * @return string|null Возвращает значение `null`, если нет необходимости подтверждать.
     */
    public function getConfirmMessage(array $fields, string $confirm): ?string
    {
        if ($confirm === 'upload') {
            $pathId = $fields['path'] ?? '';
            // если не указан идентификатор папки
            if (empty($pathId)) {
                $this->setError(Gm::t('app', 'Parameter "{0}" not specified', ['path']));
                return null;
            }

            $filename = $fields['uploadFile'] ?? '';
            // если не указано название файла
            if (empty($filename)) {
                $this->setError(Gm::t('app', 'Parameter "{0}" not specified', ['uploadFile']));
                return null;
            }

            /** @var MediaFolder|null $mediaFolder */
            $mediaFolder = $this->module->getMediaFolder($pathId);
            // если найдена медиапапка текущая или родительская
            if ($mediaFolder) {
                /** @var FolderProfile|null $folderProfile */
                $folderProfile = $mediaFolder->getFolderProfile();
                // если найден профиль медиапапки
                if ($folderProfile) {
                    // если профиль не позволяет загружать файлы
                    if (!$folderProfile->can('upload')) {
                        return $this->module->t('No media folder permission to perform this action') . '<br>'
                             . $this->module->t('Continue file upload?');
                    }

                    /** @var \Gm\Uploader\Uploader $uploader */
                    $uploader = Gm::$app->uploader;
                    // устанавливать путь в том случае, если выбранный путь найден из
                    // указанного псевдонима пути медиапапки
                    if ($mediaFolder->alias === $pathId) {
                        $pathId = $mediaFolder->path;
                    }
                    $uploader->setPath($pathId);

                    /** @var \Gm\Uploader\UploadedEmptyFile $emptyFile */
                    $emptyFile = $uploader->setFile('some', 'empty');
                    
                    /** @var Collection Параметры загрузки файла */
                    $emptyFile->setOptions($folderProfile->getOptions()->getAll());

                    /** @var string $filename Имя файла (с локальным путём, например: 'public/uploads/img/file.jpg') */
                    $filename = $emptyFile->makeFilenameByRule($filename);

                    // если файл существует
                    if ($this->module->getSafePath($filename)) {
                        return $this->module->t('The downloaded file "{0}" is already on the server, should I replace it?', [$filename]);
                    }
                }
             // если не найдена медиапапка, значит просто путь идент. пути
            } else {
                $basename = basename($filename);
                $filename = $this->module->getSafePath($pathId) . DS . $basename;
                if (file_exists($filename)) {
                    return $this->module->t('The downloaded file "{0}" is already on the server, should I replace it?', [$basename]);
                }
            }
        }
        return null;
    }
}

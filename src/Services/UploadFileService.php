<?php


namespace San4o101\uploadFiles\Services;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadFileService
{
    public const VERSION = '0.1';

    public const IMAGE = 'image';
    public const VIDEO = 'video';

    public static function checkFolderPermissions($dir_path)
    {
        if (!is_dir($dir_path)) {
            mkdir($dir_path, 0775, true);
        }

        return true;
    }

    public static function checkDisk($disk)
    {
        $configDisk = config("filesystems.disks.{$disk}");
        if (empty($configDisk)) {
            return [
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => "Disk '{$disk}' not found in config 'filesystems.php'!",
                'data' => null,
            ];
        }

        return true;
    }

    public static function getRandomString()
    {
        return Str::random(config('upload_files.random_file_name_length'));
    }

    public static function createFile(string $disk, array $fileInformation)
    {
        $checkDisk = self::checkDisk($disk);
        if ($checkDisk) {
            try {
                $model = config('upload_files.models.main');
                $file = new $model();
                $file->name = $fileInformation['name'];
                $file->hash = $fileInformation['hash'];
                $file->disk = $fileInformation['disk'];
                $file->folder = $fileInformation['folder'];
                $file->mimeType = $fileInformation['mimeType'];
                $file->extension = $fileInformation['extension'];
                $file->size = $fileInformation['size'];
                if (!empty($fileInformation['duration'])) {
                    $file->duration = $fileInformation['duration'];
                }
                $file->save();

                return [
                    'status' => Response::HTTP_OK,
                    'message' => 'File created!',
                    'data' => $file
                ];
            } catch (\Exception $exception) {
                return [
                    'status' => Response::HTTP_BAD_REQUEST,
                    'message' => $exception->getMessage(),
                    'data' => null,
                ];
            }
        }

        return $checkDisk;
    }

    public function deleteFile(string $disk, string $fullFilePath)
    {
        $checkDisk = self::checkDisk($disk);
        if ($checkDisk) {
            Storage::disk($disk)->delete($fullFilePath);
            return [
                'status' => Response::HTTP_OK,
                'message' => 'File deleted!',
                'data' => null,
            ];
        }

        return $checkDisk;
    }

    public static function moveFile(string $fullPath, $file, array $fileInformation)
    {
        try {
            $file->move($fullPath, "{$fileInformation['hash']}.{$fileInformation['extension']}");

            return [
                'status' => Response::HTTP_OK,
                'message' => 'File created!',
                'data' => null,
            ];
        } catch (\Exception $exception) {
            return [
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => $exception->getMessage(),
                'data' => null,
            ];
        }
    }

    public static function saveFile($file, string $folder, string $disk)
    {
        $configDisk = config("filesystems.disks.{$disk}");
        $response = [
            'status' => Response::HTTP_BAD_REQUEST,
            'message' => 'Bad file!',
            'data' => null,
        ];

        $checkDisk = self::checkDisk($disk);
        if (!$checkDisk) {
            return $checkDisk;
        }
        if (!is_file($file) || empty($file) || empty($file->getClientOriginalName())) {
            return $response;
        }
        $fileExtension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
        $fileType = null;
        if (in_array($fileExtension, self::getAllowImageExtensions())) {
            $fileType = self::IMAGE;
        } else if (in_array($fileExtension, self::getAllowVideoTypesExtensions())) {
            $fileType = self::VIDEO;
        }
        if (is_null($fileType)) {
            $response['message'] = "File extension {$fileExtension} is not allowed!";
            return $response;
        }

        $rootPath = $configDisk['root'];
        $trimFolder = ltrim($folder, '/');
        $filePath = "{$rootPath}/{$trimFolder}";

        static::checkFolderPermissions($filePath);

        $fileInformation = self::getFileInformation($file, $fileType);

        $fileInformation = array_merge([
            'disk' => $disk,
            'folder' => $folder,
        ], $fileInformation);

        try {
            DB::beginTransaction();
            $databaseFile = self::createFile($disk, $fileInformation);
            if ($databaseFile['status'] != Response::HTTP_OK) {
                return $databaseFile;
            }
            if (empty($databaseFile['data'])) {
                return $databaseFile;
            }

            self::moveFile($filePath, $file, $fileInformation);
            DB::commit();

            return [
                'status' => Response::HTTP_OK,
                'message' => 'File created!',
                'data' => $databaseFile['data'],
            ];
        } catch (\Exception $exception) {
            DB::rollBack();
            return [
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => $exception->getMessage(),
                'data' => null,
            ];
        }
    }

    public static function getFileInformation($file, $fileType)
    {
        $fileName = $file->getClientOriginalName();
        try {
            $mimeType = $file->getMimeType();
        } catch (\Exception $e) {
            $mimeType = $file->getClientMimeType();
        }
        $fileInformation = [
            'name' => pathinfo($fileName, PATHINFO_FILENAME),
            'hash' => self::getRandomString(),
            'mimeType' => $mimeType,
            'extension' => pathinfo($fileName, PATHINFO_EXTENSION),
            'size' => $file->getSize(),
        ];
        if ($fileType === self::VIDEO) {
            array_merge([
                'duration' => self::getVideoDuration($file),
            ], $fileInformation);
        }

        return $fileInformation;
    }

    public static function getVideoDuration($video)
    {
        try {
            $getID3 = new \getID3;
            $file = $getID3->analyze($video);
            $playtime_seconds = $file['playtime_seconds'];

            return date('H:i:s', $playtime_seconds);
        } catch (\Exception $exception) {
            return "undefined";
        }
    }

    public static function getAllowImageExtensions()
    {
        return config('upload_files.allow_mime_types.images');
    }

    public static function getAllowVideoTypesExtensions()
    {
        return config('upload_files.allow_mime_types.videos');
    }

    /* Need send model config('upload_files.models.main') */
    public static function getFile($file)
    {
        if (get_class($file) != config('upload_files.models.main')) {
            return [
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'Model not found!',
                'data' => null,
            ];
        }
        $disk = self::checkFileDisk($file);
        if (!empty($disk['status'])) {
            return $disk;
        }

        try {
            $file = Storage::disk($file->disk)->get("{$file->folder}/{$file->hash}.{$file->extension}");
            return [
                'status' => Response::HTTP_OK,
                'message' => 'File found!',
                'data' => $file
            ];
        } catch (FileNotFoundException $exception) {
            return [
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => $exception->getMessage(),
                'data' => null,
            ];
        }
    }

    /* Need send model config('upload_files.models.main') */
    public static function getFileUrl($file)
    {
        $disk = self::checkFileDisk($file);
        if (!empty($disk['status'])) {
            return $disk;
        }

        $fileFolder = ltrim($file->folder, '/');
        return [
            'status' => Response::HTTP_OK,
            'message' => 'File URL',
            'data' => "{$disk['url']}/{$fileFolder}/{$file->hash}.{$file->extension}",
        ];
    }

    public static function checkFileDisk($file)
    {
        if (empty($file)) {
            return [
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'File not found!',
                'data' => null,
            ];
        }

        $disk = config("filesystems.disks.{$file->disk}");
        if (empty($disk['url']) || empty($disk['visibility']) || $disk['visibility'] != 'public') {
            return [
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'File not public!',
                'data' => null,
            ];
        }

        return $disk;
    }

}
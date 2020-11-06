# Upload file package

## Deleting files
- You can delete file in database with file in folder.
    - `SFile::query()->where('id', '=', 1)->first()->delete()`
    - `SFile::query()->where('id', '=', 1)->first()->forceDelete()`
    - `SFile::query()->find(1)->first()->forceDelete()`
## Install
- `php artisan vendor:publish`
- `php artisan config:clear` or `php artisan config:cache`
## Service
- `UploadFileService`
## Model
- `SFile`
## Config
- You can change validation setting in configuration file
- You can change length file random name
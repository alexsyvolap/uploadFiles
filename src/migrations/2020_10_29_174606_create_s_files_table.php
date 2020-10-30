<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $table = config('upload_files.tables.main');
        Schema::create($table, function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('hash');
            $table->string('disk');
            $table->string('folder');
            $table->string('mimeType');
            $table->string('extension');
            $table->bigInteger('size');
            $table->string('duration')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $table = config('upload_files.tables.main');
        Schema::dropIfExists($table);
    }
}

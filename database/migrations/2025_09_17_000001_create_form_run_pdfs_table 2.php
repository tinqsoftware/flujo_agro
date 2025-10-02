<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('form_run_pdfs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('form_run_id');
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('path');
            $table->string('filename');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('form_run_id')->references('id')->on('form_runs')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('pdf_templates')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('form_run_pdfs');
    }
};

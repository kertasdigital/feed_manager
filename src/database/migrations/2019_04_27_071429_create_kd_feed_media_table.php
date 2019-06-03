<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKdFeedMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kd_feed_media', function (Blueprint $table) {
					$table->bigIncrements('id');
					$table->unsignedBigInteger('feeditem_id')->index();
					$table->foreign('feeditem_id')->references('id')->on('kd_feed_url');
					$table->char('src_md5', 32)->default('')->unique();

					$table->string('title', 320)->default('');
					$table->string('src', 2000)->default('');
					$table->string('mime', 50)->default('');
					$table->unsignedInteger('lenght')->nullable()->default('0');
					
					$table->string('remark', 500)->nullable()->default('');
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
        Schema::dropIfExists('kd_feed_media');
    }
}

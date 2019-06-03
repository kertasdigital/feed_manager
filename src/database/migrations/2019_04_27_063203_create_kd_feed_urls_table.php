<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKdFeedUrlsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
			Schema::create('kd_feed_url', function (Blueprint $table) {
				$table->bigIncrements('id');
				$table->char('url_md5', 32)->default('0')->unique();
				$table->char('content_md5', 32)->default('0');
				
				$table->string('url', 2000)->default('');
				$table->string('name', 200)->default('');
				$table->unsignedInteger('quota')->default('0');
				$table->unsignedTinyInteger('status')->default('0')->comment('1=allow, 2=carantine, 3=error');

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
        Schema::dropIfExists('kd_feed_url');
    }
}

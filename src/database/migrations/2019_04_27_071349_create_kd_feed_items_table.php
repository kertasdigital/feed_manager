<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKdFeedItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kd_feed_item', function (Blueprint $table) {
          $table->bigIncrements('id');
  				$table->unsignedBigInteger('feedurl_id')->index();
					$table->foreign('feedurl_id')->references('id')->on('kd_feed_url');
					$table->char('link_md5', 32)->default('')->unique();
					$table->char('content_md5', 32)->nullable()->default('');
					
					$table->string('title', 320)->default('');
					$table->string('link', 2000)->default('');
					$table->Text('description')->nullable();
					$table->longText('content')->nullable();
			
					$table->string('author', 200)->nullable()->default('');
					$table->string('guid', 200)->nullable()->default('');
					$table->unsignedInteger('pub_date')->nullable()->default('0');

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
        Schema::dropIfExists('kd_feed_item');
    }
}

<?php

namespace KertasDigital\FeedManager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use KertasDigital\FeedManager\Facades\FeedManagerFacade;
use KertasDigital\FeedManager\Models\KdFeedUrl;
use KertasDigital\FeedManager\Models\KdFeedItem;
use KertasDigital\FeedManager\Models\KdFeedMedia;

class FeedFetchCommand extends Command {
	
	protected $signature = 'feedman:fetch';

	protected $description = 'Fetch Rss';

	private function strip_text($text){
		if(empty($text)) return "";
		$text= trim($text);
		$ret= preg_replace('#<\s*(i|b|a|div|h1|h2|h3|h4|h5|H6).*?>#', '', $text);
		$ret= preg_replace('#<\s*\/(i|b|a|div|h1|h2|h3|h4|h5|H6).*?>#', '', $ret);
		
		return trim($ret);
	}
	
	private function save_media($item_id, $medias){
		$urls=					data_get($medias, '*.src');
		$urls_md5=			array_map(md5, $urls);
		$old_medias=		KdFeedMedia::whereIn('src_md5',$urls_md5)->get()->toArray();	
		$old_src_md5=		data_get($old_medias, '*.src_md5');
		
		foreach($medias as $media){
			$src_md5=	md5($media->src);
			$src=			$media->src;
      $title=		$media->title;
			if (strlen($title) > 310)		$title=		Str::limit($title, 310, ' ');
      $length=	$media->length;
      $mime=		$media->mime;
			
			if (empty($src)	||	in_array($src_md5, $old_src_md5) || $length < 500 || empty($mime)) continue;

			$new_media= KdFeedMedia::create([
				'feeditem_id'=>		$item_id,
				'src_md5'=>				$src_md5,
				'title'=>					$title,
				'src'=>						$src,
				'mime'=>					$mime,
				'lenght'=>				$length
			]);
		}
		
		return;
	}
	
	private function save_items($feed, $items){
		$feed_id=	$feed->id;
		$item_add= 0;
		$item_dup= 0;
		foreach ($items as $item) {
			extract($item, EXTR_OVERWRITE);
			
			$title=				(!empty($title)) ? trim($title) : "";
			$link=				(!empty($link)) ? trim($link) : "";
			$description=	(!empty($description)) ? trim($description) : "";
			$content=			(!empty($content)) ? serialize($this->strip_text($content)) : $description;
			$authors=			(!empty($authors) && is_array($authors)) ? implode(",", $authors) : "";
			$pub_date=		(intval($pub_date) < 1) ? intval($pub_date) : time();
						
			if	(empty($title) || empty($link))	continue;
			
			$link_md5=			md5($link);
			$content_md5=		md5(serialize(array($title, $link, $content, $authors)));
			$old_item=			KdFeedItem::where('link_md5', '=', $link_md5)->first();
			if	(!empty($old_item)) {
				$is_update=			strcmp( $content_md5 , $old_item->content_md5);
				$is_feedpart=		$old_item->feedurl_id == $feed_id;
				$item_dup++;			
				if(!$isUpdate || $is_feedpart) continue;
			
				if (strlen($title) > 310)		$title=		Str::limit($title, 310, ' ');
				if (strlen($authors) > 190) $authors= Str::limit($authors, 190, ' ');
				
				$old_item->content_md5=		$content_md5;
				$old_item->title=					$title;
				$old_item->link=					$link;
				$old_item->description=		$description;
				$old_item->content=				$content;
				$old_item->author=				$authors;
				$old_item->guid=					$link_md5;
				$old_item->pub_date=			$pub_date;
				$old_item->remark=				$old_item->remark. "Item Update at ".date("D, d M Y");
				
				$old_item->save();
				continue;
			}
			
			if (strlen($title) > 310)		$title=		Str::limit($title, 310, ' ');
			if (strlen($authors) > 190) $authors= Str::limit($authors, 190, ' ');
						
			$new_item= Item::create([
				"feedurl_id"=>		$feed_id,
				"link_md5"=>			$link_md5,
				"content_md5"=>		$content_md5,
				"title"=>					$title,
				"link"=>					$link,
				"description"=>		$description,
				"content"=>				$content,
				"author"=>				$authors,
				"guid"=>					$link_md5,
				"pub_date"=>			$pub_date				
			]);
			
			if(($new_item->id > 0) && !empty($medias)) $this->save_media($new_item->id, $medias);
		
			$item_add++;
		}
		
		$rmrk=<<<aaa
		New Item ( $item_add ) | Duplicate Item ( $item_dup )	
aaa;
		$feed->remark=	"Last Crawl: ".date("D, d M Y h:i:s")." | ".$rmrk;
		$feed->save();
		$this->info ("Fetch ".$feed->name." Result :");
		$this->info ("--New Item (".$item_add.") | Duplicate Item (".$item_dup.")");
		
		Return true;
	}
	
	private function fetch_items(){
		//$curlOpt['curl.options']= array(CURLOPT_SSL_VERIFYHOST => 0,CURLOPT_SSL_VERIFYPEER => false);
		$feeds=	KdFeedUrl::where('status', '=' , 1)->get();
		if ($feeds->isEmpty()) return false;
		foreach ($feeds as $feed) {
			$quo=	intval($feed->quota);
			if (empty($feed->url) || (!filter_var($feed->url, FILTER_VALIDATE_URL))) {
				$feed->status= 3;
				$feed->save();
				continue;
			}
			
			if ($quo < 1) $quo= 5; 
			$this->info("Fetching {$feed->name}");
			$items=		FeedManagerFacade::getItems($feed->url,$quo,true);
			
			$this->save_items($feed, $items);
		}
	}

	public function handle(){
		$KdFeedUrl=		Schema::hasTable('kd_feed_url');
		$KdFeedItems=	Schema::hasTable('kd_feed_item');
		$KdFeedMedia=	Schema::hasTable('kd_feed_media');
		if(!$KdFeedUrl || !$KdFeedItems || !$KdFeedMedia){
			$this->info("default kd_* table not found. Please run `php artisan vendor:publish`");
		}
		else {
			$this->info("Processing... \n");
			$this->fetch_items();
		}
	}
}

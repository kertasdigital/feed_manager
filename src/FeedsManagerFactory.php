<?php

namespace KertasDigital\FeedManager;

use SimplePie;
use Illuminate\Support\Arr;

class FeedManagerFactory {
	  /**
     * The config.
     *
     * @var array
     */
    protected $config;

    /**
     * @var SimplePie
     */
    protected $simplePie;

		/**
     * Configure SimplePie.
     *
     * @return void
     */
    protected function configure()
    {
        $curlOptions = [];

        if ($this->config['cache.disabled']) {
            $this->simplePie->enable_cache(false);
        } else {
            $this->simplePie->set_cache_location($this->config['cache.location']);
            $this->simplePie->set_cache_duration($this->config['cache.life']);
        }

        if (isset($this->config['curl.options']) && is_array($this->config['curl.options'])) {
            $curlOptions += $this->config['curl.options'];
        }

        if ($this->config['ssl_check.disabled']) {
            $curlOptions[CURLOPT_SSL_VERIFYHOST] = false;
            $curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
        }

        if (is_array($curlOptions)) {
            $this->simplePie->set_curl_options($curlOptions);
        }
    }
				
		private function clearTagAtributes($text){
			return preg_replace("/<([a-z][a-z0-9]*)[^>]*?(\/?)>/i",'<$1$2>', $text);
		}

		private function getUrlProperties($file_url){
			$head = array_change_key_case(get_headers($file_url, 1));
			
			$src		= $file_url;
			$title	= basename($src);
			$length = isset($head['content-length']) ? $head['content-length'] : 0;
			$mime		= isset($head['content-type']) ? $head['content-type'] : "";
			
			return compact('src','title','length','mime'); 
		}
		
		private function getMedia($item){
			if(!is_object($item)) return null;
			$img_urls=		[];
			
			//get from image tag
			if ($return = $item->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'image'))
			{
				$img_urls[]= $item->serialize($return[0]['data'], $item->registry->call('Misc', 'atom_10_construct_type', array($return[0]['attribs'])), $item->get_base($return[0]));
			}
			elseif ($return = $item->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_03, 'image'))
			{
				$img_urls[]= $item->serialize($return[0]['data'], $item->registry->call('Misc', 'atom_03_construct_type', array($return[0]['attribs'])), $item->get_base($return[0]));
			}
			elseif ($return = $item->get_item_tags(SIMPLEPIE_NAMESPACE_RSS_10, 'image'))
			{
				$img_urls[]= $item->serialize($return[0]['data'], SIMPLEPIE_CONSTRUCT_MAYBE_HTML, $item->get_base($return[0]));
			}
			elseif ($return = $item->get_item_tags(SIMPLEPIE_NAMESPACE_RSS_090, 'image'))
			{
				$img_urls[]= $item->serialize($return[0]['data'], SIMPLEPIE_CONSTRUCT_MAYBE_HTML, $item->get_base($return[0]));
			}
			elseif ($return = $item->get_item_tags(SIMPLEPIE_NAMESPACE_RSS_20, 'image'))
			{
				$img_urls[]= $item->serialize($return[0]['data'], SIMPLEPIE_CONSTRUCT_MAYBE_HTML, $item->get_base($return[0]));
			}
			elseif ($return = $item->get_item_tags(SIMPLEPIE_NAMESPACE_DC_11, 'image'))
			{
				$img_urls[]= $item->serialize($return[0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
			}
			elseif ($return = $item->get_item_tags(SIMPLEPIE_NAMESPACE_DC_10, 'image'))
			{
				$img_urls[]= $item->serialize($return[0]['data'], SIMPLEPIE_CONSTRUCT_TEXT);
			}
			
			//get from description
			preg_match_all('/<img[^>]+>/i',$item->get_content(), $imgs);
			$url_img=	array();
			$imgs=	Arr::flatten($imgs);
			if(is_array($imgs)&& Arr::has($imgs,[0])) {
				foreach ($imgs as $img){
					$aimg=	trim($img);
					preg_match_all('/(src)=("[^"]*")/i',$aimg, $url_img[]);

					if (!empty($url_img[0][2][0])) {
						$ret=					trim($url_img[0][2][0]);
						$img_urls[]=		str_replace('"', '', $ret);
					}
				}
			}
			
			//get from thumbnail
			$thumbnail=		$item->get_thumbnail();
			if (!empty($thumbnail)) $img_urls[]= $thumbnail['url'];
			
			//get from enclosure
			$enc=	$item->get_enclosure()->link;
			if(!empty($enc)) $img_urls[]=	$enc;
			
			//get properties
			if (!is_array($img_urls) || empty($img_urls)) return null;		
			
			$img_urls=	Arr::flatten($img_urls);
			$img_urls=	array_unique($img_urls);
			
			$media=	[];
			foreach ($img_urls as $img_url){
				$media[]= (object) $this->getUrlProperties($img_url);
			}
			
			return $media;
		}

		
    public function __construct($config)
    {
        $this->config = $config;
    }

				/**
     * @param array $feedUrl RSS URL
     * @param int   $limit    items returned per-feed with multifeeds
     * @param bool  $forceFeed
     * @param null  $options
     * @return simplePie
     */
    public function make($feedUrl = [], $limit = 0, $forceFeed = false, $options = null)
    {
        $this->simplePie = new SimplePie();
        $this->configure();
        $this->simplePie->set_feed_url($feedUrl);
        $this->simplePie->set_item_limit($limit);

        if ($forceFeed === true) {
            $this->simplePie->force_feed(true);
        }

        $stripHtmlTags = Arr::get($this->config, 'strip_html_tags.disabled', false);

        if (! $stripHtmlTags && ! empty($this->config['strip_html_tags.tags']) && is_array($this->config['strip_html_tags.tags'])) {
            $this->simplePie->strip_htmltags($this->config['strip_html_tags.tags']);
        } else {
            $this->simplePie->strip_htmltags(false);
        }

        if (! $stripHtmlTags && ! empty($this->config['strip_attribute.tags']) && is_array($this->config['strip_attribute.tags'])) {
            $this->simplePie->strip_attributes($this->config['strip_attribute.tags']);
        } else {
            $this->simplePie->strip_attributes(false);
        }

        if (isset($this->config['curl.timeout']) && is_int($this->config['curl.timeout'])) {
            $this->simplePie->set_timeout($this->config['curl.timeout']);
        }

        if (isset($options) && is_array($options)) {
            if (isset($options['curl.options']) && is_array($options['curl.options'])) {
                $this->simplePie->set_curl_options($this->simplePie->curl_options + $options['curl.options']);
            }

            if (isset($options['strip_html_tags.tags']) && is_array($options['strip_html_tags.tags'])) {
                $this->simplePie->strip_htmltags($options['strip_html_tags.tags']);
            }

            if (isset($options['strip_attribute.tags']) && is_array($options['strip_attribute.tags'])) {
                $this->simplePie->strip_attributes($options['strip_attribute.tags']);
            }

            if (isset($options['curl.timeout']) && is_int($options['curl.timeout'])) {
                $this->simplePie->set_timeout($options['curl.timeout']);
            }
        }

        $this->simplePie->init();

        return $this->simplePie;
    }
		
		public function getItems($feedUrl = [], $limit = 0, $forceFeed = false, $options = null)
		{
			$fed=			$this->make($feedUrl,$limit,$forceFeed,$options);
			$items=		$fed->get_items();
			$result=	[];
			$loop=		1; 
			foreach ($items as $item){
				if($loop > $limit) continue;
				
				//simplepie item method
				$title=					$item->get_title();
				$link=					$item->get_permalink();
				$description=		$item->get_description();
				$content=				$item->get_content();
				$copyright=			$item->get_copyright();
				$pub_date=			$item->get_date('U');
				$categories=		$item->get_categories();
				$authors=				$item->get_authors();				//[name, link, email]
				
				//custom method
				$medias=					$this->getMedia($item);
				
				if	(empty($title) || empty($link))	continue;
				
				$description=		(!empty($description)) ?	strip_tags($description) : null;
				$content=				(!empty($content)) ?	$this->clearTagAtributes($content) : null;
				$pub_date=			(!empty($pub_date)) ? intval($pub_date) : 0;
				
				//Author
				$auth_name= [];
				if (is_array($authors) && !empty($authors)){
					foreach($authors as $author){
						if (!empty($author->name)) $auth_name[]= $author->name;
						else if (!empty($author->link)) $auth_name[]= $author->link;
						else if (!empty($author->email)) $auth_name[]= $author->email;
					}
				}
				$authors= array_unique(Arr::flatten($auth_name));
				
				//Category
				$ctgs_name= [];
				if (is_array($categories) && !empty($categories)){
					foreach($categories as $categorie){
						if (!empty($categorie->term)) $ctgs_name[]=	$categorie->term;
					}
				}
				$categories= array_unique(Arr::flatten($ctgs_name));
				
				$result[]= compact(
					'title',
					'link',
					'description',
					'content',
					'copyright',
					'pub_date',
					'categories',
					'authors',
					'medias');
				
				$loop++;
			}
			
			return $result;
		}
		
		
}

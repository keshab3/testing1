<?php

class B2S_Meta {

    static private $instance = null;
    public $print;
    public $post;
    public static $meta_prefix = '_b2s_post_meta';
    public $metaData = false;
    public $options;
    public $author;

    static public function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function _run() {
        global $post;
        $this->post = $post;
        $this->print = true;
        $post_id = isset($this->post->ID) ? $this->post->ID : 0; //V5.1.0 optimization
        $this->getMeta($post_id);
        $this->options = get_option('B2S_PLUGIN_GENERAL_OPTIONS');
        $authorData = new B2S_Options((isset($this->post->post_author) ? $this->post->post_author : 0));
        $this->author = $authorData->_getOption('meta_author_data');

        //Check 3rd Plugin Yoast - override
        if (isset($this->options['og_active']) && (int) $this->options['og_active'] == 1) {  //on
            $yoast = get_option('wpseo_social');
            if (is_array($yoast) && isset($yoast['opengraph']) && $yoast['opengraph'] !== false && defined('WPSEO_VERSION')) { //plugin with settings is active
                $this->override3rdYoast();
            } else {
                $this->getOgMeta();
            }
        }
        //Check 3rd Plugin Yoast - override
        if (isset($this->options['card_active']) && (int) $this->options['card_active'] == 1) {  //on
            $yoast = get_option('wpseo_social');
            if (is_array($yoast) && isset($yoast['twitter']) && $yoast['twitter'] !== false && defined('WPSEO_VERSION')) {//plugin with settings is active
                $this->override3rdYoast('card');
            } else {
                $this->getCardMeta();
            }
        }
        if (isset($this->options['oembed_active']) && (int) $this->options['oembed_active'] == 0) {  //on
            remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
        }

        //SEO
        if (!defined('WPSEO_VERSION') && (isset($this->options['og_active']) && (int) $this->options['og_active'] == 1) || isset($this->options['card_active']) && (int) $this->options['card_active'] == 1) {
            $this->getAuthor();
        }
    }
    //Nulled By Morehere
    //other plugins remove
    /* public function _remove() {
      $this->options = get_option('B2S_PLUGIN_GENERAL_OPTIONS');
      //Remove other plugin meta tags
      if ((isset($this->options['og_active']) && (int) $this->options['og_active'] == 1) || (isset($this->options['card_active']) && (int) $this->options['card_active'] == 1)) {
      //Remove Social Warfare tags open graph tags (Sorry Social Warfare guys - You do a great job)
      add_filter('sw_meta_tags', '__return_false', 99);
      if ((isset($this->options['og_active']) && (int) $this->options['og_active'] == 1)) {
      add_filter('mashsb_opengraph_meta', '__return_false', 99);
      if (class_exists('JetPack')) {
      add_filter('jetpack_enable_opengraph', '__return_false', 99);
      }
      }
      if ((isset($this->options['card_active']) && (int) $this->options['card_active'] == 1)) {
      add_filter('mashsb_twittercard_meta', '__return_false', 99);
      }
      }
      } */

    public function getOgMeta() {
        if ($this->print) {
            echo "\n<!-- Open Graph Meta Tags generated by Blog2Social " . esc_html(B2S_PLUGIN_VERSION) . " - https://www.blog2social.com -->\n";
        }
        $this->getTitle();
        $this->getDesc();
        $this->getUrl();
        //$this->getPublishDate();
        $this->getImage();
        //$this->getSocialAuthor();
        $this->getObjectType();
        $this->getLocale();
        if ($this->print) {
            echo "<!-- Open Graph Meta Tags generated by Blog2Social " . esc_html(B2S_PLUGIN_VERSION) . " - https://www.blog2social.com -->\n";
        }
    }

    public function getCardMeta() {
        if ($this->print) {
            echo "\n<!-- Twitter Card generated by Blog2Social " . esc_html(B2S_PLUGIN_VERSION) . " - https://www.blog2social.com -->\n";
        }
        $this->getCardType();
        $this->getTitle('card');
        $this->getDesc('card');
        $this->getImage('card');
        //$this->getSocialAuthor('card');
        if ($this->print) {
            echo "<!-- Twitter Card generated by Blog2Social " . esc_html(B2S_PLUGIN_VERSION) . " - https://www.blog2social.com -->\n";
        }
    }

    private function getCardType() {
        $typeData = array('summary', 'summary_large_image');
        $type = (isset($this->options['card_default_type']) && !empty($this->options['card_default_type'])) ? $typeData[$this->options['card_default_type']] : 'summary';
        if ($this->print) {
            echo '<meta name="twitter:card" content="' . esc_attr(apply_filters('b2s_card_meta_type', $type)) . '">' . "\n";
        } else {
            return $type;
        }
    }

    private function getTitle($type = 'og') {
        if (is_singular()) {
            if (isset($this->metaData[$type . '_title']) && !empty($this->metaData[$type . '_title'])) {
                $title = $this->metaData[$type . '_title'];
            } else {
                $title = get_the_title();
            }
        } else {
            $title = (isset($this->options[$type . '_default_title']) && !empty($this->options[$type . '_default_title'])) ? $this->options[$type . '_default_title'] : get_bloginfo('name');
        }
        $title = html_entity_decode($title, ENT_QUOTES | ENT_XML1);
        if ($this->print) {
            if ($type == 'og') {
                echo '<meta property="og:title" content="' . esc_attr(apply_filters('b2s_og_meta_title', $title)) . '"/>' . "\n";
            } else {
                echo '<meta name="twitter:title" content="' . esc_attr(apply_filters('b2s_card_meta_title', $title)) . '"/>' . "\n";
            }
        } else {
            return $title;
        }
    }

    //case Linkedin
    /* private function getPublishDate() {
      $date = date('c');
      echo '<meta name="publish_date" property="og:publish_date" content="' . esc_attr(apply_filters('b2s_og_meta_publish_date', $date)) . '" />' . "\n";
      } */

    private function getDesc($type = 'og') {
        if (is_singular()) {
            if (isset($this->metaData[$type . '_desc']) && !empty($this->metaData[$type . '_desc'])) {
                $desc = str_replace("\r\n", ' ', sanitize_textarea_field(strip_shortcodes($this->metaData[$type . '_desc'])));
            } else {
                if (has_excerpt($this->post->ID)) {
                    $desc = sanitize_textarea_field(strip_shortcodes(get_the_excerpt()));
                } else {
                    $desc = str_replace("\r\n", ' ', substr(sanitize_textarea_field(strip_shortcodes($this->post->post_content)), 0, 160));
                }
            }
        } else {
            $desc = (isset($this->options[$type . '_default_desc']) && !empty($this->options[$type . '_default_desc'])) ? $this->options[$type . '_default_desc'] : get_bloginfo('description');
        }
        $desc = html_entity_decode($desc, ENT_QUOTES | ENT_XML1);
        if ($this->print) {
            if ($type == 'og') {
                echo '<meta property="og:description" content="' . esc_attr(apply_filters('b2s_og_meta_desc', $desc)) . '"/>' . "\n";
            } else {
                echo '<meta name="twitter:description" content="' . esc_attr(apply_filters('b2s_card_meta_desc', $desc)) . '"/>' . "\n";
            }
        } else {
            return $desc;
        }
    }

    private function getUrl() {
        $url = home_url();
        if (!is_home()) {
            $parts = parse_url($url);
            if (is_array($parts) && isset($parts['scheme']) && isset($parts['host'])) {
                $url = esc_url_raw($parts['scheme'] . '://' . $parts['host'] . $_SERVER['REQUEST_URI']);
            }
        }
        echo '<meta property="og:url" content="' . esc_url(apply_filters('b2s_og_meta_url', $url)) . '"/>' . "\n";
    }

    private function getImage($type = 'og') {

        $image = '';
        $image_alt = '';
        $image_size = array();
        if (!is_home()) {
            if (isset($this->metaData[$type . '_image']) && !empty($this->metaData[$type . '_image'])) {
                $image = $this->metaData[$type . '_image'];
                if (isset($this->metaData[$type . '_image_alt']) && !empty($this->metaData[$type . '_image_alt'])) {
                    $image_alt = $this->metaData[$type . '_image_alt'];
                } else {
                    if ($id_attachment = attachment_url_to_postid($this->metaData[$type . '_image'])) {
                        $image_alt = get_post_meta($id_attachment, '_wp_attachment_image_alt', true);
                    }
                }
            } else {
                //is set featured image
                if (isset($this->post->ID)) {
                    if ($id_attachment = get_post_thumbnail_id($this->post->ID)) {
                        $image = wp_get_attachment_url($id_attachment, false);
                        $imageMetaData = wp_get_attachment_metadata($id_attachment);
                        $image_alt = get_post_meta($id_attachment, '_wp_attachment_image_alt', true);
                        if (isset($imageMetaData['width']) && isset($imageMetaData['height'])) {
                            $image_size = array($imageMetaData['width'], $imageMetaData['height']);
                        }
                        if (!preg_match('/^https?:\/\//', $image)) {
                            // Remove any starting slash with ltrim() and add one to the end of site_url()
                            $image = site_url('/') . ltrim($image, '/');
                        }
                    }
                }
                if (empty($image)) {
                    //search in Content
                    $images = $this->findImages();
                    //set first
                    if ($images !== false && is_array($images) & isset($images[0])) {
                        $image = $images[0];
                    }
                }
            }
        }
        if ((is_home() || empty($image)) && isset($this->options[$type . '_default_image']) && !empty($this->options[$type . '_default_image'])) {
            $image = $this->options[$type . '_default_image'];
        }

        if (!empty($image)) {
            if ($this->print) {
                if ($type == 'og') {
                    $size = "";
                    if (!isset($this->options['og_imagedata_active']) || (isset($this->options['og_imagedata_active']) && (int) $this->options['og_imagedata_active'] == 1)) {
                        if (empty($image_size)) {
                            if (ini_get('allow_url_fopen') && function_exists('getimagesize')) {
                                $image_size = @getimagesize(esc_url(apply_filters('b2s_og_meta_image', $image)));
                            }
                        }
                        if (!empty($image_size)) {
                            if (isset($image_size[0]) && (int) $image_size[0] > 0 && isset($image_size[1]) && (int) $image_size[1] > 0) {
                                $size = '<meta property="og:image:width" content="' . esc_attr($image_size[0]) . '"/>' . "\n";
                                $size .= '<meta property="og:image:height" content="' . esc_attr($image_size[1]) . '"/>' . "\n";
                            }
                            if (isset($image_size['mime']) && !empty($image_size['mime'])) {
                                $size .= '<meta property="og:image:type" content="' . esc_attr($image_size['mime']) . '"/>' . "\n";
                            }
                        }
                    }
                    if (!empty($image_alt)) {
                        echo '<meta property="og:image:alt" content="' . esc_attr($image_alt) . '"/>' . "\n";
                    }

                    echo '<meta property="og:image" content="' . esc_url(apply_filters('b2s_og_meta_image', $image)) . '"/>' . "\n" . $size;
                } else {
                    echo '<meta name="twitter:image" content="' . esc_url(apply_filters('b2s_card_meta_image', $image)) . '"/>' . "\n";
                    if (!empty($image_alt)) {
                        echo '<meta name="twitter:image:alt" content="' . esc_attr($image_alt) . '"/>' . "\n";
                    }
                }
            }
        } else {
            return $image;
        }
    }

    public function getAuthor() {
        if (isset($this->post->post_author)) {
            if ($this->post->post_author > 0 && is_singular()) {
                $author_meta = get_the_author_meta('display_name', $this->post->post_author);
                echo '<meta name="author" content="' . trim(esc_attr($author_meta)) . '"/>' . "\n";
            }
        }
    }

    public function getObjectType() {
        if (!isset($this->options['og_objecttype_active']) || (isset($this->options['og_objecttype_active']) && (int) $this->options['og_objecttype_active'] == 1)) {
            if (!is_front_page() && isset($this->post->ID)) {
                echo '<meta property="og:type" content="article"/>' . "\n";
                echo '<meta property="og:article:published_time" content="' . esc_attr($this->post->post_date) . '"/>' . "\n";
                echo '<meta property="og:article:modified_time" content="' . esc_attr($this->post->post_modified) . '"/>' . "\n";
                $tags = get_the_tags($this->post->ID);
                if ($tags !== false && is_array($tags) && !empty($tags)) {
                    foreach ($tags as $tag) {
                        echo '<meta property="og:article:tag" content="' . esc_attr($tag->name) . '"/>' . "\n";
                    }
                }
            } else {
                echo '<meta property="og:type" content="website"/>' . "\n";
            }
        }
    }

    public function getLocale() {
        if (!isset($this->options['og_locale_active']) || (isset($this->options['og_locale_active']) && (int) $this->options['og_locale_active'] == 1)) {
            if (isset($this->options['og_locale']) && !empty($this->options['og_locale'])) {
                echo '<meta property="og:locale" content="' . esc_attr($this->options['og_locale']) . '"/>' . "\n";
            }
        }
    }

    private function findImages() {
        if (!is_object($this->post)) {
            return false;
        }
        $content = $this->post->post_content;
        $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $content, $matches);

        if ($output === FALSE) {
            return false;
        }
        $images = array();
        foreach ($matches[1] as $match) {
            if (!preg_match('/^https?:\/\//', $match)) {
                $match = site_url('/') . ltrim($match, '/');
            }
            $images[] = $match;
        }
        return $images;
    }

    public function getMeta($postId = 0) {
        $postId = ((int) $postId > 0) ? $postId : (isset($this->post->ID) && is_object($this->post->ID) ? $this->post->ID : 0);
        if ($postId > 0) {
            $this->metaData = get_post_meta($postId, self::$meta_prefix, true);
            return $this->metaData;
        } else {
            return false;
        }
    }

    public function setMeta($key, $value) {
        $update = false;
        if (!is_array($this->metaData) || $this->metaData === false) {
            $this->metaData = array($key => $value);
            $update = true;
        } else {
            foreach ($this->metaData as $k) {
                if (isset($this->metaData[$key])) {
                    $this->metaData[$key] = $value;
                    $update = true;
                }
            }
            if (!$update) {
                if (is_array($this->metaData)) {
                    $this->metaData[$key] = $value;
                }
            }
        }
    }

    public function updateMeta($postId) {
        if (!add_post_meta($postId, self::$meta_prefix, $this->metaData, true)) {
            update_post_meta($postId, self::$meta_prefix, $this->metaData);
        }
        return true;
    }

    public function deleteMeta($post_id) {
        return delete_post_meta($post_id, self::$meta_prefix);
    }

    public function override3rdYoast($type = 'og') {
        $this->print = true;
        if ($type == 'og' && ( (is_array($this->metaData)) || isset($this->options['og_active']) && (int) $this->options['og_active'] == 1)) {
            add_filter('wpseo_opengraph_title', '__return_false');
            $this->getTitle();
            add_filter('wpseo_opengraph_desc', '__return_false');
            $this->getDesc();
            add_filter('wpseo_opengraph_image', '__return_false');
            $this->getImage();
            add_filter('wpseo_opengraph_type', '__return_false');
            $this->getObjectType();
            add_filter('wpseo_og_locale', '__return_false');
            $this->getLocale();
            /* if ($this->author !== false && isset($this->author['og_article_author']) && !empty($this->author['og_article_author'])) {
              add_filter('wpseo_opengraph_author_facebook', '__return_false');
              $this->getSocialAuthor();
              } */
        }

        if ($type == 'card' && ( (is_array($this->metaData)) || isset($this->options['card_active']) && (int) $this->options['card_active'] == 1)) {
            add_filter('wpseo_twitter_card_type', '__return_false');
            $this->getCardType();
            add_filter('wpseo_twitter_title', '__return_false');
            $this->getTitle('card');
            add_filter('wpseo_twitter_description', '__return_false');
            $this->getDesc('card');
            add_filter('wpseo_twitter_image', '__return_false');
            $this->getImage('card');
            /* if ($this->author !== false && isset($this->author['card_twitter_creator']) && !empty($this->author['card_twitter_creator'])) {
              add_filter('wpseo_twitter_creator_account', '__return_false');
              $this->getSocialAuthor('card');
              } */
        }
    }

    /* 3rd Party - Yoast SEO */

    public function is_yoast_seo_active() {
        if (defined('WPSEO_VERSION')) {
            $yoast = get_option('wpseo_social');
            $result = array();
            if (is_array($yoast) && ((isset($yoast['opengraph']) && $yoast['opengraph'] !== false ) || ( isset($yoast['twitter']) && $yoast['twitter'] !== false) )) {
                return true;
            }
        }
        return false;
    }

    /* 3rd Party - All in One SEO Pack */

    public function is_aioseop_active() {
        if (defined('AIOSEOP_VERSION')) {
            return true;
        }
        return false;
    }

    /* 3rd Party - Facebook Open Graph, Google+ and Twitter Card Tag */

    public function is_webdados_active() {
        if (defined('WEBDADOS_FB_VERSION')) {
            return true;
        }
        return false;
    }

    /* Own Social Meta Tags */

    public function is_b2s_active() {
        $this->options = get_option('B2S_PLUGIN_GENERAL_OPTIONS');
        if ((isset($this->options['og_active']) && (int) $this->options['og_active'] == 1 ) || (isset($this->options['card_active']) && (int) $this->options['card_active'] == 1)) {  //on
            return true;
        }
        return false;
    }

}

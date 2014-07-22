<?php

namespace Tiqbiz\Api;

class Posts extends Sync
{

    public function __construct()
    {
        parent::__construct();

        add_action('add_meta_boxes', array($this, 'addBoxesMetaBox'));
        add_action('save_post', array($this, 'syncPost'));
    }

    public function addBoxesMetaBox()
    {
        if (!$this->boxes) {
            return;
        }

        add_meta_box(
            'tiqbiz-api-boxes-metabox',
            'Tiqbiz Boxes',
            array($this, 'renderBoxesMetaBox'),
            'post',
            'side',
            'high'
        );
    }

    public function syncPost($post_id)
    {
        if (!$this->validateSave($post_id)) {
            return;
        }

        $this->savePostBoxes($post_id);

        $post = get_post($post_id);

        if ($post->post_type != 'post') {
            return;
        }

        $tiqbiz_api_id = (int)get_post_meta($post_id, '_tiqbiz_api_id', true);

        $post_data = array();

        if ($tiqbiz_api_id) {
            $post_data['id'] = $tiqbiz_api_id;
        }

        $post_data['title'] = $post->post_title;

        $boxes = $this->getPostBoxes($post_id);

        if ($post->post_status == 'publish' && $boxes) {
            $post_data['content'] = wpautop($post->post_content);
            $post_data['boxes'] = $boxes;

            $this->queueAction($post_id, 'createPost', $post_data);
        } else if ($tiqbiz_api_id) {
            $this->queueAction($post_id, 'deletePost', $post_data);
        }
    }

}
<?php

namespace Tiqbiz\Api;

class Api
{

    protected $endpoint = 'http://www.tiqbiz.com/api/endpoint.php';

    protected $cid = '';
    protected $api_key = '';
    protected $boxes = array();
    protected $timeout = 30;

    public function __construct()
    {
        $settings = get_option('tiqbiz_api_settings');

        if (isset($settings['cid'])) {
            $this->cid = $settings['cid'];
        }

        if (isset($settings['api_key'])) {
            $this->api_key = $settings['api_key'];
        }

        if (isset($settings['boxes'])) {
            $this->boxes = $settings['boxes'];
        }

        if (isset($settings['timeout'])) {
            $this->timeout = $settings['timeout'];
        }
    }

    protected function getApiAuthName()
    {
        return $this->apiAction('getAuthName');
    }

    protected function getBoxes()
    {
        $boxes = $this->apiAction('getBoxes');

        $boxes = array_filter(array_map(function($box_details) {
            if (in_array('Newsfeed', $box_details->newsletters)) {
                return array(
                    'name' => $box_details->name,
                    'description' => $box_details->description,
                    'slug' => $box_details->slug
                );
            } else {
                return false;
            }
        }, $boxes));

        usort($boxes, function($a, $b) {
            if ($a['slug'] == $b['slug']) {
                return 0;
            }

            return $a['slug'] < $b['slug'] ? -1 : 1;
        });

        return $boxes;
    }

    protected function getPluginVersion($plugin = null) {
        if ($plugin) {
            $plugin_details = @get_plugin_data(ABSPATH . 'wp-content/plugins/' . $plugin);

            if (strlen($plugin_details['Name'])) {
                if (!is_plugin_active($plugin)) {
                    return $plugin_details['Version'] . ' (Inactive)';
                }
            } else {
                return 'Not Installed';
            }
        } else {
            $plugin_details = get_plugin_data(TIQBIZ_API_PLUGIN_PATH);
        }

        return $plugin_details['Version'];
    }

    protected function apiAction($method, $payload = '', $raw_response = false)
    {
        $post_fields = array(
            'cid'      => $this->cid,
            'auth_key' => $this->api_key,
            $method    => json_encode($payload, JSON_NUMERIC_CHECK)
        );

        $post_query = http_build_query($post_fields);

        $endpoint = $this->endpoint;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,            $endpoint   );
        curl_setopt($ch, CURLOPT_POST,           1           );
        curl_setopt($ch, CURLOPT_POSTFIELDS,     $post_query );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true        );

        $response = curl_exec($ch);

        if (!$response) {
            throw new \Exception(curl_error($ch));
        }

        curl_close($ch);

        if ($raw_response) {
            return $response;
        }

        $results = json_decode($response);

        if ($results->success) {
            return $results->data;
        } else {
            throw new \Exception($results->error_message);
        }
    }

}
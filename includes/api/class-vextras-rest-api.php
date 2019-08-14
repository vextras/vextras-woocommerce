<?php

class Vextras_Rest_Api extends WP_REST_Controller {

    protected $version = 1;
    protected $plugin_name = 'vextras-woocommerce';
    protected $plugin_options;

    /**
     *
     */
    protected function register_route_schema()
    {
        register_rest_route( 'vextras', '/' . $this->getRouteBase() . '/schema', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array( $this, 'get_public_item_schema' ),
        ));
    }

    /**
     * @return string
     */
    protected function get_api_url()
    {
        return 'vextras';
    }

    /**
     * @return string
     */
    protected function getRouteBase()
    {
        return '/';
    }

    /**
     * @param $params
     * @return array
     */
    protected function extract_pagination_params($params)
    {
        $page = 1;
        $per_page = 10;
        $sort = false;

        if (isset($params['page'])) $page = (int) $params['page'];
        if (isset($params['per_page'])) $per_page = (int) $params['per_page'];
        if (isset($params['sort'])) $sort = $params['sort'];

        return array($page, $per_page, $sort);
    }

    /**
     * Check if a given request has access to get a specific item
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function get_item_permissions_check( $request ) {
        return $this->auth_callback( $request );
    }

    /**
     * Check if a given request has access to create items
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function create_item_permissions_check( $request ) {
        return $this->auth_callback( $request );
    }

    /**
     * Check if a given request has access to update a specific item
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function update_item_permissions_check( $request ) {
        return $this->auth_callback( $request );
    }

    /**
     * Check if a given request has access to delete a specific item
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function delete_item_permissions_check( $request ) {
        return $this->auth_callback( $request );
    }

    /**
     * Get the query params for collections
     *
     * @return array
     */
    public function get_collection_params() {
        return array(
            'page'     => array(
                'description'       => 'Current page of the collection.',
                'type'              => 'integer',
                'default'           => 1,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'description'       => 'Maximum number of items to be returned in result set.',
                'type'              => 'integer',
                'default'           => 10,
                'sanitize_callback' => 'absint',
            ),
            'sort' => array(
                'description'       => 'Sorting',
                'type'              => 'string',
                'default'           => false,
                'sanitize_callback' => 'absint',
            ),
            'search'   => array(
                'description'       => 'Limit results to those matching a string.',
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        );
    }

    /**
     * Check if a given request has access to get items
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function auth_callback( $request ) {
        $token = $request->get_header('Authorization');
        if (!empty($token)) {
            $token = str_replace('Bearer ', '', $token);
        }
        if (empty($token)) {
            $params = $request->get_query_params();
            $token = array_key_exists('token', $params) ? $params['token'] : $request->get_header('Authorization');
        }
        if (empty($token)) return false;
        $api_key = $this->getOption('unique_install_key');
        if (empty($api_key)) return false;

        return $token === $api_key;
    }

    /**
     * @param $key
     * @param null $default
     * @return null
     */
    public function getOption($key, $default = null)
    {
        $options = $this->getOptions();
        if (isset($options[$key])) {
            return $options[$key];
        }
        return $default;
    }

    /**
     * @param $key
     * @param bool $default
     * @return bool
     */
    public function hasOption($key, $default = false)
    {
        return (bool) $this->getOption($key, $default);
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        if (empty($this->plugin_options)) {
            $this->plugin_options = get_option($this->plugin_name);
        }
        return is_array($this->plugin_options) ? $this->plugin_options : array();
    }

}

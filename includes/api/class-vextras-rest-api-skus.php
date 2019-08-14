<?php

class Vextras_Rest_Api_Skus extends Vextras_Rest_Api {

    /**
     * @return string
     */
    protected function getRouteBase()
    {
        return '/skus';
    }

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes() {
        $namespace = $this->get_api_url();
        $base = $this->getRouteBase();

        register_rest_route($namespace, $base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'auth_callback' ),
                'args'                => array(),
            )
        ));

        register_rest_route($namespace, $base.'/pagination_meta', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_pagination_meta' ),
                'permission_callback' => array( $this, 'auth_callback' ),
                'args'                => array(),
            )
        ));

        register_rest_route($namespace, $base.'/specific', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_many' ),
                'permission_callback' => array( $this, 'auth_callback' ),
                'args'                => array(),
            )
        ));

        register_rest_route($namespace, $base.'/get/'. '(?P<sku>[a-zA-Z0-9-]+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_item'),
                'permission_callback' => array($this, 'auth_callback'),
                'args' => array(),
            )
        ));

        $this->register_route_schema();
    }

    /**
     * Get a collection of items
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_pagination_meta( $request ) {

        list ($page, $per_page) = $this->extract_pagination_params($request->get_query_params());

        if (isset($q['page'])) $page = (int) $page;
        if (isset($q['per_page'])) $per_page = (int) $per_page;

        $response = wc_get_products(array(
            'paginate' => true,
            'page' => $page,
            'limit' => $per_page,
            //'status' => 'publish',
        ));

        return new WP_REST_Response((object) array('pages' => $response->max_num_pages, 'products' => $response->total), 200);
    }

    /**
     * Get a collection of items
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_items( $request ) {

        list ($page, $per_page, $sort) = $this->extract_pagination_params($request->get_query_params());

        $query = array(
            'paginate' => true,
            'page' => $page,
            'limit' => $per_page,
            //'status' => 'publish',
        );

        if ($sort && ($parts = str_getcsv(trim($sort))) && count($parts) > 1) {
            $query['orderby']  = 'meta_value_num';
            $query['order']    = strtolower($parts[1]) === 'desc' ? 'DESC' : 'ASC';
            $query['meta_key'] = $parts[0];
        }

        $response = wc_get_products($query);

        foreach ($response->products as $key => $result) {
            /** @var \WC_Product $result */
            $response->products[$key] = (object) array(
                'id' => $result->get_id(),
                'sku' => $result->sku,
                'qty' => $result->get_stock_quantity(),
                'price' => $result->get_price(),
                'status' => $result->post->post_status,
                'created_at' => $result->post->post_date_gmt
            );
        }

        return new WP_REST_Response($response, 200);
    }

    /**
     * Get one item from the collection
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_many($request)
    {
        try {
            $skus = str_getcsv($request->get_param('skus'));

            $results = array();
            foreach ($skus as $sku) {
                $product_id = wc_get_product_id_by_sku(trim($sku));
                $product = wc_get_product($product_id);
                $results [] = (object) array(
                    'id' => $product->get_id(),
                    'sku' => $product->sku,
                    'qty' => $product->get_stock_quantity(),
                    'price' => $product->get_price(),
                    'status' => $product->post->post_status,
                    'created_at' => $product->post->post_date_gmt
                );
            }

            return new WP_REST_Response($results, 200);
        } catch (\Exception $e) {
            return new WP_Error( 404, __($e->getMessage(), 'vextras-woocommerce' ) );
        }
    }

    /**
     * Get one item from the collection
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_item($request) {
        $sku = $request->get_param('sku');

        $product_id = wc_get_product_id_by_sku($sku);
        $product = wc_get_product($product_id);

        if ($product->get_id() < 1) {
            return new WP_Error( 404, __( 'Product Not Found', 'vextras-woocommerce' ) );
        }
        return new WP_REST_Response((object) array(
            'id' => $product->get_id(),
            'sku' => $product->sku,
            'qty' => $product->get_stock_quantity()
        ), 200);
    }
}

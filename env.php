<?php
global $wp_version;

return (object) array(
    'repo' => 'master',
    'environment' => 'production',
    'version' => '2.0.1',
    'api_endpoint' => 'https://staging.app.vextras.com/third_party/notify/woo',
    'wp_version' => (empty($wp_version) ? 'Unknown' : $wp_version),
);

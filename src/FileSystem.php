<?php namespace GM\ATP;

class FileSystem {

    public function getFolder() {
        $upload = wp_upload_dir();
        $path = trailingslashit( $upload[ 'basedir' ] ) . 'ajax_query_template/cache';
        return is_dir( $path ) || wp_mkdir_p( $path ) ? $path : FALSE;
    }

}
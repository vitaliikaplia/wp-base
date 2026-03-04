<?php

if(!defined('ABSPATH')){exit;}

function remove_quick_edit_cpt( $actions, $post ) {
    $post_types = array(
        'mail-log',
        'patterns',
    );
//    if(!get_option('allow_quick_edit_for_catalog_items')){
//        $post_types[] = 'catalog';
//    }
    if(in_array($post->post_type, $post_types)){
        unset($actions['edit']);
        unset($actions['inline hide-if-no-js']);
    }
    return $actions;
}
add_filter('post_row_actions','remove_quick_edit_cpt',10,2);

function only_trash_bulk_action_for_mail_log( $bulk_actions ) {
    if ( isset($bulk_actions['edit']) ) {
        unset($bulk_actions['edit']); // прибирає "Редагувати"
    }
    return $bulk_actions;
}
add_filter( 'bulk_actions-edit-mail-log', 'only_trash_bulk_action_for_mail_log' );
add_filter( 'bulk_actions-edit-patterns', 'only_trash_bulk_action_for_mail_log' );

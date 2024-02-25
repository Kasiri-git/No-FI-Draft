<?php
/**
 * Plugin Name: No FI Draft
 * Description: アイキャッチが設定されていない投稿を下書きに戻すか削除するプラグインです。
 * Version: 1.0.0
 * Author: Kasiri
 * Author URI: https://kasiri.icu
 */

// メニューページを追加するためのフック
function add_plugin_menu_item() {
    add_menu_page(
        'No Featured Image Draft Settings',
        'No FI Draft',
        'manage_options',
        'no-fi-draft-settings',
        'no_fi_draft_settings_page',
        'dashicons-format-image',
        20
    );
}
add_action('admin_menu', 'add_plugin_menu_item');

// 設定ページのコンテンツ
function no_fi_draft_settings_page() {
    ?>
    <div class="wrap">
        <h2>アイキャッチ未設定の投稿管理</h2>
        <p>アイキャッチが設定されていない投稿を下書きに戻すか削除するアクションを選択してください。</p>
        <p>削除できるのは公開中の記事だけです。</p>
        <form method="post">
            <input type="radio" id="action_draft" name="action" value="draft" checked>
            <label for="action_draft">下書きに戻す</label><br>
            <input type="radio" id="action_delete" name="action" value="delete">
            <label for="action_delete">削除する</label><br><br>
            <input type="hidden" name="no_fi_draft_action" value="no_fi_draft">
            <?php wp_nonce_field( 'no_fi_draft_action', 'no_fi_draft_nonce' ); ?>
            <input type="submit" class="button button-primary" value="実行">
        </form>
    </div>
    <?php
}

// メインの処理
function no_featured_image_to_draft_or_delete( $action ) {
    // アイキャッチが設定されていない投稿を取得
    $args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => '_thumbnail_id',
                'compare' => 'NOT EXISTS',
            ),
        ),
    );
    $query = new WP_Query( $args );

    // 選択されたアクションに応じて処理を行う
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            if ( $action === 'draft' ) {
                wp_update_post( array(
                    'ID'          => get_the_ID(),
                    'post_status' => 'draft',
                ) );
            } elseif ( $action === 'delete' ) {
                wp_delete_post( get_the_ID(), true );
            }
        }
        wp_reset_postdata();
    }
}

// ボタンがクリックされたときに実行される処理
function handle_no_featured_image_to_draft_or_delete() {
    if ( isset( $_POST['no_fi_draft_action'] ) && $_POST['no_fi_draft_action'] === 'no_fi_draft' ) {
        if ( ! isset( $_POST['no_fi_draft_nonce'] ) || ! wp_verify_nonce( $_POST['no_fi_draft_nonce'], 'no_fi_draft_action' ) ) {
            wp_die( 'セキュリティチェックに失敗しました' );
        }

        $action = $_POST['action'];

        no_featured_image_to_draft_or_delete( $action );

        // 実行が完了したらリダイレクト
        wp_redirect( admin_url( 'admin.php?page=no-fi-draft-settings&success=true' ) );
        exit;
    }
}
add_action( 'admin_init', 'handle_no_featured_image_to_draft_or_delete' );

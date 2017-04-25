<?php
/*
Plugin Name: Mastodon For WordPress
Plugin URI: https://github.com/hseki-luckey/mastodon-for-wp
Description: Mastodonに投稿の公開を通知する
Version: 1.0
Author: hseki
Author URI: https://tech.linkbal.co.jp
License: GPL2
*/

add_action('admin_menu', 'add_mastodon_relation_menu');

function add_mastodon_relation_menu(){
	add_options_page('Mastodon連携', 'Mastodon連携', 'manage_options', 'mastodon-for-wp', 'mastodon_setting_form');
}

function mastodon_setting_form(){
?>
<style>input[type="text"] { width: 60%; }</style>
<div class="wrap">
	<h2>Mastodon連携設定</h2>
	<form method="post" action="options.php">
		<?php wp_nonce_field('update-options'); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">インスタンス</th>
				<td><input type="text" name="mastodon_instance" value="<?php echo get_option('mastodon_instance'); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">クライアント名</th>
				<td><input type="text" name="mastodon_client_name" value="<?php echo get_option('mastodon_client_name'); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">クライアントID</th>
				<td><input type="text" name="mastodon_client_id" value="<?php echo get_option('mastodon_client_id'); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">クライアントシークレット</th>
				<td><input type="text" name="mastodon_client_secret" value="<?php echo get_option('mastodon_client_secret'); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">認証トークン</th>
				<td><input type="text" name="mastodon_auth_token" value="<?php echo get_option('mastodon_auth_token'); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">bearer</th>
				<td><input type="text" name="mastodon_bearer" value="<?php echo get_option('mastodon_bearer'); ?>" /></td>
			</tr>
		</table>
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="page_options" value="mastodon_instance, mastodon_client_name, mastodon_client_id, mastodon_client_secret, mastodon_auth_token, mastodon_bearer" />
		<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		</p>
	</form>
</div>
<?php
}

function toot_published_post_for_mastodon($post_id){
	require_once("autoload.php");

	$t = new \theCodingCompany\Mastodon();
	$t->PostStatuses("新しい記事が公開されました！\n".get_permalink($post_id));
}

add_action('publish_post', 'toot_published_post_for_mastodon');
?>
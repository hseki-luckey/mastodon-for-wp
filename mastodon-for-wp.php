<?php
/*
Plugin Name: Mastodon For WordPress
Plugin URI: https://github.com/hseki-luckey/mastodon-for-wp
Description: Mastodonに投稿の公開を通知する
Version: 1.0
Author: hseki
Author URI: https://tech.linkbal.co.jp/
License: GPL2
*/

add_action('admin_menu', 'add_mastodon_relation_menu');

function add_mastodon_relation_menu(){
	add_options_page('Mastodon連携', 'Mastodon連携', 'manage_options', 'mastodon-for-wp', 'mastodon_setting_form');
}

/* Mastodon設定 */
function mastodon_setting_options(){
	return array(
		'instance' => get_option('mastodon_instance', ''),
		'client_name' => get_option('mastodon_client_name', ''),
		'client_id' => get_option('mastodon_client_id', ''),
		'client_secret' => get_option('mastodon_client_secret', ''),
		'auth_token' => get_option('mastodon_auth_token', ''),
		'bearer' => get_option('mastodon_bearer', '')
	);
}

/* Mastodon設定画面 */
function mastodon_setting_form(){
	$options = mastodon_setting_options();
	$auth_url = false;
	if(!empty($options['instance']) && !empty($options['client_name'])){
		if(empty($options['client_id']) || empty($options['client_secret'])){
			mastodon_setting_first_step($options['instance'], $options['client_name']);
		}elseif(empty($options['auth_token'])){
			$auth_url = mastodon_setting_second_step();
		}else{
			mastodon_setting_third_step($options['auth_token']);
		}
	}
?>
<style>input[type="text"] { width: 60%; }</style>
<div class="wrap">
<h2>Mastodon連携設定</h2>
<form method="post" action="options.php">
	<?php wp_nonce_field('update-options'); ?>
	<?php if($auth_url): ?>
	<div class="updated notice">
		<p><strong>アプリの認証・<a href="<?php echo $auth_url; ?>" target="_blank">認証トークンを取得</a>をしてください。</strong></p>
	</div>
	<?php endif; ?>
	<table class="form-table">
		<tr valign="top">
			<th scope="row">インスタンス</th>
			<td><input type="text" name="mastodon_instance" value="<?php echo $options['instance']; ?>" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">クライアント名</th>
			<td><input type="text" name="mastodon_client_name" value="<?php echo $options['client_name']; ?>" /></td>
		</tr>
		<?php if(!empty($options['client_id'])): ?>
		<tr valign="top">
			<th scope="row">クライアントID</th>
			<td><input type="text" name="mastodon_client_id" value="<?php echo $options['client_id']; ?>" /></td>
		</tr>
		<?php endif; ?>
		<?php if(!empty($options['client_secret'])): ?>
		<tr valign="top">
			<th scope="row">クライアントシークレット</th>
			<td><input type="text" name="mastodon_client_secret" value="<?php echo $options['client_secret']; ?>" /></td>	
		</tr>
		<?php endif; ?>
		<?php if(!empty($options['client_id']) && !empty($options['client_secret'])): ?>
		<tr valign="top">
			<th scope="row">認証トークン</th>
			<td><input type="text" name="mastodon_auth_token" value="<?php echo $options['auth_token']; ?>" /></td>
		</tr>
		<?php endif; ?>
		<?php if(!empty($options['bearer'])): ?>
		<tr valign="top">
			<th scope="row">bearer</th>
			<td><input type="text" name="mastodon_bearer" value="<?php echo $options['bearer']; ?>" /></td>
		</tr>
		<?php endif; ?>
	</table>
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="mastodon_instance, mastodon_client_name, mastodon_client_id, mastodon_client_secret, mastodon_auth_token, mastodon_bearer" />
	<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
</form>
</div>
<?php
}

/* クライアントID・シークレット生成 */
function mastodon_setting_first_step($instance, $client_name){
	require_once('autoload.php');

	$t = new \theCodingCompany\Mastodon();
	$token_info = $t->createApp(get_bloginfo('name'), home_url('/'));

	if($token_info){
		update_option('mastodon_client_id', $token_info['client_id']);
		update_option('mastodon_client_secret', $token_info['client_secret']);
	}
}

/* 認証用URL発行 */
function mastodon_setting_second_step(){
	require_once('autoload.php');

	$t = new \theCodingCompany\Mastodon();
	$auth_url = $t->getAuthUrl();

	return $auth_url;
}

/* bearer発行 */
function mastodon_setting_third_step($auth_token){
	require_once('autoload.php');

	$t = new \theCodingCompany\Mastodon();
	$token_info = $t->getAccessToken($auth_token);

	if($token_info){
		update_option('mastodon_bearer', $token_info);
	}
}

/* 公開時にtoot */
function toot_published_post_for_mastodon($post_id){
	require_once('autoload.php');

	$options = mastodon_setting_options();
	$mastodon_post_flg = true;
	foreach ($options as $key => $value) {
		if(empty($value)){
			$mastodon_post_flg = false;
			break;
		}
	}

	if($mastodon_post_flg){
		$t = new \theCodingCompany\Mastodon();
		$t->PostStatuses(get_the_title($post_id)."\n".get_permalink($post_id));
	}
}

add_action('publish_post', 'toot_published_post_for_mastodon');
?>
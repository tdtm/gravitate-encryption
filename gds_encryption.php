<?php

/*
Plugin Name: Gravitate Encryption
Plugin URI: http://www.gravitatedesign.com/blog/wordpress-and-gravity-forms/
Description: This plugin allows the data stored by Gravity forms and other Plugins to be Encrypted and even sent to another database if needed. The Plugin allows for Symmetric and A-Semmetric Encryption.
Version: 1.0.4
Author: Gravitate
Author URI: http://www.gravitatedesign.com
*/

/////////////////////////////////////
// Enable Settings Page
/////////////////////////////////////

$gds_encryption_enable_settings_page = true;

//////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////

class GDS_Encryption_Class {

	public function init() {
		$options = unserialize( get_option( 'gds_encryption' ) );

		if ( ( ! empty( $options['encription_type'] ) && $options['encription_type'] != 'encryption_none' ) || ! empty( $options['use_remote_storage'] ) ) {
			add_filter( "gform_save_field_value", "gds_encryption_gform_save_field_value", 10, 4 );
		}

		add_filter( "gform_get_input_value", "gds_encryption_gform_get_field_value", 10, 4 );

		if ( ! empty( $options['remote_database_removal'] ) ) {
			add_action( "gform_delete_lead", "gds_encryption_gform_delete_lead" );
		}
	}

	private function email_keys() {
		$msg = '';

		$subject = '';

		if ( ! empty( $_POST['keys_email'] ) && $_POST['keys_type'] == 1 ) {
			$email = trim( $_POST['keys_email'] );

			$subject = 'Your Public and Private Keys';

			$msg = "Below are your Public and Private Keys. Make sure to Keep them for your records.\n\n* Warning: If you lose them and need them you will not be able to Decrypt the data once it has been Encrypted and all the data can be lost FOREVER!\n\n\n";

			// Create the keypair

			$config = [
				"digest_alg"       => "sha1",
				"private_key_bits" => 2048,
				"private_key_type" => OPENSSL_KEYTYPE_RSA,
				"encrypt_key"      => false
			];
			$res    = openssl_pkey_new( $config );

			// Get private key
			openssl_pkey_export( $res, $privkey );

			$msg .= $privkey . "\n";

			// Get public key
			$pubkey = openssl_pkey_get_details( $res );
			$pubkey = $pubkey["key"];

			$msg .= $pubkey;
		} else if ( ! empty( $_POST['keys_email'] ) && $_POST['keys_type'] == 2 ) {
			$email = trim( $_POST['keys_email'] );

			$subject = 'Your Encryption Key';

			$msg = "Below is the Key. Make sure to Keep it for your records.\n\n* Warning: If you lose it and need it you will not be able to Decrypt the data once it has been Encrypted and all the data can be lost FOREVER!\n\n\n";

			$msg .= md5( time() . rand( 100, 999 ) );
		}

		if ( wp_mail( $email, $subject, $msg ) ) {
			return true;
		}

		return false;

	}

	public function custom_submenu_page_callback() {

		$page_link = substr( $_SERVER['REQUEST_URI'], 0, strpos( $_SERVER['REQUEST_URI'], '?' ) );

		$sent_keys = false;

		$encryption_test = "Here's My Phone Number (123) 123-1234";

		if ( ! empty( $_POST['keys_email'] ) && ! empty( $_POST['keys_type'] ) ) {
			if ( $this->email_keys() ) {
				$sent_keys = true;
			}
		}

		$update = false;
		if ( ! empty( $_POST['action'] ) ) {

			$options = get_option( 'gds_encryption' );

			$update = 'error';
			$value  = serialize( $_POST );

			$checkmarks = [ 'encrypt_all_gravity_forms', 'use_remote_storage', 'remote_database_removal' ];
			foreach ( $checkmarks as $checkmark ) {
				if ( empty( $_POST[ $checkmark ] ) ) {
					$_POST[ $checkmark ] = 0;
				}
			}

			if ( md5( $options ) == md5( $value ) ) {
				$update = true;
			} else {
				if ( update_option( 'gds_encryption', $value ) ) {
					$update = true;
				} else if ( add_option( 'gds_encryption', $value, "", "no" ) ) {
					$update = true;
				}
			}
		}

		$mcrypt_available  = ( function_exists( 'mcrypt_encrypt' ) ? true : false );
		$openssl_available = ( function_exists( 'openssl_seal' ) ? true : false );

		$options = unserialize( get_option( 'gds_encryption' ) );

		if ( ! empty( $_POST['submit'] ) && $_POST['submit'] == 'Save Changes & Test Database' ) {
			$this->database_test();
		}

		$form_fields_defaults = [
			'encription_type'                 => 'symmetric',
			'public_key'                      => '',
			'private_key'                     => '',
			'encryption_key'                  => '',
			'encrypt_all_gravity_forms'       => '1',
			'use_remote_storage'              => '',
			'remote_database_removal'         => '1',
			'remote_database_host'            => '',
			'remote_database_port'            => '3306',
			'remote_database_name'            => 'remote_storage',
			'remote_database_username'        => '',
			'remote_database_password'        => '',
			'remote_database_table'           => 'remote_storage_table',
			'remote_database_table_id'        => 'id',
			'remote_database_table_parent_id' => 'parent_id',
			'remote_database_table_value'     => 'value',
			'remote_database_table_group'     => 'group'
		];

		if ( ! $mcrypt_available ) {
			$form_fields_defaults['encription_type'] = 'encryption_weak';
		}

		$form_fields = [ ];
		foreach ( $form_fields_defaults as $key => $value ) {
			if ( isset( $_POST[ $key ] ) ) {
				$form_fields[ $key ] = $_POST[ $key ];
			} else if ( isset( $options[ $key ] ) ) {
				$form_fields[ $key ] = $options[ $key ];
			} else {
				$form_fields[ $key ] = $value;
			}
		}


		?>
		<script type="text/javascript">
			<!--//
			function gds_encryption_update_form() {
				if (document.getElementById('asymmetric').checked) {
					document.getElementById('public_key_container').style.display = '';
					document.getElementById('private_key_container').style.display = '';
				}
				else {
					document.getElementById('public_key_container').style.display = 'none';
					document.getElementById('private_key_container').style.display = 'none';
				}

				if (document.getElementById('symmetric').checked || document.getElementById('encryption_weak').checked) {
					document.getElementById('encryption_key_container').style.display = '';
				}
				else {
					document.getElementById('encryption_key_container').style.display = 'none';
				}

				if (document.getElementById('encryption_none').checked) {
					document.getElementById('encrypt_all_gravity_forms_container').style.display = 'none';
					document.getElementById('enc_test_container').style.display = 'none';
				}
				else {
					document.getElementById('encrypt_all_gravity_forms_container').style.display = '';
					document.getElementById('enc_test_container').style.display = '';
				}

				if (document.getElementById('use_remote_storage').checked) {
					document.getElementById('remote_database_host_container').style.display = '';
					document.getElementById('remote_database_port_container').style.display = '';
					document.getElementById('remote_database_name_container').style.display = '';
					document.getElementById('remote_database_username_container').style.display = '';
					document.getElementById('remote_database_password_container').style.display = '';
					document.getElementById('remote_database_removal_container').style.display = '';
					document.getElementById('remote_database_table_container').style.display = '';
					document.getElementById('remote_database_table_id_container').style.display = '';
					document.getElementById('remote_database_table_value_container').style.display = '';
					document.getElementById('remote_database_table_parent_id_container').style.display = '';
					document.getElementById('remote_database_table_group_container').style.display = '';
					document.getElementById('submit_test').style.display = '';
				}
				else {
					document.getElementById('remote_database_host_container').style.display = 'none';
					document.getElementById('remote_database_port_container').style.display = 'none';
					document.getElementById('remote_database_name_container').style.display = 'none';
					document.getElementById('remote_database_username_container').style.display = 'none';
					document.getElementById('remote_database_password_container').style.display = 'none';
					document.getElementById('remote_database_removal_container').style.display = 'none';
					document.getElementById('remote_database_table_container').style.display = 'none';
					document.getElementById('remote_database_table_id_container').style.display = 'none';
					document.getElementById('remote_database_table_value_container').style.display = 'none';
					document.getElementById('remote_database_table_parent_id_container').style.display = 'none';
					document.getElementById('remote_database_table_group_container').style.display = 'none';
					document.getElementById('submit_test').style.display = 'none';
				}

			}
			function gds_encryption_generate_keys(type) {
				if (type == 1) {
					var email = prompt("Please enter your Email Address to send the keys to:", "");
					if (email) {
						document.getElementById('keys_email').value = email;
						document.getElementById('keys_type').value = 1;
						document.forms['submit_keys'].submit();
					}
				}
				else if (type == 2) {
					var email = prompt("Please enter your Email Address to send the key to:", "");
					if (email) {
						document.getElementById('keys_email').value = email;
						document.getElementById('keys_type').value = 2;
						document.forms['submit_keys'].submit();
					}
				}
			}
			//-->
		</script>
		<form action="<?php echo $page_link;?>?page=gds-encryption-custom-submenu-page" method="post" name="submit_keys" id="submit_keys">
			<input type="hidden" value="" name="keys_email" id="keys_email">
			<input type="hidden" value="" name="keys_type" id="keys_type">
		</form>
		<div class="wrap">
			<div class="icon32" id="icon-options-general"><br></div>
			<h2>Gravitate Encryption Settings</h2>
			<br/>
			<?php if ( $sent_keys ) { ?>
				<div class="updated"><p>Your Key(s) have been Emailed to You! Check your spam folder if you don't see them.</p></div> <?php } ?>
			<?php if ( $update && $update !== 'error' ) { ?>
				<div class="updated"><p>Settings Saved Successfully!</p></div> <?php } ?>
			<?php if ( $update && $update === 'error' ) { ?>
				<div class="error"><p>Error Saving Settings to Database. Please try again.</p></div> <?php } ?>
			<form action="<?php echo $page_link;?>?page=gds-encryption-custom-submenu-page" method="post">
				<input type="hidden" value="update" name="action">
				<table class="form-table">
					<tbody>
					<tr valign="top">
						<th scope="row" width="260" style="width:260px;">Type</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span>Type</span></legend>
								<label for="symmetric">
									<input type="radio"<?php if ( $form_fields['encription_type'] == 'symmetric' ) { ?> checked="checked"<?php } ?>
									       value="symmetric" id="symmetric" name="encription_type"
									       onclick="gds_encryption_update_form();"<?php if ( ! $mcrypt_available ) { ?> disabled="disabled"<?php } ?> />
									Symmetric (Standard)</label><?php if ( ! $mcrypt_available ) { ?><span
									style="color:red;"> &nbsp; ( Unavailable, You need <u>Mcrypt</u> installed on the server )</span><?php } else { ?><span
									style="color:green;"> &nbsp; ( Available )</span><?php } ?><br/>
								<label for="asymmetric">
									<input type="radio"<?php if ( $form_fields['encription_type'] == 'asymmetric' ) { ?> checked="checked"<?php } ?>
									       value="asymmetric" id="asymmetric" name="encription_type"
									       onclick="gds_encryption_update_form();"<?php if ( ! $openssl_available ) { ?> disabled="disabled"<?php } ?> />
									A-Symmetric (For Advanced Users)</label><?php if ( ! $openssl_available ) { ?><span style="color:red;"> &nbsp; ( Unavailable, You need <u>Openssl</u> installed on the server )</span><?php } else { ?>
									<span style="color:green;"> &nbsp; ( Available )</span><?php } ?><br/>
								<label for="encryption_weak">
									<input type="radio"<?php if ( $form_fields['encription_type'] == 'encryption_weak' ) { ?> checked="checked"<?php } ?>
									       value="encryption_weak" id="encryption_weak" name="encription_type" onclick="gds_encryption_update_form();"/>
									Weak Encryption</label> <span
									style="color:gray;"> ( Weak Encryption, but useful if other methods are unavailable. )</span><br/>
								<label for="encryption_none">
									<input type="radio"<?php if ( $form_fields['encription_type'] == 'encryption_none' ) { ?> checked="checked"<?php } ?>
									       value="encryption_none" id="encryption_none" name="encription_type" onclick="gds_encryption_update_form();"/>
									No Encryption</label><br/>
							</fieldset>
						</td>
					</tr>
					<tr id="public_key_container" valign="top">
						<th scope="row"><label for="public_key">Public Key (ONLY)</label></th>
						<td>
							<textarea id="public_key" name="public_key" style="width: 520px; height: 260px;"><?php echo $form_fields['public_key'];?></textarea>
						</td>
					</tr>
					<tr id="private_key_container" valign="top">
						<th scope="row"><label for="private_key">Private Key<br/><br/>LEAVE THIS BLANK if you plan on decrypting from another
								Application</label></th>
						<td>
							<textarea id="private_key" name="private_key"
							          style="width: 520px; height: 300px;"><?php echo $form_fields['private_key'];?></textarea>
							<?php if ( $openssl_available ) { ?><a class="button" href="javascript:gds_encryption_generate_keys(1);">Auto Create
								Keys</a><?php } ?>
						</td>
					</tr>
					<tr id="encryption_key_container" valign="top">
						<th scope="row"><label for="encryption_key">Encryption Key</label></th>
						<td>
							<span style="font-size: 10px; color: gray;">Should be between 8-32 characters and use Number, Letters, and Symbols</span><br/>
							<input type="text" id="encryption_key" name="encryption_key" maxlength="32" value="<?php echo $form_fields['encryption_key'];?>"/>
							&nbsp;
							<?php if ( $mcrypt_available ) { ?><a class="button" href="javascript:gds_encryption_generate_keys(2);">Auto Create
								Key</a><?php } ?>
						</td>
					</tr>
					<tr id="encrypt_all_gravity_forms_container" valign="top">
						<th scope="row">Encrypt All Gravity Forms Data</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span>Encrypt All Gravity Forms Data</span></legend>
								<label for="encrypt_all_gravity_forms">
									<input type="checkbox"<?php if ( $form_fields['encrypt_all_gravity_forms'] ) { ?> checked="checked"<?php } ?> value="1"
									       id="encrypt_all_gravity_forms" name="encrypt_all_gravity_forms" onchange="gds_encryption_update_form();"/>
								</label> &nbsp; <span style="font-size: 10px; color: gray;">For now this is the only option for Gravity Forms. More to come Later...</span>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">Use Remote Storage for Data</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span>Use Remote Storage for Data</span></legend>
								<label for="use_remote_storage">
									<input type="checkbox"<?php if ( $form_fields['use_remote_storage'] ) { ?> checked="checked"<?php } ?> value="1"
									       id="use_remote_storage" name="use_remote_storage" onchange="gds_encryption_update_form();"/>
								</label> &nbsp; <span style="font-size: 10px; color: gray;">This Option will send the data to be stored on a separate Remote Server.  The ID of the Remote Database Primary ID field will be stored on the Local Server for Reference.</span>
							</fieldset>
						</td>
					</tr>
					<tr id="remote_database_removal_container" valign="top">
						<th scope="row">Allow Auto Removal From Remote Database</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span>Allow Auto Removal From Remote Database</span></legend>
								<label for="remote_database_removal">
									<input type="checkbox"<?php if ( $form_fields['remote_database_removal'] ) { ?> checked="checked"<?php } ?> value="1"
									       id="remote_database_removal" name="remote_database_removal"/>
								</label> &nbsp; <span style="font-size: 10px; color: gray;">This Options will delete the Entries from the Remote Server when an Entry gets deleted on the local server.</span>
							</fieldset>
						</td>
					</tr>
					<tr id="remote_database_host_container" valign="top">
						<th scope="row"><label for="remote_database_host">Remote Database Host</label></th>
						<td>
							<input type="text" id="remote_database_host" name="remote_database_host" maxlength="200"
							       value="<?php echo $form_fields['remote_database_host'];?>"/>
						</td>
					</tr>
					<tr id="remote_database_port_container" valign="top">
						<th scope="row"><label for="remote_database_port">Remote Database Port</label></th>
						<td>
							<input type="text" id="remote_database_port" name="remote_database_port" maxlength="10"
							       value="<?php echo $form_fields['remote_database_port'];?>"/>
						</td>
					</tr>
					<tr id="remote_database_name_container" valign="top">
						<th scope="row"><label for="remote_database_name">Remote Database Name</label></th>
						<td>
							<input type="text" id="remote_database_name" name="remote_database_name" maxlength="200"
							       value="<?php echo $form_fields['remote_database_name'];?>"/>
						</td>
					</tr>
					<tr id="remote_database_username_container" valign="top">
						<th scope="row"><label for="remote_database_username">Remote Database Username</label></th>
						<td>
							<input type="text" autocomplete="off" id="remote_database_username" name="remote_database_username" maxlength="200"
							       value="<?php echo $form_fields['remote_database_username'];?>"/>
						</td>
					</tr>
					<tr id="remote_database_password_container" valign="top">
						<th scope="row"><label for="remote_database_password">Remote Database Password</label></th>
						<td>
							<input type="password" autocomplete="off" id="remote_database_password" name="remote_database_password" maxlength="200"
							       value="<?php echo $form_fields['remote_database_password'];?>"/>
						</td>
					</tr>
					<tr id="remote_database_table_container" valign="top">
						<th scope="row"><label for="remote_database_table">Remote Database Table Name</label></th>
						<td>
							<input type="text" autocomplete="off" id="remote_database_table" name="remote_database_table" maxlength="200"
							       value="<?php echo $form_fields['remote_database_table'];?>"/>
						</td>
					</tr>
					<tr id="remote_database_table_id_container" valign="top">
						<th scope="row"><label for="remote_database_table_id">Remote Database Table Primary ID Field Name</label></th>
						<td>
							<input type="text" autocomplete="off" id="remote_database_table_id" name="remote_database_table_id" maxlength="200"
							       value="<?php echo $form_fields['remote_database_table_id'];?>"/>
						</td>
					</tr>
					<tr id="remote_database_table_parent_id_container" valign="top">
						<th scope="row"><label for="remote_database_table_parent_id">Remote Database Table Parent ID Field Name</label></th>
						<td>
							<input type="text" autocomplete="off" id="remote_database_table_parent_id" name="remote_database_table_parent_id" maxlength="200"
							       value="<?php echo $form_fields['remote_database_table_parent_id'];?>"/>
						</td>
					</tr>
					<tr id="remote_database_table_value_container" valign="top">
						<th scope="row"><label for="remote_database_table_value">Remote Database Table Value Field Name</label></th>
						<td>
							<input type="text" autocomplete="off" id="remote_database_table_value" name="remote_database_table_value" maxlength="200"
							       value="<?php echo $form_fields['remote_database_table_value'];?>"/>
						</td>
					</tr>
					<tr id="remote_database_table_group_container" valign="top">
						<th scope="row"><label for="remote_database_table_group">Remote Database Table Group Field Name</label></th>
						<td>
							<input type="text" autocomplete="off" id="remote_database_table_group" name="remote_database_table_group" maxlength="200"
							       value="<?php echo $form_fields['remote_database_table_group'];?>"/>
						</td>
					</tr>
					</tbody>
				</table>
				<p class="submit">
					<input type="submit" value="Save Changes" class="button button-primary" id="submit" name="submit"> &nbsp;
					<input type="submit" value="Save Changes & Test Database" class="button button-primary" id="submit_test" name="submit">
				</p>
			</form>
			<br/>
			<?php if ( ! empty( $form_fields['encription_type'] ) && $form_fields['encription_type'] != 'encryption_none' ) { ?>
				<div id="enc_test_container">
					<h2 id="enc_test">Encryption Test <a class="button"
					                                     href="?page=gds-encryption-custom-submenu-page&encryption_test=1&rand=<?php echo time(); ?>#enc_test">Run
							Test</a></h2>
					<?php if ( ! empty( $_GET['encryption_test'] ) ) { ?><br/>
						<label style="display: inline-block;width: 100px;font-weight: bold;">Plain Text:</label><?php echo $encryption_test; ?><br/><br/>
						<label style="display: inline-block;width: 100px;font-weight: bold;">Encrypted
							Text:</label><?php echo GDS_Encryption_Class::encrypt( $encryption_test ); ?><br/><br/>
						<label style="display: inline-block;width: 100px;font-weight: bold;">Decrypted
							Text:</label><?php echo GDS_Encryption_Class::decrypt( GDS_Encryption_Class::encrypt( $encryption_test ) ); ?><br/>
					<?php } ?>
				</div>
			<?php } ?>

			<br/>

			<h2>Use this plugin with other Plugins or in your functions.php file of your theme</h2>

			<h3>Here are the functions you can use:</h3>
			if(class_exists('GDS_Encryption_Class'))<br/>
			{<br/>
			&nbsp; &nbsp; GDS_Encryption_Class::encrypt($your_value_to_encrypt);<br/>
			}<br/>
			if(class_exists('GDS_Encryption_Class'))<br/>
			{<br/>
			&nbsp; &nbsp; GDS_Encryption_Class::decrypt($your_value_to_decrypt);<br/>
			}<br/><br/><br/>
			<span style="color:#900;">* Warning: This Plugin and the Plugin Provider offers no Support and no Guarantees or Warranty for any lost of stolen data. Use this Plugin at your own risk.</span>
		</div>
		<script type="text/javascript">gds_encryption_update_form();</script>
	<?php
	}

	private function get_database_link() {
		$options = unserialize( get_option( 'gds_encryption' ) );

		return mysqli_connect( $options['remote_database_host'], $options['remote_database_username'], $options['remote_database_password'],
			$options['remote_database_name'], $options['remote_database_port'] );
	}

	private function database_test() {
		$options = unserialize( get_option( 'gds_encryption' ) );

		if ( ! empty( $options['remote_database_username'] ) ) {
			$link = GDS_Encryption_Class::get_database_link();
			if ( ! $link ) {
				?>
				<div class="error">
					<p><?php _e( 'Unable to Connect to the Remote Server! (' . mysqli_connect_error() . ')', 'my-text-domain' ); ?></p>
				</div>
			<?php
			} else {
				?>
				<div class="updated">
					<p><?php _e( 'Remote Server Connected Successfully!', 'my-text-domain' ); ?></p>
				</div>
			<?php
			}
		} else {
			?>
			<div class="error">
				<p><?php _e( 'Error Retrieving Options. Please Try again.', 'my-text-domain' ); ?></p>
			</div>
		<?php
		}
	}

	/**
	 * Replaces failed encrypt() method.
	 * @param $value
	 *
	 * @return bool|string
	 */
	public static function ex_encrypt($value)
	{
		global $gds_encryption_class;

		if ($gds_encryption_class == null)
		{
			$gds_encryption_class = new GDS_Encryption_Class();
			GDS_Encryption_Class::init();
		}

		$options = unserialize( get_option( 'gds_encryption' ) );

		///////////////////////////////////////////////////////////////////////////////////////////////////
		// Symmetric Encryption
		///////////////////////////////////////////////////////////////////////////////////////////////////

		if ( $options['encription_type'] == 'symmetric' ) {
			$salt     = "djkns(235mk^p";
			$password = "7%r?1C" . trim( $options['encryption_key'] ) . "jr-3";
			$key      = hash( 'SHA256', $salt . $password, true );
			srand();
			$iv = mcrypt_create_iv( mcrypt_get_iv_size( MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC ), MCRYPT_RAND );
			if ( strlen( $iv_base64 = rtrim( base64_encode( $iv ), '=' ) ) != 22 ) {
				return false;
			}
			$encrypted = base64_encode( mcrypt_encrypt( MCRYPT_RIJNDAEL_128, $key, $value . md5( $value ), MCRYPT_MODE_CBC, $iv ) );
			$value     = "enx2:" . $iv_base64 . $encrypted;
		} else if ( $options['encription_type'] == 'asymmetric' ) {
			openssl_seal( $value, $encrypted, $ekey, [ openssl_get_publickey( trim( $options['public_key'] ) ) ] );
			$ekey = base64_encode( $ekey[0] );

			$value = "enx1:" . $ekey . ':' . base64_encode( $encrypted );
		} else if ( $options['encription_type'] == 'encryption_weak' ) {
			$key   = "7%r?1C" . trim( $options['encryption_key'] ) . "jr-3";
			$value = base64_encode( $value );
			$value = "enx3:" . base64_encode( substr( $value, 0, 3 ) . substr( $value, 0, - 5 ) . substr( base64_encode( $key ), 0, 6 ) . substr( $value,
						- 5 ) . substr( $value, - 3 ) );
		}

		return $value;
	}

	public function encrypt( $value ) {
		$options = unserialize( get_option( 'gds_encryption' ) );

		///////////////////////////////////////////////////////////////////////////////////////////////////
		// Symmetric Encryption
		///////////////////////////////////////////////////////////////////////////////////////////////////

		if ( $options['encription_type'] == 'symmetric' ) {
			$salt     = "djkns(235mk^p";
			$password = "7%r?1C" . trim( $options['encryption_key'] ) . "jr-3";
			$key      = hash( 'SHA256', $salt . $password, true );
			srand();
			$iv = mcrypt_create_iv( mcrypt_get_iv_size( MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC ), MCRYPT_RAND );
			if ( strlen( $iv_base64 = rtrim( base64_encode( $iv ), '=' ) ) != 22 ) {
				return false;
			}
			$encrypted = base64_encode( mcrypt_encrypt( MCRYPT_RIJNDAEL_128, $key, $value . md5( $value ), MCRYPT_MODE_CBC, $iv ) );
			$value     = "enx2:" . $iv_base64 . $encrypted;
		} else if ( $options['encription_type'] == 'asymmetric' ) {
			openssl_seal( $value, $encrypted, $ekey, [ openssl_get_publickey( trim( $options['public_key'] ) ) ] );
			$ekey = base64_encode( $ekey[0] );

			$value = "enx1:" . $ekey . ':' . base64_encode( $encrypted );
		} else if ( $options['encription_type'] == 'encryption_weak' ) {
			$key   = "7%r?1C" . trim( $options['encryption_key'] ) . "jr-3";
			$value = base64_encode( $value );
			$value = "enx3:" . base64_encode( substr( $value, 0, 3 ) . substr( $value, 0, - 5 ) . substr( base64_encode( $key ), 0, 6 ) . substr( $value,
						- 5 ) . substr( $value, - 3 ) );
		}

		return $value;
	}

	public function decrypt( $value ) {
		$options = unserialize( get_option( 'gds_encryption' ) );

		if ( strpos( $value, "enx" ) !== false ) {
			if ( strpos( $value, "enx2:" ) !== false ) {
				$value     = str_replace( "enx2:", "", $value );
				$salt      = "djkns(235mk^p";
				$password  = "7%r?1C" . trim( $options['encryption_key'] ) . "jr-3";
				$key       = hash( 'SHA256', $salt . $password, true );
				$iv        = base64_decode( substr( $value, 0, 22 ) . '==' );
				$encrypted = substr( $value, 22 );
				$decrypted = rtrim( mcrypt_decrypt( MCRYPT_RIJNDAEL_128, $key, base64_decode( $encrypted ), MCRYPT_MODE_CBC, $iv ), "\0\4" );
				$hash      = substr( $decrypted, - 32 );
				$decrypted = substr( $decrypted, 0, - 32 );
				if ( md5( $decrypted ) != $hash ) {
					$value = '';
				}
				$value = $decrypted;
			} else if ( strpos( $value, "enx1:" ) !== false ) {
				if ( empty( $options['private_key'] ) ) {
					$value = 'XXXXXXXXX';
				} else {
					$value          = str_replace( "enx1:", "", $value );
					$split          = explode( ":",
						$value ); // Split the String on : (Colon).  First part is the Envelope Key. Second Part is the Encrypted Data.
					$envelope_key   = base64_decode( $split[0] );
					$encrypted_data = base64_decode( $split[1] );
					openssl_open( $encrypted_data, $decrypted_data, $envelope_key, openssl_get_privatekey( trim( $options['private_key'] ) ) );

					$value = $decrypted_data;
				}
			} else if ( strpos( $value, "enx3:" ) !== false ) {
				$key   = "7%r?1C" . trim( $options['encryption_key'] ) . "jr-3";
				$value = base64_decode( str_replace( "enx3:", "", $value ) );
				$value = substr( $value, 3, - 3 );
				$value = base64_decode( str_replace( substr( base64_encode( $key ), 0, 6 ), '', $value ) );
			}
		}

		return $value;
	}

	public function new_field_value( $value, $parent_id, $group ) {

		$new_value = '';

		if ( empty( $value ) ) {
			return $value;
		}

		/*
		Multi-input fields such as Name and Address will be represented as an array,
		so each item needs to be encrypted individually
		*/

		if ( is_array( $value ) ) {
			foreach ( $value as $key => $input_value ) {
				$value[ $key ] = $this->encrypt( $input_value );
			}
			$new_value = $value;
		}
		$new_value = $this->encrypt( $value );

		$new_value = $this->save_to_remote( $new_value, $parent_id, $group );

		return $new_value;
	}

	public function gform_save_field_value( $value, $lead, $field, $form ) {
		return $this->new_field_value( $value, $lead['id'], "GravityForms" );
	}

	public function get_field_value( $value ) {

		if ( empty( $value ) ) {
			return $value;
		}

		if ( strpos( $value, 'remoteID:' ) !== false ) {
			$split = explode( ":", $value );
			$id    = $split[1];
			$value = $this->get_from_remote( $id );
		}

		/*
		Multi-input fields such as Name and Address will be represented as an array,
		so each item needs to be decrypted individually
		*/

		if ( is_array( $value ) ) {
			foreach ( $value as $key => $input_value ) {
				$value[ $key ] = $this->decrypt( $input_value );
			}

			return $value;
		}

		return $this->decrypt( $value );
	}

	public function gform_get_field_value( $value, $lead, $field, $input_id ) {
		global $wpdb;

		$options = unserialize( get_option( 'gds_encryption' ) );

		if ( $options['encription_type'] == 'asymmetric' && isset( $_POST[ 'input_' . $field['id'] ] ) && empty( $options['private_key'] ) ) {
			return $_POST[ 'input_' . $field['id'] ];
		}

		$detail = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM " . $wpdb->prefix . "rg_lead_detail WHERE `lead_id` = %d AND `field_number` = %d",
			$lead['id'], $field['id'] ) );

		if ( ! empty( $detail ) ) {
			$long = $wpdb->get_row( $wpdb->prepare( "SELECT value FROM " . $wpdb->prefix . "rg_lead_detail_long WHERE `lead_detail_id` = %d", $detail->id ) );
		}

		if ( ! empty( $long->value ) ) {
			$value = $long->value;
		}

		return $this->get_field_value( $value );
	}

	public function save_to_remote( $value, $lead_id, $group = "" ) {
		global $wpdb;

		$options = unserialize( get_option( 'gds_encryption' ) );

		if ( ! empty( $options['use_remote_storage'] ) ) {
			$link = GDS_Encryption_Class::get_database_link();

			if ( $link ) {
				$sql = "INSERT INTO " . mysqli_real_escape_string( $link, $options['remote_database_table'] ) . " (`" . mysqli_real_escape_string( $link,
						$options['remote_database_table_value'] ) . "`, `" . mysqli_real_escape_string( $link,
						$options['remote_database_table_parent_id'] ) . "`, `" . mysqli_real_escape_string( $link,
						$options['remote_database_table_group'] ) . "`) VALUES ( '" . mysqli_real_escape_string( $link,
						$value ) . "', '" . mysqli_real_escape_string( $link, $lead_id ) . "', '" . mysqli_real_escape_string( $link, $group ) . "' )";
				if ( mysqli_query( $link, $sql ) ) {
					return "remoteID:" . mysqli_insert_id( $link );
				}
			}
		}

		return $value;

	}

	public function get_from_remote( $id ) {
		global $wpdb;

		$options = unserialize( get_option( 'gds_encryption' ) );

		$link = GDS_Encryption_Class::get_database_link();

		if ( $link ) {
			$sql = "SELECT `" . mysqli_real_escape_string( $link, $options['remote_database_table_value'] ) . "` FROM " . mysqli_real_escape_string( $link,
					$options['remote_database_table'] ) . " WHERE `" . mysqli_real_escape_string( $link,
					$options['remote_database_table_id'] ) . "` = '" . mysqli_real_escape_string( $link, $id ) . "'";
			if ( $result = mysqli_query( $link, $sql ) ) {
				if ( $row = mysqli_fetch_assoc( $result ) ) {
					if ( isset( $row[ $options['remote_database_table_value'] ] ) ) {
						return $row[ $options['remote_database_table_value'] ];
					}
				}
			}
		}

		return $value;
	}

	public function delete_entry( $parent_id, $group = "" ) {
		global $wpdb;

		$options = unserialize( get_option( 'gds_encryption' ) );

		if ( ! empty( $options['remote_database_removal'] ) ) {
			$link = GDS_Encryption_Class::get_database_link();

			if ( $link ) {
				$sql = "DELETE FROM " . mysqli_real_escape_string( $link, $options['remote_database_table'] ) . " WHERE `" . mysqli_real_escape_string( $link,
						$options['remote_database_table_parent_id'] ) . "` = '" . mysqli_real_escape_string( $link,
						$parent_id ) . "' AND `" . mysqli_real_escape_string( $link,
						$options['remote_database_table_group'] ) . "` = '" . mysqli_real_escape_string( $link, $group ) . "'";
				mysqli_query( $link, $sql );
			}
		}
	}
}

function gds_encryption_custom_submenu_page()
{
	if ( class_exists( 'GDS_Class' ) ) {
		global $gds_class;

		if ( isset( $gds_class->included_plugins ) ) {
			$gds_class->included_plugins[] = [
				'GDS_Encryption_Class',
				'gravitate',
				'Encryption',
				'Encryption',
				'manage_options',
				'gds-encryption-custom-submenu-page',
				'gds_encryption_custom_submenu_page_callback'
			];
		}
	} else {
		add_submenu_page( 'options-general.php', 'Gravitate Encryption', 'Gravitate Encryption', 'manage_options', 'gds-encryption-custom-submenu-page',
			'gds_encryption_custom_submenu_page_callback' );
	}
}

$gds_encryption_class = new GDS_Encryption_Class();

$gds_encryption_class->init();

function gds_encryption_class_create_menu()
{
	global $gds_encryption_class;
	$gds_encryption_class->gds_create_menu();
}

function gds_encryption_custom_submenu_page_callback()
{
	global $gds_encryption_class;
	$gds_encryption_class->custom_submenu_page_callback();
}

if ( ! empty( $gds_encryption_enable_settings_page ) ) {
	add_action( 'admin_menu', 'gds_encryption_custom_submenu_page' );
}

function gds_encryption_gform_save_field_value( $value, $lead, $field, $form ) {
	global $gds_encryption_class;

	return $gds_encryption_class->gform_save_field_value( $value, $lead, $field, $form );
}

function gds_encryption_gform_get_field_value( $value, $lead, $field, $input_id ) {
	global $gds_encryption_class;

	return $gds_encryption_class->gform_get_field_value( $value, $lead, $field, $input_id );
}

function gds_encryption_gform_delete_lead( $lead_id ) {
	return GDS_Encryption_Class::delete_entry( $lead_id, "GravityForms" );
}

///////////////////////////////////////////////////////////////////////////////////////////////////
/* Since this Plugin is Dealing with Sensitive Data. Let make Updates Manual and not Automatic */
///////////////////////////////////////////////////////////////////////////////////////////////////

add_filter( 'http_request_args', 'gds_encryption_prevent_update_check', 10, 2 );
function gds_encryption_prevent_update_check( $r, $url ) {
	if ( 0 === strpos( $url, 'http://api.wordpress.org/plugins/update-check/' ) ) {
		$my_plugin = plugin_basename( __FILE__ );
		$plugins   = unserialize( $r['body']['plugins'] );
		unset( $plugins->plugins[ $my_plugin ] );
		unset( $plugins->active[ array_search( $my_plugin, $plugins->active ) ] );
		$r['body']['plugins'] = serialize( $plugins );
	}

	return $r;
}

add_filter( 'site_transient_update_plugins', 'gds_encryption_remove_update_nag' );
function gds_encryption_remove_update_nag( $value ) {
	if ( isset( $value->response[ plugin_basename( __FILE__ ) ] ) ) {
		unset( $value->response[ plugin_basename( __FILE__ ) ] );
	}

	return $value;
}

//////////////////////////////////////////////////////////////////////////////////////////////////
/*  End Update Uncheck Code */
//////////////////////////////////////////////////////////////////////////////////////////////////

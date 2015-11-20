=== Gravitate Encryption ===
Contributors: (Gravitate)
Tags: Gravitate, Encryption, Gravity Forms
Requires at least: 3.5
Tested up to: 3.6.1
Stable tag: trunk

Allow data stored by Gravity Forms and other Plugins to be Encrypted and sent to other databases.

== Description ==

Author: Gravitate http://www.gravitatedesign.com

Plugin Link: http://www.gravitatedesign.com/blog/wordpress-and-gravity-forms/

Description: This plugin allows the data stored by Gravity forms and other Plugins to be Encrypted and even sent to another database if needed. The Plugin allows for Symmetric and A-Semmetric Encryption.

==Gravity Forms Encryption==

Gravity Forms is an awesome plugin for WordPress.  However, it doesn’t come with an option to encrypt the data when it is being stored in the database.  While most of us will use gravity forms as a simple Contact Form, many of us might want to use it for much more than that. Gravity Forms is simple enough to use for a Contact Form, but if you wanted to use it as application tool that stores emails, phone numbers, even Social Security Numbers then you might want something that has a higher method of security then storing plain text in the database.

==Gravitate Encryption==

This is where the Gravitate Encryption Plugin comes in handy. This plugin allows you to use four methods of security with your client data.

==Symmetric Encryption==
This method uses PHP’s “mcrypt” library to encrypt and decrypt the data with a secret passphrase that you configure.  It will use a random IV with MCRYPT_RIJNDAEL_128 and MCRYPT_MODE_CBC.  There is an option to automatically create and email a Secret Key for you if you don’t know what the best option is.
    Your server will need to have “mcrypt” library installed and configured properly.

==A-Symmetric Encryption==
This method uses PHP’s “openssl” library to encrypt the date with a public_key and decrypts the data with a private_key.  This method uses a 2048bit RSA encrypted key to encrypt the full data.  This allows you to encrypt large amounts of data instead of the default RSA limited amount.  There is an option to automatically create and email a Public and Private Key for you if you don’t know how to do this yourself. The main usage of this type is that you can store the Private_Key in a separate location.  This way the Data can’t be accessed even in the Admin Panel unless you have the Private_Key.  Your server will need to have “openssl” library installed and configured properly.

==Weak Encryption==
Well this encryption method is just as it sounds.  The real only use for this is if your server doesn’t have “mcrypt” or “openssl” installed.  While this method is better than storing the data as plain text, it wouldn’t really hold against someone who know a thing or two about decrypting.  However, if it is your only option then it is there for you.
    * If you plan on storing very sensitive data, then we recommend working with your Web Server admin to get one of the other methods installed and working instead of using this method.

==Remote Database Storage==
This option allows for the data to be Stored on a Separate MySQL Database.  Useful if you want to keep the data behind your own Firewall.  This option can be used with any of the Three Encryption options above at the same time.  You will need to know how to configure a MySQL database as this option does require knowledge of a MySQL database configuration.

==WordPress Encryption==

While this plugin was intended for Gravity Forms, it can be used by any plugin or even in your WordPress theme files. First you will want to make sure that you have installed the Gravitate Encryption Plugin and configured it.  Next, make sure to Test the plugin.  There is an Encryption Test option at the bottom of the plugin.  If it is working properly then the Un-encrypted Text will show as the same as the Decrypted Text.

==Requirements==

- PHP 5.2 or above
- MySQL 5 or above
- mysqli extension
- WordPress 3.5 or above


==How to Use==

There are two ways to use this plugin:

1. Enable "Encrypt All Gravity Forms Data" in the Settings Page. (Settings -> Gravitate Encryption)
2. Use the PHP code below where you need to Encrypt or Decrypt your Data.

==To encrypt data use this PHP code:==

<pre>
if(class_exists('GDS_Encryption_Class'))
{
   echo GDS_Encryption_Class::encrypt('This is the Text to Encrypt');
}
</pre>

==To decrypt data use this PHP code:==

<pre>
if(class_exists('GDS_Encryption_Class'))
{
   echo GDS_Encryption_Class::decrypt('enx2:JKM3FFR4WP5HN6SG0C4ZAIF5K7H');
}
</pre>

NOTE: The above functions will also use the Remote Storage settings if they have been applied in the settings page. (Settings -> Gravitate Encryption)

 
* WARNING:
Once you start using the plugin and start storing the data as Encrypted, you should not change the settings of the plugin as it will no longer be able to Decrypt the data.  That means it will not be able to turn the Encrypted data back into a readable form.  Therefore it will make the data unusable.

You should only configure it once and then Disable the Plugin from being managed in the Admin Panel.

If you need to change the settings you will need a Web Administrator to backup your data Un-Encrypted then change the settings and Re-Populate the data with the New Encrypted Settings.

 

==Disable the Plugin from being managed in the Admin Panel==

This can only be done from within the code of the plugin.

You can update the code in two ways.  Either using FTP or you can edit the file from within the Plugin Edit page.

Go into the “gds_encryption.php” file and change:

<pre>$gds_encryption_enable_settings_page = true;</pre>

to

<pre>$gds_encryption_enable_settings_page = false;</pre>


== Installation ==

1. Upload the `gds_encryption` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. You can configure the Encryption Settings in `Settings` -> `Graviteate Encryption`


== Screenshots ==

1. This is the Main Settings Page
2. You can even setup a Remote Database to connect with in order to store your data.

== Changelog ==

= 1.0.1 =
* Resolved Warnings when updating other plugins

= 1.0.2 =
* Added wpdb->prepare to all Remote SQL Statements to help prevent SQL Injection.
* Added mysqli_real_escape_string to all fields including Table names and Field names to help prevent SQL Injection.
* Bug Fix - Checkmarks not saving when unchecked.

= 1.0.3 =
* Removed wpdb->prepare per WordPress Developers Request. Instead made sure all values including Table Names, Field Names, Etc are escaped using mysqli_real_escape_string.

= 1.0.4 =
* Added workaround for Gravity Forms returning short details on "gform_get_input_value" filter.  Should always be Long details if available.
* Added feature that will still show values if using A-Symmetric Encryption and Private Key is blank on Confirmation Messages for Gravity Forms.

<?php
/**
 * Plugin Name:       cPanel Widget
 * Plugin URI:        https://plugins.club/cpanel-widget/
 * Description:       Adds a widget to the admin dashboard that detects if the current website is hosted on cPanel and displays the user quota.
 * Version:           1.0
 * Author:            plugins.club
 * Author URI:        https://plugins.club
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 5.0
 * Tested up to: 	  6.2
*/

// Add the widget to the admin dashboard
add_action('wp_dashboard_setup', 'cpanel_widget_add_dashboard_widget');
function cpanel_widget_add_dashboard_widget() {
  wp_add_dashboard_widget('cpanel_widget', 'cPanel', 'cpanel_widget_display');
}

// Display the widget content
function cpanel_widget_display() {
  $username = get_option('cpanel_username');
  $password = get_option('cpanel_password');

  if (file_exists('/usr/local/cpanel/base/backend/env.cgi')) {
      //it is cpanel
    if (empty($username) || empty($password)) {
      // Show the login form
      echo '
        <form method="post">
          <label for="username">cPanel username:</label>
          <input type="text" id="username" name="cpanel_username" required>
          <br>
          <label for="password">cPanel password:</label>
          <input type="password" id="password" name="cpanel_password" required>
          <br>
          <input type="submit" name="submit" value="Save login information">
        </form>
      ';
    } else {
        // Make an API call to get the user quota
$hostname = gethostname();
$url = "https://$hostname:2083/cpsess1235467/execute/Quota/get_local_quota_info";
$auth = base64_encode("$username:$password");
$headers = array(
    "Authorization: Basic $auth",
    "Content-Type: application/json"
);
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
curl_close($ch);


// Display the user quota
$quota = json_decode($response, true);
if ($quota['status'] == 1) {
    $byte_limit = $quota['data']['byte_limit'] ?? '∞';
    $bytes_used = $quota['data']['bytes_used'];
    $size_units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes_used_formatted = @round($bytes_used/pow(1024, ($i=floor(log($bytes_used, 1024)))), 2).' '.$size_units[$i];
    $size_limit = array('B', 'KB', 'MB', 'GB', 'TB');
    
    $bytes_limit_formatted = @round($byte_limit/pow(1024, ($i=floor(log($byte_limit, 1024)))), 2).' '.$size_limit[$i];
    
    $inode_limit = $quota['data']['inode_limit'];
    $inodes_used = $quota['data']['inodes_used'];
    
    if ($byte_limit !== '∞') {
     echo "<p>Disk Usage: <b>$bytes_used_formatted</b> / <b>$bytes_limit_formatted </b></p>";   
    }
    else {
        
     echo "<p>Disk Usage: <b>$bytes_used_formatted</b> / <b>$byte_limit </b></p>";   
    }

if ($byte_limit !== '∞') {
  // Calculate the percentage of quota used
  $quota_percent = round(($bytes_used / $byte_limit) * 100);

  // Determine the color of the progress bar based on the percentage of quota used
  if ($quota_percent < 70) {
    $progress_color = 'green';
  } elseif ($quota_percent < 85) {
    $progress_color = 'orange';
  } else {
    $progress_color = 'red';
  }

  // Display the progress bar
  echo '<div style="width: 100%; background-color: #ddd; height: 10px; margin-top: 10px;">
        <div style="width: '.$quota_percent.'%; background-color: '.$progress_color.'; height: 10px;"></div>
        </div>';
}




    echo "<p>Inodes: <b>$inodes_used</b> / <b>$inode_limit</b></p>";
// Calculate the percentage of inodes quota used
$inode_percent = round(($inodes_used / $inode_limit) * 100);

// Determine the color of the progress bar based on the percentage of quota used
if ($inode_percent < 70) {
  $progress_color = 'green';
} elseif ($inode_percent < 85) {
  $progress_color = 'orange';
} else {
  $progress_color = 'red';
}

// Display the progress bar
echo '<div style="width: 100%; background-color: #ddd; height: 10px; margin-top: 10px;">
      <div style="width: '.$inode_percent.'%; background-color: '.$progress_color.'; height: 10px;"></div>
      </div>';
} else {
    echo "<p>Error: Failed to retrieve quota information.</p>";
}


echo '<div style="display: flex; justify-content: space-around; align-items: center;">';
echo '<div>';


// Make an API call to get the number of databases
      $url = "https://$hostname:2083/cpsess1235467/execute/Mysql/list_databases";
      $auth = base64_encode("$username:$password");
      $headers = array(
        "Authorization: Basic $auth",
        "Content-Type: application/json"
      );
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      $response = curl_exec($ch);
      curl_close($ch);

      // Display the number of databases
      $databases = json_decode($response, true);
      $num_databases = count($databases['data']);
      
      echo "<h2 style='text-align: center;'>$num_databases</h2>";
      echo "<p style='text-align: center; margin-top:0px;'>MySQL Databases:</p>";
echo '</div>';
echo '<div>';

// Make an API call to get the number of email accounts
      $url = "https://$hostname:2083/cpsess1235467/execute/Email/list_pops";
      $auth = base64_encode("$username:$password");
      $headers = array(
        "Authorization: Basic $auth",
        "Content-Type: application/json"
      );
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      $response = curl_exec($ch);
      curl_close($ch);

      // Display the number of databases
      $email = json_decode($response, true);
      $num_email = count($email['data']);
      echo "<h2 style='text-align: center;'>$num_email</h2>";
      echo "<p style='text-align: center; margin-top:0px;'>Email Accounts:</p>";
      
echo '</div>';
echo '</div>';

    }
  } else {
    echo '<p>Error: This website is not hosted on cPanel.</p>';
  }
}

// Save the login information when the form is submitted
add_action('admin_post_save_cpanel_login_info', 'cpanel_widget_save_login_info');


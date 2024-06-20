<?php
$user_ip = $_SERVER['REMOTE_ADDR'];
$id = $_GET['id'];

function get_data($id, $kid_data) {
    foreach ($kid_data as $item) {
        if ($item['id'] === $id) {
            return array('url' => $item['cmd'], 'kid' => $item['defaultkid'], 'pssh' => $item['pssh']);
        }
    }
    return null;
}

// Ensure paths are relative to the current directory
$file_path = __DIR__ . '/kid_data.txt';
$filename = __DIR__ . '/tplay-catchup-Mpd.json';

// Read the serialized array from the file and unserialize it
$serialized_data = file_get_contents($file_path);
if ($serialized_data === false) {
    die("Error reading kid_data.txt");
}
$kid_data = unserialize($serialized_data);

// Call the function to get 'kid' and 'pssh' corresponding to the provided 'id'
$result = get_data($id, $kid_data);
if ($result === null) {
    die("Invalid ID");
}

$default_kid = $result['kid'];

$file = file_get_contents($filename);
if ($file === false) {
    die("Error reading tplay-catchup-Mpd.json");
}

$data = json_decode($file, true);
if ($data === null) {
    die("Error decoding JSON");
}

if (!isset($data[$id])) {
    die("ID not found in JSON data");
}

$url = $data[$id];
$baseUrl = str_replace("/manifest.mpd", "/dash/", $url);
$url = str_replace("/manifest.mpd", "/dash/tsdevil.mpd", $url);

// Debugging output
error_log("URL: $url");

// Parse the URL
$parsedUrl = parse_url($url);
if ($parsedUrl === false) {
    die("Malformed URL");
}

// Get the host
$host = $parsedUrl['host'];

// Initialize Curl session
$h1 = [
    "X-Forwarded-For: 103.86.177.136",
    "Host: $host"
];

// Set Curl options
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url); // Set the URL to download
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the transfer as a string
curl_setopt($ch, CURLOPT_HEADER, 0); // Disable header output
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignore SSL verification
curl_setopt($ch, CURLOPT_HTTPHEADER, $h1);

// Execute Curl session
$response = curl_exec($ch);

// Check for errors
if(curl_errno($ch)){
    echo 'Curl error: ' . curl_error($ch);
    exit; // Exit script if there's a Curl error
}

// Close Curl session
curl_close($ch);

$new_content = "<ContentProtection value=\"cenc\" schemeIdUri=\"urn:mpeg:dash:mp4protection:2011\" cenc:default_KID=\"$default_kid\"/>";
$response = str_replace('<ContentProtection value="cenc" schemeIdUri="urn:mpeg:dash:mp4protection:2011"/>', $new_content, $response);
$response = str_replace('<BaseURL>dash/</BaseURL>', '<BaseURL>'. $baseUrl . '</BaseURL>', $response);

// Output the response (MPD content) as a file download
header('Content-Type: application/dash+xml');

echo $response;
?>

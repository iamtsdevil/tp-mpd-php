<?php
$user_ip = $_SERVER['REMOTE_ADDR'];
$id = $_GET['id'];
function get_data($id, $kid_data) {
    foreach ($kid_data as $item) {
        if ($item['id'] === $id) {
            return array('url' => $item['cmd'],'kid' => $item['defaultkid'], 'pssh' => $item['pssh']);
        }
    }
    // If 'id' is not found, return null
    return null;
}

// File path where the serialized array is stored
$file_path = 'kid_data.txt';

// Read the serialized array from the file and unserialize it
$serialized_data = file_get_contents($file_path);
$kid_data = unserialize($serialized_data);

// Call the function to get 'kid' and 'pssh' corresponding to the provided 'id'
$result = get_data($id, $kid_data);

$default_kid = $result['kid'];
//$add_pssh = $result['pssh'];

$filename = 'tplay-catchup-Mpd.json';

$file = file_get_contents($filename);

$data = json_decode($file, true);

$url = $data[$id];

// Create a DateTime object with the current time in New York
//$currentDateTime = new DateTime("now", new DateTimeZone("America/New_York"));

// Format the DateTime object to the desired format: YYYYMMDDTHHMMSS
//$currentDateTime->modify('+3 days');
//$currentNewYorkTimeFormatted = $currentDateTime->format('Ymd\THis');

//$currentDateTime->modify('-10 days');
//$past7dayNewYorkTimeFormatted = $currentDateTime->format('Ymd\THis');

//$hmac = 'begin=' . $past7dayNewYorkTimeFormatted .'&end=' . $currentNewYorkTimeFormatted;
$baseUrl = str_replace("/manifest.mpd", "/dash/", $url);
$url = str_replace("/manifest.mpd", "/dash/tsdevil.mpd", $url);

// Append query parameters to the URL
//$url .= '?' .$hmac;
// Parse the URL\
//echo $url;
$parsedUrl = parse_url($url);

// Get the host
$host = $parsedUrl['host'];

// Initialize Curl session
$h1 = [
    "X-Forwarded-For: $user_ip",
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

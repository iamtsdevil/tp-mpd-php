<?php
// Fetch JSON data from the endpoint
$jsonUrl = 'https://tplayapi.code-crafters.app/321codecrafters/fetcher.json';
$jsonData = file_get_contents($jsonUrl);

// Check if the data was fetched successfully
if ($jsonData === false) {
    die('Error fetching JSON data.');
}

// Decode the JSON response into a PHP array
$data = json_decode($jsonData, true);

// Function to fetch the 'kid' value and store it in an array
function fetch_kid($data) {
    $kid_array = array(); // Array to store id, kid, and pssh
    foreach ($data['data']['channels'] as $channel) {
        if (isset($channel['clearkeys'][0]['base64']['keys'])) {
            $id = $channel['id'];
            $cmd = $channel['manifest_url'];
            $pssh = $channel['clearkeys'][0]['pssh'];
            $psshBuffer = base64_decode($pssh);
            $bytes = substr($psshBuffer, 34, 16);
            $hex = bin2hex($bytes);
            $defaultKid = substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
            // Store id, kid, and pssh in an array
            $kid_array[] = array('id' => $id, 'cmd' => $cmd, 'pssh' => $pssh, 'defaultkid' => $defaultKid);
        }
    }
    return $kid_array;
}

// Call the function to fetch and store the 'id', 'kid', and 'pssh' in an array
$kid_array = fetch_kid($data);

// Serialize the array to store in a file
$serialized_array = serialize($kid_array);

// Write the serialized array to a file
$file_path = __DIR__ . '/kid_data.txt';
file_put_contents($file_path, $serialized_array);

echo "Data has been successfully stored in $file_path";
?>

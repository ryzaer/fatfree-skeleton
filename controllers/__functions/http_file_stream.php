<?php
// function http_file_stream($file_path) {
//   if (!file_exists($file_path))
//     throw new \Exception('The file did not exist');    
//   if (($file_size = filesize($file_path)) === false) 
//     throw new \Exception('Unable to get filesize.');    
//   // Attempt to Open file for (r) reading (b=binary safe)
//   if (($fp = @fopen($file_path, 'rb')) == false) 
//     throw new \Exception('Unable to open file.');    
  
//   // Define start and end of stream
//   $start = 0;
//   $end = $file_size - 1; // Minus 1 (Byte ranges are zero-indexed)
  
//   // -----------------------
//   // Handle "range" requests
//   // -----------------------
//   // A Range request is sent when a client requests a specific part of a file
//   // such as when using the video controls or when a download is resumed.
//   // We need to handle range requests in order to send back the requested part of a file.
  
//   // Determine if the "range" Request Header was set
//   if (isset($_SERVER['HTTP_RANGE'])) {
  
//     // Parse the range header
//     if (preg_match('|=([0-9]+)-([0-9]+)$|', $_SERVER['HTTP_RANGE'], $matches)) {
//       $start = $matches["1"];
//       $end = $matches["2"] - 1;
//     } elseif (preg_match('|=([0-9]+)-?$|', $_SERVER['HTTP_RANGE'], $matches)) {
//       $start = $matches["1"];
//     }
  
//     // Make sure we are not out of range
//     if (($start > $end) || ($start > $file_size) || ($end > $file_size) || ($end <= $start)) {
//       http_response_code(416);
//       exit();
//     }
  
//     // Position the file pointer at the requested range
//     fseek($fp, $start);
  
//     // Respond with 206 Partial Content
//     http_response_code(206);
  
//     // A "content-range" response header should only be sent if the "range" header was used in the request
//     $response_headers['content-range'] = 'bytes ' . $start . '-' . $end . '/' . $file_size;

//   } else {
//     // If the range header is not used, respond with a 200 code and start sending some content
//     http_response_code(200);
//   }
  
//   // Tell the client we support range-requests
//   $response_headers['accept-ranges'] = 'bytes';
//   // Set the content length to whatever remains
//   $response_headers['content-length'] = ($file_size - $start);
  
//   // ---------------------
//   // Send the file headers
//   // ---------------------
//   // Send the "last-modified" response header
//   // and compare with the "if-modified-since" request header (if present)
  
//   if (($timestamp = filemtime($file_path)) !== false) {
//     $response_headers['last-modified'] = gmdate("D, d M Y H:i:s", $timestamp) . ' GMT';
//     if ((isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) && ($_SERVER['HTTP_IF_MODIFIED_SINCE'] == $response_headers['last-modified'])) {
//       http_response_code(304); // Not Modified
//       exit();
//     }
//   }
  
//   // Set HTTP response headers
//   //$response_headers['content-disposition'] = " filename=" . basename($file_path);
//   $response_headers['content-type'] = mime_content_type($file_path);

//   foreach ($response_headers as $header => $value) {
//     header($header . ': ' . $value);
//   }
  
//   // ---------------------
//   // Start the file output
//   // ---------------------
//   $buffer = 1024 * 1024; // 1MB chunks for better performance
//   while (!feof($fp) && ($pointer = ftell($fp)) <= $end) {
  
//     // If next $buffer will pass $end,
//     // calculate remaining size
//     if ($pointer + $buffer > $end) {
//       $buffer = $end - $pointer + 1;
//     }
//     echo @fread($fp, $buffer);
//     flush();
//   }
//   fclose($fp);
//   exit();
// }


function http_file_stream($filePath,$mimeType=null) {
    
    // Check if file exists
    if (!file_exists($filePath)) {
        // Handle file not found error
        header("HTTP/1.1 404 Not Found");
        exit("File not found.");
    }

    // Open the file
    $file = fopen($filePath, 'rb');
    $fileSize = filesize($filePath);
    $fileName = basename($filePath);

    // Detect MIME type
    if(!$mimeType){
        $checkMime = mime_content_type($filePath);
        if ($checkMime){
            $mimeType = $checkMime;
        }else{
            $mimeType = "application/octet-stream";
        }
    }

    $chunkSize = 8192; // 8 byte chunks

    // Start streaming headers
    header("Content-Type: $mimeType");
    header("Content-Disposition: inline; filename=\"$fileName\"");
    header("Content-Length: $fileSize");
    header('Accept-Ranges: bytes');

    // Optional: Handle partial content requests (i.e., range requests)
    $start = 0;
    $range = null;
    if (isset($_SERVER['HTTP_RANGE'])) {
        $range = str_replace('bytes=', '', $_SERVER['HTTP_RANGE']);
        $range = explode('-', $range);
    }

    // If a range was requested, handle the partial content logic
    if ($range) {
        $start = intval($range[0]);
        $end = isset($range[1]) && $range[1] ? intval($range[1]) : $fileSize - 1;
        $length = $end - $start + 1;

        header("HTTP/1.1 206 Partial Content");
        header("Content-Range: bytes $start-$end/$fileSize");
        header("Content-Length: $length");

        $file = fopen($filePath, 'rb');
        fseek($file, $start);
    }

    // Stream the file in chunks
    while (!feof($file) && ($position = ftell($file)) <= $fileSize) {
        if ($range && $position >= $end)
            break;
        
        print fread($file, 8192);// 8 byte chunks
        ob_flush();
        flush(); 
        // Ensure each chunk is sent to the client
    }

    fclose($file);
    exit;
}

// Usage: Call the function with the path to the file you want to stream
// $filePath = 'path/to/your/file'; // Change this to your actual file path
// streamFile($filePath);

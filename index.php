<?php
ob_start();

	function ftp_directory_exists($ftp, $dir)
	{
	    // Get the current working directory
	    $origin = ftp_pwd($ftp);
	   
	    // Attempt to change directory, suppress errors
	    if (@ftp_chdir($ftp, $dir))
	    {
	        // If the directory exists, set back to origin
	        ftp_chdir($ftp, $origin);   
	        return true;
	    }

	    // Directory does not exist
	    return false;
	} 

    function in_arrayi($needle, $haystack) {
        return in_array(strtolower($needle), array_map('strtolower', $haystack));
    }

	function ftp_upload_directory($conn_id = null, $local_dir = null, $remote_dir = null) {
		$ftp_host		= '';
		$ftp_username	= '';
		$ftp_password	= '';

		$conn_id = ftp_connect($ftp_host);
		ftp_pasv($conn_id, true);
		ftp_login($conn_id, $ftp_username, $ftp_password);

		$files = scandir($local_dir);
		$files = array_slice($files, 2); // remove . and ..

		echo "change remote dir to $remote_dir<br/>\n";
		ftp_chdir($conn_id, $remote_dir);
		$files_remote = ftp_nlist($conn_id, null);
		ob_flush();
		flush();

		if ( count($files) ) {
			foreach ($files as $file) {
				// aggiungere gestione upload delle sottocartelle
				if ( is_dir($local_dir . $file) ) {
					if (!ftp_directory_exists($conn_id, $remote_dir . $file)) {
						echo "folder not found - make it: $local_dir$file\n<br>";
						ftp_mkdir($conn_id, $remote_dir . $file);
					}
				
					echo '<h1>ricorsiva</h1>';
					ob_flush();
					flush();
					ftp_upload_directory($conn_id, $local_dir . $file . '/', $remote_dir . $file . '/');
				} else {

					if (($local_size = filesize($local_dir.$file)) == ($remote_size = ftp_size($conn_id, $remote_dir.$file))) {
						$color = '#0f0';
					} else {
						$color = '#f00';
					}
					echo "<br/><span style=\"color:".$color."\">\nfilesize: $local_dir$file: " . ($local_size) . " | $remote_dir$file: " . ($remote_size) . "</span>";
					if (!in_arrayi($file, $files_remote)) {
						echo "no in array, change remote dir to $remote_dir<br/>\n";
						echo "<br/>\nfile not found - copy it: $local_dir$file\n<br/>";
						ob_flush();
						flush();
						ftp_chdir($conn_id, $remote_dir);
						$from = fopen($local_dir . $file, 'r');
						$result = ftp_fput($conn_id, $file, $from, FTP_BINARY);
						fclose($from);
						echo "file transfer result: $result";
						ob_flush();
						flush();
					}
				}
			}
		}
		ftp_close($conn_id);
	}

	if ($_GET['wat'] == 'images') {
		echo "<h1>sync images</h1>";
		$local_dir	= './images/';
		$remote_dir = '/www.foo.com/example/images/';
	}

	if (isset($_GET['wat'])) {
		echo "synching...\n<br/>";
		ob_flush();
		flush();
		ftp_upload_directory(null, $local_dir, $remote_dir);
	} else {
		echo "no wat, sync non effettuato. append ?wat=images or whatever\n<br/>";
	}

ob_end_flush();
ob_end_clean();
?>
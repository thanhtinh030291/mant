<?php

include_once 'config_inc.php';

define('SOURCE_PATH', '/home/habui/upload');
define('TARGET_PATH', '/home/attachmentMantisPCV');
define('USER_ID', 24);

error_reporting(0);
ini_set('display_errors', 1);
ini_set("log_errors", 1);
ini_set("error_log", DIRNAME(__FILE__) . '/upload.log');

class MfilesUpload
{
	public function __construct($host, $dbName, $user, $pass)
	{
		$files = array_diff(scandir(SOURCE_PATH), array('..', '.'));
		if (!$files || count($files) == 0)
		{
			return;
		}
		
		$objConnect = mysql_connect($host, $user, $pass) or die(mysql_error());  
		$objDB = mysql_select_db($dbName, $objConnect);
		
		foreach ($files as $file)
		{
			$fileInfo = $this->getFileInfo($file);
			
			if (strpos($file, '.filepart') !== false)
			{
				$this->error("Cannot insert file $file: Filepart!");
				continue;
			}
			
			if (!$fileInfo)
			{
				$this->error("Cannot insert file $file: Invalid file type!");
				unlink(SOURCE_PATH . "/$file");
				continue;
			}
			
			$customFields = $this->getCustomFields($fileInfo['name'], $file);
			if (!$customFields)
			{
				$this->error("Cannot insert file $file: Invalid file name!");
				unlink(SOURCE_PATH . "/$file");
				continue;
			}
			
			if (!$this->insertFile($file, $fileInfo, $customFields))
			{
				$this->error("Cannot insert file $file!");
				unlink(SOURCE_PATH . "/$file");
				continue;
			}
		}

		mysql_close($objConnect);
	}
	
	private function getFileInfo($file)
	{
		$fileParts = explode(".", $file);
		$extension = $fileParts[count($fileParts) - 1];
		
		$fileName = str_replace(".$extension", '', $file);
		$fileNameParts = explode(" (ID ", $fileName);
		$fileName = addslashes($fileNameParts[0]);
		$fileType = "";
		
		$fileTypes = array
		(
			'pdf'	=> 'application/pdf',
			'doc'	=> 'application/msword',
			'docx'	=> 'application/msword',
			'xls'	=> 'application/msexcel',
			'xlsx'	=> 'application/msexcel',
			'msg'	=> 'application/vnd.ms-outlook',
			'7z'	=> 'application/x-7z-compressed',
			'rar'	=> 'application/x-rar-compressed',
			'zip'	=> 'application/zip',
			'jpg'	=> 'image/jpeg',
			'tif'	=> 'image/tiff'
		);
		
		foreach ($fileTypes as $key => $value)
		{
			if (strcmp(strtolower($extension), $key) == 0)
			{
				$fileType = $value;
			}
		}
		if ($fileType == "")
		{
			return false;
		}
		
		return array
		(
			'name' => $fileName,
			'type' => $fileType,
			'fullname' => "$fileName.$extension"
		);
		
	}
	
	private function error($message)
	{
		error_log($message);
	}
	
	private function getCustomFields($fileName)
	{
		$pieces = explode("_", $fileName);
		$pieceCount = count($pieces);
		if ($pieceCount < 7)
		{
			return false;
		}
		
		$clientName = '';
		for ($i = 0; $i <= $pieceCount - 7; $i++)
		{
			$clientName .= '_' . $pieces[$i + 2];
		}
		$clientName = trim($clientName, '_');
		$clientNo = $pieces[$pieceCount - 4];
		
		$claimNo = $pieces[1];
		$claimNote = $pieces[$pieceCount - 1];
		$commonId = $pieces[$pieceCount - 2];
		$payee = $pieces[$pieceCount - 3];
		
			
		if (!$this->checkCommonId($pieces[5]))
		{
			return false;
		}
		
		return array
		(
			'9' => $claimNo,
			'11' => $clientName,
			'1' => $clientNo,
			'12' => $payee,
			'14' => $commonId,
			'10' => $claimNote,
			'2' => date("d/m/Y")
		);
	}

	private function checkCommonId($commonId)
	{
		$objQuery = mysql_query("SELECT value FROM mantis_custom_field_string_table WHERE field_id = 14 AND value = '$commonId'");  
		$objResult = mysql_fetch_array($objQuery);  
		if ($objResult)
		{
			$this->error('Error: DUPLICATED Common ID: ' . $commonId . '!');
			return false;
		}
		return true;
	}

	private function insertFile($file, $fileInfo, $customFields)
	{
		$bugTextId = $this->insertBugText($fileInfo['fullname']);
		if (!$bugTextId)
		{
			return false;
		}
		
		$bugId = $this->insertBug($bugTextId, $fileInfo['fullname']);
		if (!$bugId)
		{
			$this->deleteBugText($bugTextId);
			return false;
		}
		
		$bugFileId = $this->insertBugFile($bugId, $file, $fileInfo);
		if (!$bugFileId)
		{
			$this->deleteBug($bugId);
			$this->deleteBugText($bugTextId);
			return false;
		}
		
		if (!$this->insertCustomFields($bugId, $customFields))
		{
			$this->deleteCustomField($bugId);
			$this->deleteBugFile($bugFileId);
			$this->deleteBug($bugId);
			$this->deleteBugText($bugTextId);
			return false;
		}
		
		return true;
	}

	private function insertBugText($fileName, $loopCount = 0)
	{
		usleep(50);
		if ($loopCount == 10)
		{
			return false;
		}
		mysql_query("INSERT INTO mantis_bug_text_table(description) VALUES ('$fileName')");
		
		$objQuery = mysql_query("SELECT max(id) FROM mantis_bug_text_table WHERE description = '$fileName'");  
		$objResult = mysql_fetch_array($objQuery);  
		if($objResult)
		{
			return $objResult[0];
		}
		return $this->insertBugText($fileName, $loopCount + 1);
	}

	private function deleteBugText($bugTextId)
	{
		mysql_query("DELETE FROM mantis_bug_text_table WHERE id = $bugTextId");
	}

	private function insertBug($bugTextId, $fileName, $loopCount = 0)
	{
		usleep(50);
		if ($loopCount == 10)
		{
			return false;
		}
		
		$project_id = 3; 
		$reporter_id = 46;
		$handler_id = 46;
		$duplicate_id = 0;
		$priority = 30;
		$severity = 50;
		$reproducibility = 70;
		$status = 10;
		$resolution = 10;
		$projection = 10;
		$eta = 10;
		$bug_text_id = $bugTextId;
		$profile_id = 0;
		$view_state = 10;
		$summary = $fileName;
		$sponsorship_total = 0;
		$sticky = 0;
		$category_id = 14;
		$date_submitted = strtotime("now");
		$due_date = 1;
		$last_updated = strtotime("now");
		
		$sql = "INSERT INTO mantis_bug_table
				(
					project_id, reporter_id, handler_id, duplicate_id, priority, severity, reproducibility, status,resolution, projection, eta,
					bug_text_id, profile_id, view_state, summary, sponsorship_total, sticky, category_id, date_submitted, due_date, last_updated
				)
				VALUES
				(
					$project_id,$reporter_id,$handler_id,$duplicate_id,$priority,$severity,$reproducibility,$status,$resolution,$projection,$eta,
					$bug_text_id,$profile_id,$view_state,'$summary',$sponsorship_total,$sticky,$category_id,$date_submitted,$due_date,$last_updated
				)";
		
		mysql_query($sql);
		
		$strSQL = "SELECT id FROM mantis_bug_table WHERE bug_text_id = $bug_text_id AND summary = '$summary'"; 
		$objQuery = mysql_query($strSQL);  
		$objResult = mysql_fetch_array($objQuery);  
		if($objResult)
		{
			return $objResult[0];
		}
		return $this->insertBug($bugTextId, $fileName, $loopCount + 1);
	}

	private function deleteBug($bugId)
	{
		mysql_query("DELETE FROM mantis_bug_table WHERE id = $bugId");
	}

	private function insertBugFile($bugId, $file, $fileInfo, $loopCount = 0)
	{
		usleep(50);
		if ($loopCount == 10)
		{
			return false;
		}
		
		$userId = USER_ID;
		$prefix = $this->generateRandomString();
		$diskFile = $prefix . "_" . date("dmY") . "_" . $fileInfo['fullname'];
		$fullFileName = stripslashes(TARGET_PATH . "/$diskFile");
		$fileSize = filesize(SOURCE_PATH . "/$file");
		$viewState = 10;
		
		if (!rename(SOURCE_PATH . "/$file", $fullFileName))
		{
			return $this->insertBugFile($bugId, $file, $fileInfo, $loopCount + 1);
		}
		
		$sql = "INSERT INTO mantis_bug_file_table(bug_id, diskfile, filename, folder, filesize, file_type, date_added, view_state, user_id)
				VALUES($bugId,'$diskFile','" . $fileInfo['fullname'] . "','" . TARGET_PATH . "',$fileSize,'" . $fileInfo['type'] . "'," . strtotime("now") . ",$viewState,$userId)";
		mysql_query($sql);
		
		$strSQL = "SELECT id FROM mantis_bug_file_table WHERE diskfile = '$diskFile'"; 
		$objQuery = mysql_query($strSQL);  
		$objResult = mysql_fetch_array($objQuery);  
		if($objResult)
		{
			return $objResult[0];
		}
	}

	private function deleteBugFile($bugFileId)
	{
		mysql_query("DELETE FROM mantis_bug_file_table WHERE id = $bugFileId");
	}

	private function insertCustomFields($bugId, $customFields)
	{
		foreach ($customFields as $key => $value)
		{
			$ok = $this->insertCustomField($bugId, $key, $value);
			if (!$ok)
			{
				return false;
			}
		}
		return true;
	}

	private function insertCustomField($bugId, $key, $value, $loopCount = 0)
	{
		usleep(50);
		if ($loopCount == 10)
		{
			return false;
		}
		
		mysql_query("INSERT INTO mantis_custom_field_string_table(field_id,bug_id,value) VALUES ($key,$bugId,'$value')");
		$objQuery = mysql_query("SELECT value FROM mantis_custom_field_string_table WHERE field_id = $key AND bug_id = $bugId AND value = '$value'");  
		$objResult = mysql_fetch_array($objQuery);  
		if($objResult)
		{
			return true;
		}
		return $this->insertCustomField($bugId, $key, $value, $loopCount + 1);
	}

	private function deleteCustomFields($bugId)
	{
		mysql_query("DELETE FROM mantis_custom_field_string_table WHERE bug_id = $bugId");
	}

	private function generateRandomString()
	{
		$seed = str_split('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
		shuffle($seed);
		$randomString = '';
		foreach (array_rand($seed, 10) as $k) $randomString .= $seed[$k];
		return $randomString;
	}
}
new MfilesUpload("localhost", "mantis_call_log", "root", "@dmin##vdp");

?>
<?php

require_once 'core.php';

class MfilesUpload
{
    const RECEIVER = 'ngannguyen@pacificcross.com.vn';

    private $submitTime = "";
    private $fileName = "";
    private $fileNameAlt = "";
    private $fileType = "";
    private $targetPath = "";
    private $prefix = "";
    private $mfilesId = "";
    private $customFields = array();

    public function __construct()
    {
        if (!empty($_FILES))
        {
            $this->targetPath = '/data/attachment/mantis_pcv';

            $this->processFileName();

            $this->submitTime = strtotime("now");
            $this->prefix = $this->generateRandomString();

            $this->customFields = $this->getCustomFields();
            if ($this->checkCommonId())
            {
                move_uploaded_file($_FILES['file']['tmp_name'], "{$this->targetPath}/{$this->prefix}_" . date("dmY") . "_{$this->fileNameAlt}");
                $this->insertDatabase();
                mail(self::RECEIVER, "New Claim Uploaded", $this->fileNameAlt);
                error_log("New Claim Uploaded: {$this->fileNameAlt}");
                echo 'Upload successfully!';
            }
            else
            {
                echo 'Error: DUPLICATED Common ID!';
            }
        }
    }

    private function processFileName()
    {
        $fileParts = explode(".", $_FILES['file']['name']);
        $extension = $fileParts[count($fileParts) - 1];

        $fileName = str_replace('.' . $extension, '', $_FILES['file']['name']);
        $fileNameParts = explode(" (ID ", $fileName);

        $this->fileName = $_FILES['file']['name'];
        $this->fileNameAlt = $fileNameParts[0] . '.' . $extension;
        $this->mfilesId = str_replace(').', '.', $fileNameParts[count($fileNameParts) - 1]);

        $fileTypes = array
        (
            'pdf'    => 'application/pdf',
            'doc'    => 'application/msword',
            'docx'    => 'application/msword',
            'xls'    => 'application/msexcel',
            'xlsx'    => 'application/msexcel',
            'msg'    => 'application/vnd.ms-outlook',
            '7z'    => 'application/x-7z-compressed',
            'rar'    => 'application/x-rar-compressed',
            'zip'    => 'application/zip',
            'jpg'    => 'image/jpeg',
            'tif'    => 'image/tiff'
        );

        foreach ($fileTypes as $key => $value)
        {
            if (strcmp(strtolower($extension), $key) == 0)
            {
                $this->fileType = $value;
            }
        }
        if ($this->fileType == "")
        {
            $this->error("Invalid file type!");
        }
    }

    private function generateRandomString()
    {
        $seed = str_split('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
        shuffle($seed);
        $randomString = '';
        foreach (array_rand($seed, 10) as $k) $randomString .= $seed[$k];
        return $randomString;
    }

    private function error($message)
    {
        echo $message;
        exit;
    }

    private function getCustomFields()
    {
        $fileParts = explode(".", $this->fileNameAlt);
        $extension = $fileParts[count($fileParts) - 1];

        $pieces = explode("_", str_replace('.' . $extension, '', $this->fileNameAlt));
        $pieceCount = count($pieces);

        if ($pieceCount < 7)
        {
            $this->error("Invalid file name: " . json_encode($pieces) . "!");
        }
        else if ($pieceCount == 8)
        {
            return array
            (
                '9' => addslashes($pieces[1]),
                '11' => addslashes($pieces[2] . '_' . $pieces[3]),
                '1' => addslashes($pieces[4]),
                '12' => addslashes($pieces[5]),
                '14' => addslashes($pieces[6]),
                '10' => addslashes($pieces[7]),
                '2' => strtotime(date("Y-m-d"))
            );
        }
        else
        {
            return array
            (
                '9' => addslashes($pieces[1]),
                '11' => addslashes($pieces[2]),
                '1' => addslashes($pieces[3]),
                '12' => addslashes($pieces[4]),
                '14' => addslashes($pieces[5]),
                '10' => addslashes($pieces[6]),
                '2' => strtotime(date("Y-m-d"))
            );
        }
    }

    private function checkCommonId()
    {
        $sql = "SELECT `value`
                FROM `mantis_custom_field_string_table`
                WHERE `field_id` = 9
                  AND value = " . db_param();
        $objQuery = db_query($sql, [$this->customFields['14']]);
        $objResult = db_fetch_array($objQuery);
        if ($objResult)
        {
            return false;
        }
        return true;
    }

    private function insertDatabase()
    {
        $bugTextId = $this->insertBugText();
        $bugId = $this->insertBug($bugTextId);
        $this->insertCustomField($bugId);
        $this->insertBugFile($bugId);
    }

    private function insertBugText()
    {
        $sql = "INSERT INTO `mantis_bug_text_table`(`description`,`steps_to_reproduce`,`additional_information`)
                VALUES (" . db_param() . ",'','')";
        db_query($sql, [$this->fileNameAlt]);

        $objQuery = db_query("SELECT MAX(`id`) max_id FROM `mantis_bug_text_table`");
        $objResult = db_fetch_array($objQuery);
        if($objResult)
        {
            return $objResult['max_id'];
        }
        return null;
    }

    private function insertBug($bugTextId)
    {
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
        $summary = addslashes($this->fileNameAlt);
        $sponsorship_total = 0;
        $sticky = 0;
        $category_id = 14;
        $date_submitted = $this->submitTime;
        $due_date = 1;
        $last_updated = $this->submitTime;

        $sql = "INSERT INTO mantis_bug_table
                (
                    `project_id`, `reporter_id`, `handler_id`,
                    `duplicate_id`, `priority`, `severity`,
                    `reproducibility`, `status`, `resolution`,
                    `projection`, `eta`, `bug_text_id`,
                    `profile_id`, `view_state`, `summary`,
                    `sponsorship_total`, `sticky`, `category_id`,
                    `date_submitted`, `due_date`, `last_updated`
                )
                VALUES
                (
                    " . db_param() . ", " . db_param() . ", " . db_param() . ",
                    " . db_param() . ", " . db_param() . ", " . db_param() . ",
                    " . db_param() . ", " . db_param() . ", " . db_param() . ",
                    " . db_param() . ", " . db_param() . ", " . db_param() . ",
                    " . db_param() . ", " . db_param() . ", " . db_param() . ",
                    " . db_param() . ", " . db_param() . ", " . db_param() . ",
                    " . db_param() . ", " . db_param() . ", " . db_param() . "
                )";

        db_query($sql, [
            $project_id, $reporter_id, $handler_id,
            $duplicate_id, $priority, $severity,
            $reproducibility, $status, $resolution,
            $projection, $eta, $bug_text_id,
            $profile_id, $view_state, $summary,
            $sponsorship_total, $sticky, $category_id,
            $date_submitted, $due_date, $last_updated
        ]);

        $objQuery = db_query("SELECT MAX(`id`) max_id FROM `mantis_bug_table`");
        $objResult = db_fetch_array($objQuery);
        if($objResult)
        {
            return $objResult['max_id'];
        }
        return null;
    }

    private function insertCustomField($bugId)
    {
        foreach ($this->customFields as $key => $value)
        {
            $sql = "INSERT INTO `mantis_custom_field_string_table`(`field_id`,`bug_id`,`value`)
                    VALUES (" . db_param() . "," . db_param() . "," . db_param() . ")";
            db_query($sql, [$key, $bugId, $value]);
        }
    }

    private function insertBugFile($bugId)
    {
        $diskFile = "{$this->prefix}_" . date("dmY") . "_{$this->fileNameAlt}";
        $fullFileName = "{$this->targetPath}/{$diskFile}";
        $fileSize = filesize($fullFileName);
        $viewState = 10;

        $userId = 24;
        $sql = "INSERT INTO `mantis_bug_file_table`
                (
                    `bug_id`, `title`, `description`,
                    `diskfile`, `filename`, `folder`,
                    `filesize`, `file_type`, `content`,
                    `date_added`, `view_state`, `user_id`
                )
                VALUES
                (
                    " . db_param() . ",'','',
                    " . db_param() . "," . db_param() . "," . db_param() . ",
                    " . db_param() . "," . db_param() . ",'',
                    " . db_param() . "," . db_param() . "," . db_param() . "
                )";
        db_query($sql, [
            $bugId,
            $diskFile, $this->fileNameAlt, $this->targetPath,
            $fileSize, $this->fileType,
            $this->submitTime, $viewState, $userId
        ]);
    }
}
new MfilesUpload();

?>
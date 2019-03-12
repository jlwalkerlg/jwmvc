<?php

/**
 * FileUpload class.
 *
 * Validates a single file and uploads to permanent location.
 */
class FileUpload
{
    /** @var array $file File array, from form, to be uploaded. */
    private $file;

    /** @var string $name Name of file to upload, including extension. */
    private $name;

    /** @var string $destination Path to directory where file is to be saved. */
    private $destination;

    /** @var array $errors Array of errors regarding file upload. */
    private $errors = [];

    /** @var int $maxSize Maximum size allowed for uploaded file in bytes. */
    private $maxSize = 51200;

    /** @var array $permittedTypes Permitted mime types. */
	private $permittedTypes = [
        'image/jpeg',
        'image/pjpeg',
        'image/gif',
        'image/png',
        'image/svg+xml',
        'image/webp'
    ];

    /** @var array $permittedExtensions Permitted extensions. */
    private $permittedExtensions = [
        'jpg', 'jpeg', 'gif', 'png', 'svg', 'webp'
    ];

    /** @var bool
     *
     * Whether file should be renamed if a file of the same name already exists.
     * Existing file will be overwritten if this is set to false.
     */
	private $renameDuplicates = true;


    /**
     * Save file, file name, and destination to object instance.
     */
	public function __construct(array $file, string $destination)
	{
        // Ensure destination folder exists and is writable.
		if (!is_dir($destination) || !is_writable($destination)) {
			throw new Exception('Destination must be a valid, writable folder.');
        }
        // Append trailing slash to destination folder if it doesn't have one already.
		if ($destination[strlen($destination) - 1] !== '/') {
			$destination .= '/';
        }

        $this->file = $file;
        $this->name = $file['name'];
        $this->destination = $destination;
    }


    /**
     * Set upload options.
     *
     * @param array $options Array of options to set.
     */
    public function setOptions(array $options)
    {
        if (key_exists('name', $options)) {
            $this->setName($options['name']);
        }

        if (key_exists('maxSize', $options)) {
            $this->setMaxSize($options['maxSize']);
        }

        if (key_exists('renameDuplicates', $options)) {
            $this->renameDuplicates = $options['renameDuplicates'];
        }
    }


    /**
     * Set new name for uploaded file.
     *
     * @param string $name Name to save uploaded file as, excluding extension.
     */
    private function setName(string $name)
    {
        $extension = pathinfo($this->name, PATHINFO_EXTENSION);
        $this->name = $name . '.' . $extension;
    }


    /**
     * Override the default max file size.
     *
     * @param int $bytes Maximum bytes allowed for uploaded file.
     */
	private function setMaxSize(int $bytes)
	{
		$serverMax = self::convertToBytes(ini_get('upload_max_filesize'));
		if ($bytes > $serverMax) {
			throw new Exception('Maximum size cannot exceed server limit for individual files: ' . self::convertFromBytes($serverMax));
		}
		if (is_numeric($bytes) && $bytes > 0) {
			$this->maxSize = $bytes;
		}
	}


    /**
     * Check file is fit for upload and upload if so.
     *
     * @return bool True if file is successfully uploaded; false otherwise.
     */
	public function upload()
	{
        if ($this->checkFile()) {
            // Rename file before upload if necessary.
            $this->rename();
            if ($this->saveFile()) {
                return true;
            } else {
                $this->errors[] = 'Could not upload file.';
            }
        }
        return false;
	}


    /**
     * Retrieve error.
     *
     * @return array Array of errors.
     */
	public function getErrors()
	{
		return $this->errors;
	}


    /**
     * Perform various checks to see if the file is fit for upload.
     *
     * @return bool True if file is fit for upload; false otherwise.
     */
	private function checkFile()
	{
		if (!$this->checkError()) {
			return false;
		}
		if (!$this->checkSize()) {
			return false;
		}
        if (!$this->checkType()) {
            return false;
        }
        if (!$this->checkExtension()) {
            return false;
        }
		return true;
	}


    /**
     * Store error message in errors array.
     * Gets called only when the file has an error code which is not 0.
     *
     * @return bool True if uploaded file has no error; false otherwise.
     */
	private function checkError()
	{
        if ($this->file['error'] === 0) {
            return true;
        }

		switch($this->file['error']) {
			case 1:
			case 2:
                $this->errors[] = 'File is too big (max: ' . self::convertFromBytes($this->maxSize) . ').';
                break;
			case 3:
                $this->errors[] = 'File was only partially uploaded.';
                break;
			case 4:
                $this->errors[] = 'No file submitted.';
                break;
			default:
                $this->errors[] = 'Sorry, there was a problem uploading the file.';
        }
        return false;
	}


    /**
     * Check the file size is below max size.
     *
     * @return bool True if size is below max allowed size; false otherwise.
     */
	private function checkSize()
	{
		if ($this->file['size'] === 0) {
			$this->errors[] = 'File is empty.';
			return false;
		} elseif ($this->file['size'] > $this->maxSize) {
			$this->errors[] = 'File exceeds the maximum size (' . self::convertFromBytes($this->maxSize) . ').';
			return false;
		}
        return true;
	}


    /**
     * Check file mime type is in permitted types array.
     *
     * @return bool True if file mime type is in list of permitted types; false otherwise.
     */
	private function checkType()
	{
		if (!in_array($this->file['type'], $this->permittedTypes, true)) {
            $this->errors[] = 'File is not of a permitted type.';
			return false;
		}
        return true;
    }


    /**
     * Check file extension is in permitted extensions array.
     *
     * @return bool True if file extension is in list of permitted extensions; false otherwise.
     */
    private function checkExtension()
    {
        $extension = pathinfo($this->name, PATHINFO_EXTENSION);
        if (!in_array($extension, $this->permittedExtensions, true)) {
            $this->errors[] = 'File extension is not permitted.';
            return false;
        }
        return true;
    }


    /**
     * Rename the file if necessary.
     */
	private function rename()
	{
        // If the file name has spaces in it, replace them with underscores.
        if (strpos($this->name, ' ')) {
			$this->name = str_replace(' ', '_', $this->name);
        }

        // Only rename duplicates if desired.
		if ($this->renameDuplicates) {

            // Get an array of all files in destination directory.
            $existing = scandir($this->destination);

            // If the file name already exists in the destination directory...
			if (in_array($this->name, $existing, true)) {
                // Get different parts of the file name to be used in renaming.
                $nameparts = pathinfo($this->name);
                $filename = $nameparts['filename'];
                $extension = $nameparts['extension'];

                // Rename file with number until file name is unique.
				$i = 1;
				do {
					$this->name = $filename . '_' . $i++ . '.' . $extension;
				} while (in_array($this->name, $existing, true));
			}
		}
	}


    /**
     * Save file in destination directory.
     *
     * @return bool True if file was saved successfully; false otherwise.
     */
	private function saveFile()
	{
        return move_uploaded_file($this->file['tmp_name'], $this->destination . $this->name);
    }


    /**
     * Convert human readable representation of file size to bytes.
     * E.g. '50K' -> 51200
     *
     * @param string $val Bytes in human-readable format, e.g. '50K'
     * @return float Bytes converted to number of bytes, e.g. 51200.
     */
	public static function convertToBytes(string $val)
	{
        // Trim whitespace.
        $val = trim($val);
        // Get unit, e.g. K for kilobytes.
        $unit = strtoupper($val[strlen($val) - 1]);
        // Convert string to float, which will throw away the unit.
        $val = (float) $val;
        // Multiply value by appropriate multiplier(s).
        switch ($unit) {
            case 'G':
                $val *= 1024;
            case 'M':
                $val *= 1024;
            case 'K':
                $val *= 1024;
        }
		return $val;
	}


    /**
     * Convert bytes to human readable format.
     * E.g. 51200 -> '50K'
     *
     * @param float $bytes Bytes to convert to human-readable format, e.g. 51200
     * @return string Bytes in human-readable format, e.g. '50K'
     */
	public static function convertFromBytes($bytes)
	{
        $bytes /= 1024;
        if ($bytes > 1024**2) {
            return number_format($bytes / 1024**2, 1) . ' GB';
        } elseif ($bytes > 1024) {
			return number_format($bytes / 1024, 1) . ' MB';
		}
        return number_format($bytes, 1) . ' KB';
	}
}

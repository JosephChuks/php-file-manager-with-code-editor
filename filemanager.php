<?php

/**
 * The Kinsmen File Manager v2.7
 *
 * A comprehensive, modern file manager with cPanel styling and all essential features:
 * - File Tree Navigation
 * - Search functionality
 * - Create/Edit/Delete files and folders
 * - Upload/Download files
 * - Copy/Move operations
 * - Compression (zip, tar, gzip)
 * - Extraction (zip, tar, gzip)
 * - Deletion confirmations
 * - Rename operations
 * - Permission management
 * - Drag and drop support
 * - Multi-select operations
 * - Sorting and filtering
 */

$username = "joe";
$root_path = $_SERVER['DOCUMENT_ROOT'];

// Configuration
$config = [
    "root_path" => $root_path,
    "allowed_extensions" => ["*"],
    "timezone" => "Africa/Lagos",
    "date_format" => "M j Y, g:i A",
    "font_size" => "16px",
];

if (file_exists("$root_path/.fm-config")) {
    $userConfig = "$root_path/.fm-config";
    $settings = json_decode(file_get_contents($userConfig), true);

    $config['timezone'] = $settings['timezone'] ?? $config['timezone'];
    $config['date_format'] = $settings['date_format'] ?? $config['date_format'];
    $config['font_size'] = $settings['font_size'] ?? $config['font_size'];
}

// Set timezone
date_default_timezone_set($config["timezone"]);

// Security check function
function securityCheck($path)
{
    global $config;
    $realPath = realpath($path);
    if (!$realPath) {
        return $config["root_path"];
    }
    return strpos($realPath, $config["root_path"]) === 0;
}

// Helper function to format file size
function formatSize($bytes)
{
    $units = ["bytes", "KB", "MB", "GB", "TB"];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . " " . $units[$i];
}

// Helper function to get file icon based on extension
function getFileIcon($file)
{
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    $iconMap = [
        "pdf"   => "<i class='fas fa-file-pdf' style='color:#D9534F'></i>",
        "doc"   => "<i class='fas fa-file-word' style='color:#2B579A'></i>",
        "docx"  => "<i class='fas fa-file-word' style='color:#2B579A'></i>",
        "xls"   => "<i class='fas fa-file-excel' style='color:#217346'></i>",
        "xlsx"  => "<i class='fas fa-file-excel' style='color:#217346'></i>",
        "ppt"   => "<i class='fas fa-file-powerpoint' style='color:#D24726'></i>",
        "pptx"  => "<i class='fas fa-file-powerpoint' style='color:#D24726'></i>",
        "jpg"   => "<i class='fas fa-file-image' style='color:#F4A261'></i>",
        "jpeg"  => "<i class='fas fa-file-image' style='color:#F4A261'></i>",
        "png"   => "<i class='fas fa-file-image' style='color:#F4A261'></i>",
        "gif"   => "<i class='fas fa-file-image' style='color:#F4A261'></i>",
        "txt"   => "<i class='fas fa-file-alt' style='color:#6C757D'></i>",
        "zip"   => "<i class='fas fa-file-archive' style='color:#FF9F1C'></i>",
        "tar"   => "<i class='fas fa-file-archive' style='color:#FF9F1C'></i>",
        "gz"    => "<i class='fas fa-file-archive' style='color:#FF9F1C'></i>",
        "html"  => "<i class='fas fa-file-code' style='color:#E34C26'></i>",
        "htm"   => "<i class='fas fa-file-code' style='color:#E34C26'></i>",
        "css"   => "<i class='fas fa-file-code' style='color:#264DE4'></i>",
        "js"    => "<i class='fas fa-file-code' style='color:#F0DB4F'></i>",
        "php"   => "<i class='fas fa-file-code' style='color:#8892BF'></i>",
        "py"    => "<i class='fas fa-file-code' style='color:#306998'></i>",
        "java"  => "<i class='fas fa-file-code' style='color:#B07219'></i>",
        "c"     => "<i class='fas fa-file-code' style='color:#555555'></i>",
        "cpp"   => "<i class='fas fa-file-code' style='color:#00599C'></i>",
        "mp3"   => "<i class='fas fa-file-audio' style='color:#6F42C1'></i>",
        "mp4"   => "<i class='fas fa-file-video' style='color:#20C997'></i>",
        "mov"   => "<i class='fas fa-file-video' style='color:#20C997'></i>",
        "avi"   => "<i class='fas fa-file-video' style='color:#20C997'></i>",
    ];


    if (is_dir($file)) {
        return "<i class='fas fa-folder folder-icon'></i>";
    } elseif (isset($iconMap[$extension])) {
        return $iconMap[$extension];
    } else {
        return "<i class='fas fa-file' style='color:#6C757D'></i>";
    }
}

// Function to get file permissions as string
function getPermissions($file)
{
    $perms = fileperms($file);

    if (($perms & 0xc000) == 0xc000) {
        // Socket
        $info = "s";
    } elseif (($perms & 0xa000) == 0xa000) {
        // Symbolic Link
        $info = "l";
    } elseif (($perms & 0x8000) == 0x8000) {
        // Regular
        $info = "-";
    } elseif (($perms & 0x6000) == 0x6000) {
        // Block special
        $info = "b";
    } elseif (($perms & 0x4000) == 0x4000) {
        // Directory
        $info = "d";
    } elseif (($perms & 0x2000) == 0x2000) {
        // Character special
        $info = "c";
    } elseif (($perms & 0x1000) == 0x1000) {
        // FIFO pipe
        $info = "p";
    } else {
        // Unknown
        $info = "u";
    }

    // Owner
    $info .= $perms & 0x0100 ? "r" : "-";
    $info .= $perms & 0x0080 ? "w" : "-";
    $info .=
        $perms & 0x0040
        ? ($perms & 0x0800
            ? "s"
            : "x")
        : ($perms & 0x0800
            ? "S"
            : "-");

    // Group
    $info .= $perms & 0x0020 ? "r" : "-";
    $info .= $perms & 0x0010 ? "w" : "-";
    $info .=
        $perms & 0x0008
        ? ($perms & 0x0400
            ? "s"
            : "x")
        : ($perms & 0x0400
            ? "S"
            : "-");

    // World
    $info .= $perms & 0x0004 ? "r" : "-";
    $info .= $perms & 0x0002 ? "w" : "-";
    $info .=
        $perms & 0x0001
        ? ($perms & 0x0200
            ? "t"
            : "x")
        : ($perms & 0x0200
            ? "T"
            : "-");

    return $info;
}

// Function to build directory tree
function buildDirectoryTree($dir, $relativePath = "")
{
    global $config;

    $result = [];
    $cdir = scandir($dir);

    foreach ($cdir as $key => $value) {
        if (!in_array($value, [".", ".."])) {
            $fullPath = $dir . DIRECTORY_SEPARATOR . $value;

            // Make sure relativePath does not start with a slash
            $relPathPrefix = $relativePath
                ? ltrim($relativePath, "/") . "/"
                : "";
            $relPath = $relPathPrefix . $value;

            if (is_dir($fullPath)) {
                // Ensure security check passes
                if (securityCheck($fullPath)) {
                    $result[] = [
                        "name" => $value,
                        "type" => "dir",
                        "path" => "/" . ltrim($relPath, "/"), // Ensure consistent format
                        "children" => buildDirectoryTree($fullPath, $relPath),
                    ];
                }
            }
        }
    }

    return $result;
}

// Function to get directory contents
function getDirectoryContents($dir, $sort = "name", $order = "asc")
{
    $result = [];
    $cdir = scandir($dir);

    foreach ($cdir as $key => $value) {
        if (!in_array($value, [".", ".."])) {
            $fullPath = $dir . DIRECTORY_SEPARATOR . $value;

            $fileInfo = [
                "name" => $value,
                "type" => is_dir($fullPath) ? "dir" : "file",
                "size" => is_dir($fullPath)
                    ? ""
                    : formatSize(filesize($fullPath)),
                "size_raw" => is_dir($fullPath) ? 0 : filesize($fullPath),
                "extension" => is_dir($fullPath)
                    ? ""
                    : pathinfo($value, PATHINFO_EXTENSION),
                "date_added" => date(
                    $GLOBALS["config"]["date_format"],
                    filectime($fullPath)
                ),
                "last_modified" => date(
                    $GLOBALS["config"]["date_format"],
                    filemtime($fullPath)
                ),
                "permissions" => getPermissions($fullPath),
                "icon" => getFileIcon($fullPath),
            ];

            $result[] = $fileInfo;
        }
    }

    // Sort results
    usort($result, function ($a, $b) use ($sort, $order) {
        // Directories always come first
        if ($a["type"] != $b["type"]) {
            return $a["type"] == "dir" ? -1 : 1;
        }

        // Then sort by the specified field
        $valA = $a[$sort];
        $valB = $b[$sort];

        if ($sort == "size") {
            $valA = $a["size_raw"];
            $valB = $b["size_raw"];
        }

        if ($order == "asc") {
            return $valA <=> $valB;
        } else {
            return $valB <=> $valA;
        }
    });

    return $result;
}

// Include all the other functions from filemanager.php
function createDirectory($path, $name)
{
    $dirPath = $path . DIRECTORY_SEPARATOR . $name;

    if (!file_exists($dirPath)) {
        if (mkdir($dirPath, 0755)) {
            return [
                "status" => "success",
                "message" => "Directory created successfully",
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Failed to create directory",
            ];
        }
    } else {
        return ["status" => "error", "message" => "Directory already exists"];
    }
}

function createFile($path, $name, $content = "")
{
    $filePath = $path . DIRECTORY_SEPARATOR . $name;

    if (!file_exists($filePath)) {
        if (file_put_contents($filePath, $content) !== false) {
            return [
                "status" => "success",
                "message" => "File created successfully",
            ];
        } else {
            return ["status" => "error", "message" => "Failed to create file"];
        }
    } else {
        return ["status" => "error", "message" => "File already exists"];
    }
}

function updateSettings($timezone, $dateFormat, $fontSize)
{
    global $config;

    if (empty($timezone) && empty($dateFormat) && empty($fontSize)) {
        return ["status" => "error", "message" => "No changes made"];
    }

    $data = [
        'timezone' => empty($timezone) ? $config["timezone"] : $timezone,
        'date_format' => empty($dateFormat) ? $config["date_format"] : $dateFormat,
        'font_size' => empty($fontSize) ? $config["font_size"] : $fontSize,
    ];

    $file = $config["root_path"] . "/.fm-config";

    if (file_put_contents($file, json_encode($data))) {
        return [
            "status" => "success",
            "message" => "Settings updated successfully",
        ];
    } else {
        return ["status" => "error", "message" => "Failed to update settings"];
    }
}

function renameItem($path, $oldName, $newName)
{
    $oldPath = $path . DIRECTORY_SEPARATOR . $oldName;
    $newPath = $path . DIRECTORY_SEPARATOR . $newName;

    if (file_exists($oldPath) && !file_exists($newPath)) {
        if (rename($oldPath, $newPath)) {
            return [
                "status" => "success",
                "message" => "Item renamed successfully",
            ];
        } else {
            return ["status" => "error", "message" => "Failed to rename item"];
        }
    } else {
        return [
            "status" => "error",
            "message" => "Source does not exist or destination already exists",
        ];
    }
}

function deleteItem($path)
{
    if (is_dir($path)) {
        $files = array_diff(scandir($path), [".", ".."]);

        foreach ($files as $file) {
            deleteItem($path . DIRECTORY_SEPARATOR . $file);
        }

        if (rmdir($path)) {
            return true;
        } else {
            return false;
        }
    } else {
        if (unlink($path)) {
            return true;
        } else {
            return false;
        }
    }
}

function copyItem($source, $destination)
{
    if (is_dir($source)) {
        if (!file_exists($destination)) {
            mkdir($destination, 0755);
        }

        $files = array_diff(scandir($source), [".", ".."]);

        foreach ($files as $file) {
            copyItem(
                $source . DIRECTORY_SEPARATOR . $file,
                $destination . DIRECTORY_SEPARATOR . $file
            );
        }

        return [
            "status" => "success",
            "message" => "Directory copied successfully",
        ];
    } else {
        if (copy($source, $destination)) {
            return [
                "status" => "success",
                "message" => "File copied successfully",
            ];
        } else {
            return ["status" => "error", "message" => "Failed to copy file"];
        }
    }
}

function changePermissions($path, $mode)
{
    if (chmod($path, octdec($mode))) {
        return [
            "status" => "success",
            "message" => "Permissions changed successfully",
        ];
    } else {
        return [
            "status" => "error",
            "message" => "Failed to change permissions",
        ];
    }
}

function compressItems($items, $destination, $type = "zip")
{

    global $config;

    if (count($items) < 1) {
        return [
            "status" => "error",
            "message" => "No selected items to compress",
        ];
    }

    switch ($type) {
        case "empty_trash":
            $trashDir = $config["root_path"] . "/.trash";
            if (!is_dir($trashDir)) {
                $response = ["status" => "error", "message" => "Trash directory not found"];
                break;
            }

            $items = scandir($trashDir);
            $deleted = 0;
            foreach ($items as $item) {
                if ($item !== "." && $item !== "..") {
                    $path = $trashDir . "/" . $item;
                    if (is_file($path) || is_link($path)) {
                        if (@unlink($path)) $deleted++;
                    } elseif (is_dir($path)) {
                        $cmd = "rm -rf " . escapeshellarg($path);
                        @exec($cmd, $out, $code);
                        if ($code === 0) $deleted++;
                    }
                }
            }

            $response = [
                "status" => "success",
                "message" => "Trash emptied ($deleted item(s) deleted)"
            ];
            break;
        case "zip":
            $zip = new ZipArchive();

            if ($zip->open($destination, ZipArchive::CREATE) === true) {
                foreach ($items as $item) {
                    if (is_dir($item)) {
                        $files = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($item),
                            RecursiveIteratorIterator::LEAVES_ONLY
                        );

                        foreach ($files as $file) {
                            if (!$file->isDir()) {
                                $filePath = $file->getRealPath();
                                $relativePath = substr(
                                    $filePath,
                                    strlen(dirname($item)) + 1
                                );

                                $zip->addFile($filePath, $relativePath);
                            }
                        }
                    } else {
                        $zip->addFile($item, basename($item));
                    }
                }

                $zip->close();
                return [
                    "status" => "success",
                    "message" => "Files compressed successfully (ZIP)",
                ];
            } else {
                return [
                    "status" => "error",
                    "message" => "Failed to create ZIP archive",
                ];
            }
            break;

        case "tar":
            $phar = new PharData($destination);

            foreach ($items as $item) {
                $phar->addFile($item, basename($item));
            }

            return [
                "status" => "success",
                "message" => "Files compressed successfully (TAR)",
            ];
            break;

        case "gzip":
            if (count($items) > 1) {
                return [
                    "status" => "error",
                    "message" => "GZip can only compress one file at a time",
                ];
            }

            $content = file_get_contents($items[0]);
            $gzContent = gzencode($content, 9);
            file_put_contents($destination, $gzContent);

            return [
                "status" => "success",
                "message" => "File compressed successfully (GZip)",
            ];
            break;

        default:
            return [
                "status" => "error",
                "message" => "Unknown compression type",
            ];
    }
}

// Function to move item to trash
function moveToTrash($source, $currentPath, $config)
{
    // Create trash directory if it doesn't exist
    $trashDir = $config["root_path"] . "/.trash";
    if (!file_exists($trashDir)) {
        mkdir($trashDir, 0755, true);
    }

    // Get the item name from the source path
    $itemName = basename($source);

    // Create a unique name if a file with the same name already exists in trash
    $destinationPath = $trashDir . "/" . $itemName;
    $counter = 1;

    $originalName = pathinfo($itemName, PATHINFO_FILENAME);
    $extension = pathinfo($itemName, PATHINFO_EXTENSION);

    while (file_exists($destinationPath)) {
        if ($extension) {
            $destinationPath =
                $trashDir .
                "/" .
                $originalName .
                " (" .
                $counter .
                ")." .
                $extension;
        } else {
            $destinationPath =
                $trashDir . "/" . $originalName . " (" . $counter . ")";
        }
        $counter++;
    }

    // Move the item to trash
    if (rename($source, $destinationPath)) {
        // Create metadata file to store original location
        $metaFilename = $destinationPath . ".trashinfo";
        $metadata = [
            "original_path" =>
            str_replace($config["root_path"], "", $currentPath) .
                "/" .
                $itemName,
            "deletion_date" => date("Y-m-d H:i:s"),
            "original_name" => $itemName,
        ];

        file_put_contents(
            $metaFilename,
            json_encode($metadata, JSON_PRETTY_PRINT)
        );

        return true;
    }

    return false;
}

// Function to restore an item from trash
function restoreFromTrash($source, $config)
{
    // Check if metadata file exists
    $metaFilename = $source . ".trashinfo";
    if (!file_exists($metaFilename)) {
        return ["status" => "error", "message" => "Trash metadata not found"];
    }

    // Read metadata
    $metadata = json_decode(file_get_contents($metaFilename), true);
    if (!$metadata || !isset($metadata["original_path"])) {
        return ["status" => "error", "message" => "Invalid trash metadata"];
    }

    // Construct destination path
    $destPath = $config["root_path"] . $metadata["original_path"];
    $destDir = dirname($destPath);

    // Create destination directory if it doesn't exist
    if (!file_exists($destDir)) {
        if (!mkdir($destDir, 0755, true)) {
            return [
                "status" => "error",
                "message" => "Failed to create destination directory",
            ];
        }
    }

    // Check if destination already exists
    if (file_exists($destPath)) {
        // Create a unique name if a file with the same name already exists
        $originalName = pathinfo($destPath, PATHINFO_FILENAME);
        $extension = pathinfo($destPath, PATHINFO_EXTENSION);
        $counter = 1;

        $newDestPath = $destPath;
        while (file_exists($newDestPath)) {
            if ($extension) {
                $newDestPath =
                    $destDir .
                    "/" .
                    $originalName .
                    " (restored " .
                    $counter .
                    ")." .
                    $extension;
            } else {
                $newDestPath =
                    $destDir .
                    "/" .
                    $originalName .
                    " (restored " .
                    $counter .
                    ")";
            }
            $counter++;
        }

        $destPath = $newDestPath;
    }

    // Move the item back to original location
    if (rename($source, $destPath)) {
        // Delete metadata file
        if (file_exists($metaFilename)) {
            unlink($metaFilename);
        }
        return [
            "status" => "success",
            "message" => "Item restored successfully",
        ];
    }

    return ["status" => "error", "message" => "Failed to restore item"];
}

// Function to extract archives
function extractArchive($source, $destination, $config)
{
    // Make sure destination exists
    if (!file_exists($destination)) {
        if (!mkdir($destination, 0755, true)) {
            return [
                "status" => "error",
                "message" => "Failed to create destination directory",
            ];
        }
    }

    // Get file extension to determine archive type
    $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));

    // Handle different archive types
    switch ($extension) {
        case "zip":
            return extractZip($source, $destination);

        case "tar":
            return extractTar($source, $destination);

        case "gz":
        case "gzip":
            return extractGzip($source, $destination);

        case "bz2":
        case "bzip2":
            return extractBzip2($source, $destination);

        case "rar":
            return extractRar($source, $destination);

        case "7z":
            return extract7Zip($source, $destination);

        default:
            return [
                "status" => "error",
                "message" => "Unsupported archive type: " . $extension,
            ];
    }
}

// Extract ZIP archive
function extractZip($source, $destination)
{
    $zip = new ZipArchive();
    $res = $zip->open($source);

    if ($res === true) {
        $zip->extractTo($destination);
        $zip->close();
        return [
            "status" => "success",
            "message" => "ZIP archive extracted successfully",
        ];
    } else {
        return [
            "status" => "error",
            "message" =>
            "Failed to open ZIP archive (Error code: " . $res . ")",
        ];
    }
}

// Extract TAR archive
function extractTar($source, $destination)
{
    try {
        $phar = new PharData($source);
        $phar->extractTo($destination, null, true); // Extract all files, overwrite
        return [
            "status" => "success",
            "message" => "TAR archive extracted successfully",
        ];
    } catch (Exception $e) {
        return [
            "status" => "error",
            "message" => "Failed to extract TAR archive: " . $e->getMessage(),
        ];
    }
}

// Extract GZIP archive
function extractGzip($source, $destination)
{
    // GZIP usually contains a single file
    $basename = basename($source, ".gz");
    if (substr($basename, -4) === ".tar") {
        // Handle .tar.gz files
        try {
            $phar = new PharData($source);
            $phar->extractTo($destination, null, true);
            return [
                "status" => "success",
                "message" => "TAR.GZ archive extracted successfully",
            ];
        } catch (Exception $e) {
            return [
                "status" => "error",
                "message" =>
                "Failed to extract TAR.GZ archive: " . $e->getMessage(),
            ];
        }
    } else {
        // Regular .gz file (single file)
        $destFile = $destination . DIRECTORY_SEPARATOR . $basename;

        $sfp = gzopen($source, "rb");
        $fp = fopen($destFile, "wb");

        if (!$sfp || !$fp) {
            return [
                "status" => "error",
                "message" => "Failed to open files for extraction",
            ];
        }

        while (!gzeof($sfp)) {
            fwrite($fp, gzread($sfp, 4096));
        }

        gzclose($sfp);
        fclose($fp);

        return [
            "status" => "success",
            "message" => "GZIP file extracted successfully",
        ];
    }
}

// Extract BZIP2 archive
function extractBzip2($source, $destination)
{
    // BZIP2 usually contains a single file
    $basename = basename($source, ".bz2");
    if (substr($basename, -4) === ".tar") {
        // Handle .tar.bz2 files
        try {
            $phar = new PharData($source);
            $phar->extractTo($destination, null, true);
            return [
                "status" => "success",
                "message" => "TAR.BZ2 archive extracted successfully",
            ];
        } catch (Exception $e) {
            return [
                "status" => "error",
                "message" =>
                "Failed to extract TAR.BZ2 archive: " . $e->getMessage(),
            ];
        }
    } else {
        // Regular .bz2 file (single file)
        $destFile = $destination . DIRECTORY_SEPARATOR . $basename;

        $sfp = bzopen($source, "r");
        $fp = fopen($destFile, "w");

        if (!$sfp || !$fp) {
            return [
                "status" => "error",
                "message" => "Failed to open files for extraction",
            ];
        }

        while (!feof($sfp)) {
            fwrite($fp, bzread($sfp, 4096));
        }

        bzclose($sfp);
        fclose($fp);

        return [
            "status" => "success",
            "message" => "BZIP2 file extracted successfully",
        ];
    }
}

// Extract RAR archive (requires rar extension or unrar command)
function extractRar($source, $destination)
{
    // Try using RarArchive class if available
    if (class_exists("RarArchive")) {
        $rar = RarArchive::open($source);
        if ($rar === false) {
            return [
                "status" => "error",
                "message" => "Failed to open RAR archive",
            ];
        }

        $entries = $rar->getEntries();
        foreach ($entries as $entry) {
            $entry->extract($destination);
        }

        $rar->close();
        return [
            "status" => "success",
            "message" => "RAR archive extracted successfully",
        ];
    }
    // Try using unrar command
    elseif (function_exists("exec")) {
        $command =
            "unrar x -o+ " .
            escapeshellarg($source) .
            " " .
            escapeshellarg($destination);
        $output = [];
        $returnVar = 0;

        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            return [
                "status" => "success",
                "message" =>
                "RAR archive extracted successfully using unrar command",
            ];
        } else {
            return [
                "status" => "error",
                "message" =>
                "Failed to extract RAR archive using unrar command",
            ];
        }
    } else {
        return [
            "status" => "error",
            "message" =>
            "RAR extraction not supported - PHP RAR extension or exec() function required",
        ];
    }
}

// Extract 7Zip archive (requires 7zip command)
function extract7Zip($source, $destination)
{
    if (function_exists("exec")) {
        $command =
            "7z x " .
            escapeshellarg($source) .
            " -o" .
            escapeshellarg($destination) .
            " -y";
        $output = [];
        $returnVar = 0;

        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            return [
                "status" => "success",
                "message" => "7Zip archive extracted successfully",
            ];
        } else {
            return [
                "status" => "error",
                "message" =>
                "Failed to extract 7Zip archive: " . implode("\n", $output),
            ];
        }
    } else {
        return [
            "status" => "error",
            "message" =>
            "7Zip extraction not supported - exec() function required",
        ];
    }
}

// Handle File Manager Operations
if (isset($_POST["action"]) || isset($_GET["action"])) {
    $action = isset($_POST["action"]) ? $_POST["action"] : $_GET["action"];

    // Default response
    $response = ["status" => "error", "message" => "Unknown action"];

    // Current directory
    $currentPath = isset($_POST["path"])
        ? $config["root_path"] . $_POST["path"]
        : $config["root_path"];
    if (isset($_GET["path"])) {
        $currentPath = $config["root_path"] . $_GET["path"];
    }

    // Security check
    if (!securityCheck($currentPath) && $action != "search") {
        $response = ["status" => "error", "message" => "Security violation"];
    } else {
        switch ($action) {
            case "settings":
                $tmz = isset($_POST["timezone"]) ? $_POST["timezone"] : "";
                $dt = isset($_POST["dateformat"]) ? $_POST["dateformat"] : "";
                $font = isset($_POST["fontSize"]) ? $_POST["fontSize"] : "";
                $response = updateSettings($tmz, $dt, $font);
                break;

            case "list":
                $sort = isset($_POST["sort"])
                    ? $_POST["sort"]
                    : (isset($_GET["sort"])
                        ? $_GET["sort"]
                        : "name");
                $order = isset($_POST["order"])
                    ? $_POST["order"]
                    : (isset($_GET["order"])
                        ? $_GET["order"]
                        : "asc");
                $response = [
                    "status" => "success",
                    "data" => getDirectoryContents($currentPath, $sort, $order),
                    "current_path" => str_replace(
                        $config["root_path"],
                        "",
                        $currentPath
                    ),
                ];
                break;


            case "tree":
                $response = [
                    "status" => "success",
                    "data" => buildDirectoryTree($config["root_path"]),
                ];
                break;

            case "create_dir":
                $name = isset($_POST["name"]) ? $_POST["name"] : "";
                $response = createDirectory($currentPath, $name);
                break;

            case "create_file":
                $name = isset($_POST["name"]) ? $_POST["name"] : "";
                $content = isset($_POST["content"]) ? $_POST["content"] : "";
                $response = createFile($currentPath, $name, $content);
                break;

            case "rename":
                $oldName = isset($_POST["old_name"]) ? $_POST["old_name"] : "";
                $newName = isset($_POST["new_name"]) ? $_POST["new_name"] : "";
                $response = renameItem($currentPath, $oldName, $newName);
                break;

            case "trash":
                $itemsArray = isset($_POST["items"]) ? $_POST["items"] : "";
                $items = explode(",", $itemsArray);
                $status = true;
                $messages = [];
                $movedCount = 0;
                $failedCount = 0;

                if ($itemsArray == "") {
                    $response = [
                        "status" => "error",
                        "message" => "No items selected",
                    ];
                    break;
                }

                foreach ($items as $item) {
                    $itemPath = $currentPath . DIRECTORY_SEPARATOR . $item;

                    if (!file_exists($itemPath)) {
                        continue;
                    }

                    if (moveToTrash($itemPath, $currentPath, $config)) {
                        $movedCount++;
                    } else {
                        $failedCount++;
                        $status = false;
                    }
                }

                $response = [
                    "status" => $status ? "success" : "error",
                    "message" =>
                    "Moved $movedCount item(s) to trash" .
                        ($failedCount > 0
                            ? ", Failed $failedCount item(s)"
                            : ""),
                ];
                break;

            case "delete":
                $itemsArray = isset($_POST["items"]) ? $_POST["items"] : "";
                $status = true;
                $messages = [];
                $movedCount = 0;
                $failedCount = 0;
                $fmconfig = ".fm-config";
                $items = explode(",", $itemsArray);

                if ($itemsArray == "") {
                    $response = [
                        "status" => "error",
                        "message" => "No items selected",
                    ];
                    break;
                }

                foreach ($items as $item) {
                    $itemPath = $currentPath . DIRECTORY_SEPARATOR . $item;

                    if (!file_exists($itemPath)) {
                        continue;
                    }

                    if (deleteItem($itemPath)) {
                        $movedCount++;
                    } else {
                        $failedCount++;
                        $status = false;
                    }
                }

                $response = [
                    "status" => $status ? "success" : "error",
                    "message" =>
                    "Deleted $movedCount item(s) successfully" .
                        ($failedCount > 0
                            ? ", $failedCount item(s) Failed to delete"
                            : ""),
                ];

                break;

            case "restore":
                $itemsArray = isset($_POST["items"]) ? $_POST["items"] : "";
                $items = explode(",", $itemsArray);
                if ($itemsArray == "") {
                    $response = [
                        "status" => "error",
                        "message" => "No items selected",
                    ];
                    break;
                }
                $status = true;
                $messages = [];
                $restoredCount = 0;
                $failedCount = 0;

                foreach ($items as $item) {
                    $itemPath = $currentPath . DIRECTORY_SEPARATOR . $item;

                    $result = restoreFromTrash($itemPath, $config);

                    if ($result["status"] === "success") {
                        $restoredCount++;
                        $messages[] = "$item restored successfully";
                    } else {
                        $failedCount++;
                        $status = false;
                        $messages[] =
                            "Failed to restore $item: " . $result["message"];
                    }
                }

                $response = [
                    "status" => $status ? "success" : "error",
                    "message" =>
                    "Restored $restoredCount item(s)" .
                        ($failedCount > 0
                            ? ", Failed $failedCount item(s)"
                            : ""),
                ];
                break;

            case "copy":
                $itemsArray = isset($_POST["items"]) ? $_POST["items"] : "";
                $items = explode(",", $itemsArray);
                if ($itemsArray == "") {
                    $response = [
                        "status" => "error",
                        "message" => "No items selected",
                    ];
                    break;
                }

                $destination = isset($_POST["destination"])
                    ? $config["root_path"] . $_POST["destination"]
                    : "";

                if (!securityCheck($destination)) {
                    $response = [
                        "status" => "error",
                        "message" => "Security violation on destination",
                    ];
                    break;
                }

                $status = true;
                $messages = [];

                foreach ($items as $item) {
                    $sourcePath = $currentPath . DIRECTORY_SEPARATOR . $item;
                    $destPath = $destination . DIRECTORY_SEPARATOR . $item;

                    $result = copyItem($sourcePath, $destPath);

                    if ($result["status"] != "success") {
                        $status = false;
                    }

                    $messages[] = $result["message"];
                }

                $response = [
                    "status" => $status ? "success" : "error",
                    "message" => implode(", ", $messages),
                ];
                break;

            case "move":
                $itemsArray = isset($_POST["items"]) ? $_POST["items"] : "";
                $items = explode(",", $itemsArray);
                if ($itemsArray == "") {
                    $response = [
                        "status" => "error",
                        "message" => "No items selected",
                    ];
                    break;
                }
                $destination = isset($_POST["destination"])
                    ? $config["root_path"] . $_POST["destination"]
                    : "";

                if (!securityCheck($destination)) {
                    $response = [
                        "status" => "error",
                        "message" => "Security violation on destination",
                    ];
                    break;

                    $movedCount = 0;
                    $messages = [];

                    foreach ($items as $item) {
                        $sourcePath = $config["root_path"] . $item;
                        $destPath = rtrim($destination, "/") . "/" . basename($item);

                        if (rename($sourcePath, $destPath)) {
                            $movedCount++;
                            $messages[] = "Moved $item successfully";
                        } else {
                            $messages[] = "Failed to move $item";
                        }
                    }

                    $response = [
                        "status" => $movedCount > 0 ? "success" : "error",
                        "message" => implode("\n", $messages),
                    ];
                }

                $status = true;
                $messages = [];

                foreach ($items as $item) {
                    $sourcePath = $currentPath . DIRECTORY_SEPARATOR . $item;
                    $destPath = $destination . DIRECTORY_SEPARATOR . $item;

                    if (!file_exists($destPath)) {
                        if (rename($sourcePath, $destPath)) {
                            $messages[] = "Moved $item successfully";
                        } else {
                            $status = false;
                            $messages[] = "Failed to move $item";
                        }
                    } else {
                        $status = false;
                        $messages[] = "$item already exists in destination";
                    }
                }

                $response = [
                    "status" => $status ? "success" : "error",
                    "message" => implode(", ", $messages),
                ];
                break;

            case "permissions":
                $item = isset($_POST["item"]) ? $_POST["item"] : "";
                $mode = isset($_POST["mode"]) ? $_POST["mode"] : "0644";

                $itemPath = $currentPath . DIRECTORY_SEPARATOR . $item;
                $response = changePermissions($itemPath, $mode);
                break;

            case "compress":
                $itemsArray = isset($_POST["items"]) ? $_POST["items"] : "";
                $type = isset($_POST["type"]) ? $_POST["type"] : "zip";
                $name = isset($_POST["name"]) ? $_POST["name"] : "archive_" . time();

                $items = explode(",", $itemsArray);

                $itemPaths = [];
                foreach ($items as $item) {
                    $itemPaths[] = $currentPath . DIRECTORY_SEPARATOR . $item;
                }

                $extension = "";
                switch ($type) {
                    case "zip":
                        $extension = ".zip";
                        break;
                    case "tar":
                        $extension = ".tar";
                        break;
                    case "gzip":
                        $extension = ".gz";
                        break;
                }

                $destination = $currentPath . DIRECTORY_SEPARATOR . $name . $extension;
                $response = compressItems($itemPaths, $destination, $type);
                break;

            case "read_file":
                $item = isset($_POST["item"]) ? $_POST["item"] : "";
                $itemPath = $currentPath . DIRECTORY_SEPARATOR . $item;

                if (file_exists($itemPath) && is_file($itemPath)) {
                    $content = file_get_contents($itemPath);
                    $response = [
                        "status" => "success",
                        "data" => [
                            "content" => $content,
                            "editable" => true,
                        ],
                    ];
                } else {
                    $response = [
                        "status" => "error",
                        "message" => "File not found",
                    ];
                }
                break;

            case "save_file":
                $item = isset($_POST["item"]) ? $_POST["item"] : "";
                $content = isset($_POST["content"]) ? $_POST["content"] : "";

                $itemPath = $currentPath . DIRECTORY_SEPARATOR . $item;

                if (file_exists($itemPath) && is_file($itemPath)) {
                    if (file_put_contents($itemPath, $content) !== false) {
                        $response = [
                            "status" => "success",
                            "message" => "File saved successfully",
                        ];
                    } else {
                        $response = [
                            "status" => "error",
                            "message" => "Failed to save file",
                        ];
                    }
                } else {
                    $response = [
                        "status" => "error",
                        "message" => "File not found",
                    ];
                }
                break;

            case "search":
                $query = isset($_POST["query"]) ? $_POST["query"] : "";
                $path = isset($_POST["path"])
                    ? $config["root_path"] . $_POST["path"]
                    : $config["root_path"];

                if (!securityCheck($path)) {
                    $response = [
                        "status" => "error",
                        "message" => "Security violation",
                    ];
                    break;
                }

                $results = [];

                if (!empty($query)) {
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator(
                            $path,
                            RecursiveDirectoryIterator::SKIP_DOTS
                        ),
                        RecursiveIteratorIterator::SELF_FIRST
                    );

                    foreach ($iterator as $file) {
                        if (stripos($file->getFilename(), $query) !== false) {
                            $relativePath = str_replace(
                                $config["root_path"],
                                "",
                                $file->getPathname()
                            );

                            $results[] = [
                                "name" => $file->getFilename(),
                                "path" => $relativePath,
                                "type" => $file->isDir() ? "dir" : "file",
                                "size" => $file->isDir()
                                    ? ""
                                    : formatSize($file->getSize()),
                                "last_modified" => date(
                                    $config["date_format"],
                                    $file->getMTime()
                                ),
                                "icon" => getFileIcon($file->getPathname()),
                            ];
                        }
                    }
                }

                $response = ["status" => "success", "data" => $results];
                break;

            case "extract":
                $file = isset($_POST["file"]) ? $_POST["file"] : "";
                $destination = isset($_POST["destination"])
                    ? $_POST["destination"]
                    : "";

                if (empty($file)) {
                    $response = [
                        "status" => "error",
                        "message" => "No file specified for extraction",
                    ];
                    break;
                }

                $filePath = $currentPath . DIRECTORY_SEPARATOR . $file;

                // If no destination specified, create a folder with the same name as the archive
                if (empty($destination)) {
                    $fileInfo = pathinfo($file);
                    $destination =
                        $currentPath .
                        DIRECTORY_SEPARATOR .
                        $fileInfo["filename"];
                } else {
                    $destination = $config["root_path"] . $destination;
                }

                // Make sure the destination directory exists
                if (!file_exists($destination)) {
                    if (!mkdir($destination, 0755, true)) {
                        $response = [
                            "status" => "error",
                            "message" =>
                            "Failed to create destination directory",
                        ];
                        break;
                    }
                }

                $result = extractArchive($filePath, $destination, $config);
                $response = $result;
                break;

            case "download":
                $file = isset($_GET["file"]) ? $_GET["file"] : "";
                $filePath = $currentPath . DIRECTORY_SEPARATOR . $file;

                if (file_exists($filePath) && is_file($filePath)) {
                    // Set headers for file download
                    header("Content-Description: File Transfer");
                    header("Content-Type: application/octet-stream");
                    header(
                        'Content-Disposition: attachment; filename="' .
                            basename($filePath) .
                            '"'
                    );
                    header("Expires: 0");
                    header("Cache-Control: must-revalidate");
                    header("Pragma: public");
                    header("Content-Length: " . filesize($filePath));
                    readfile($filePath);
                    exit();
                } else {
                    $response = [
                        "status" => "error",
                        "message" => "File not found",
                    ];
                }
                break;
        }
    }

    // Return JSON response for AJAX requests
    if ($action != "download") {
        header("Content-Type: application/json");
        echo json_encode($response);
        exit();
    }
}


function parseSize($size)
{
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
    $size = preg_replace('/[^0-9\.]/', '', $size);
    if ($unit) {
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    }
    return round($size);
}


if (isset($_FILES["files"])) {
    header("Content-Type: application/json");

    $currentPath = isset($_POST["path"])
        ? $config["root_path"] . $_POST["path"]
        : $config["root_path"];

    if (!securityCheck($currentPath)) {
        echo json_encode([
            "status" => "error",
            "message" => "Security violation",
        ]);
        exit();
    }

    $files = $_FILES["files"];
    $uploaded = 0;
    $failed = 0;
    $failedFiles = [];

    for ($i = 0; $i < count($files["name"]); $i++) {
        $fileName = $files["name"][$i];
        $filePath = $currentPath . DIRECTORY_SEPARATOR . $fileName;

        // Check upload error codes
        if ($files["error"][$i] !== UPLOAD_ERR_OK) {
            $failed++;
            $errorMsg = "";
            switch ($files["error"][$i]) {
                case UPLOAD_ERR_INI_SIZE:
                    $errorMsg =
                        "File exceeds upload_max_filesize directive in php.ini";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMsg =
                        "File exceeds MAX_FILE_SIZE directive specified in the HTML form";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMsg = "File was only partially uploaded";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errorMsg = "No file was uploaded";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errorMsg = "Missing a temporary folder";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errorMsg = "Failed to write file to disk";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $errorMsg = "File upload stopped by extension";
                    break;
                default:
                    $errorMsg = "Unknown upload error";
            }
            $failedFiles[] = $fileName . " (" . $errorMsg . ")";
            continue;
        }

        // File size check
        $maxFileSize = parseSize(ini_get('upload_max_filesize'));
        if ($files["size"][$i] > $maxFileSize) {
            $failed++;
            $failedFiles[] = $fileName . " (Exceeds maximum file size limit)";
            continue;
        }

        // Extension check
        if ($config["allowed_extensions"][0] !== "*") {
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($extension, $config["allowed_extensions"])) {
                $failed++;
                $failedFiles[] = $fileName . " (File type not allowed)";
                continue;
            }
        }

        if (move_uploaded_file($files["tmp_name"][$i], $filePath)) {
            $uploaded++;
        } else {
            $failed++;
            $failedFiles[] = $fileName . " (Server error during move)";
        }
    }

    $response = [
        "status" => $failed == 0 ? "success" : "partial",
        "message" => "Uploaded $uploaded file(s)" .
            ($failed > 0 ? ", Failed $failed file(s)" : ""),
    ];

    if (!empty($failedFiles)) {
        $response["failedFiles"] = $failedFiles;
    }

    echo json_encode($response);
    exit();
}

$lines = file(__FILE__);
foreach ($lines as $line) {
    if (strpos($line, "* The Kinsmen") !== false) {
        $versionInfo = trim(str_replace("*", "", $line));
        break;
    }
}


$version = explode(" ", $versionInfo);
$version = end($version);

if ($username == null) {
    $username = "";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>File Manager</title>
    <link rel="icon" href="icon.png" type="image/png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/coolalertjs@latest/dist/coolalert.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/htmx.org@2.0.6/dist/htmx.min.js"></script>
    <style>
        :root {
            --kinsmen-primary: #0c0f25;
            --kinsmen-secondary: #428bca;
            --kinsmen-bg: #f8f9fa;
            --kinsmen-border: #dee2e6;
        }

        html,
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: <?= $config["font_size"] ?>;
            background-color: var(--kinsmen-bg);
        }

        .cool-alert-toast {
            background-color: var(--kinsmen-primary) !important;
        }

        .top-header {
            background-color: var(--kinsmen-primary);
            color: white;
            padding: 8px 15px;
            border-bottom: 1px solid var(--kinsmen-border);
        }

        .top-header .brand {
            font-weight: bold;
            font-size: 0.875rem;
        }

        .search-container {
            max-width: 300px;
        }

        .main-toolbar {
            background-color: #e9ecef;
            padding: 8px 15px;
            border-bottom: 1px solid var(--kinsmen-border);
            line-height: 30px;
        }

        .main-toolbar .btn {
            font-size: 0.875rem;
            padding: 4px 8px;
            margin-right: 5px;
        }

        .navigation-bar {
            background-color: #f1f3f4;
            padding: 8px 15px;
            border-bottom: 1px solid var(--kinsmen-border);
        }

        .navigation-bar .btn {
            font-size: 0.875rem;
            padding: 4px 8px;
            margin-right: 5px;
        }

        .sidebar {
            background-color: white;
            border-right: 1px solid var(--kinsmen-border);
            height: calc(100vh - 120px);
            overflow-y: auto;
            padding: 0 10px;
        }

        .sidebar .folder-tree {
            font-size: 0.875rem;
        }

        .sidebar .folder-tree .folder-item {
            padding: 2px 0;
            cursor: pointer;
            white-space: nowrap;
        }

        .sidebar .folder-tree .folder-item:hover {
            background-color: #f8f9fa;
        }

        .sidebar .folder-tree .folder-item.active {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .sidebar .folder-tree .folder-item i {
            width: 14px;
            margin-right: 5px;
        }

        .main-content {
            background-color: white;
            height: calc(100vh - 120px);
            overflow-y: auto;
        }

        .file-table {
            font-size: 0.875rem;
            width: 100%;
            border-collapse: collapse;
        }

        .file-table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid var(--kinsmen-border);
            padding: 8px;
            font-weight: 600;
            color: var(--kinsmen-secondary);
        }

        .file-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        .file-table tr:hover {
            background-color: #f8f9fa;
        }

        .file-table tr.selected {
            background-color: rgba(13, 110, 253, 0.1);
        }

        .file-icon {
            width: 14px;
            margin-right: 8px;
        }

        .file-name {
            color: #000;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
        }

        .file-name:hover {
            text-decoration: underline;
        }

        .folder-icon {
            color: #ffa726;
        }

        .file-size,
        .file-date {
            color: var(--kinsmen-secondary);
        }

        .permissions {
            font-family: monospace;
            font-size: 0.688rem;
        }

        .checkbox-col {
            width: 30px;
        }

        .icon-col {
            width: 40px;
        }

        .collapse-all {
            font-size: 0.875rem;
            color: var(--kinsmen-secondary);
            cursor: pointer;
            margin-bottom: 10px;
        }

        .btn-sm {
            font-size: 0.688rem;
            padding: 2px 6px;
        }

        .header-btns {
            text-decoration: none;
            font-size: 0.875rem;
            margin-right: 15px;
            color: #495057;
            transition: color 0.2s;
        }

        .nav-links {
            text-decoration: none;
            font-size: 0.875rem;
            margin-right: 15px;
            color: var(--kinsmen-secondary);
            transition: color 0.2s;
        }

        .header-btns:hover,
        .nav-links:hover {
            color: #007bff;
        }

        .header-btns.disabled,
        .nav-links.disabled {
            pointer-events: none;
            color: #adb5bd;
            cursor: not-allowed;
        }

        .header-btns.disabled:hover,
        .nav-links.disabled:hover {
            cursor: not-allowed;
            color: #adb5bd;
        }



        /* Progress bar */
        .progress {
            height: 20px;
            border-radius: 0;
            display: none;
            text-align: center;

        }

        .progress-bar {
            transition: width 0.3s ease;
            text-align: center;
            line-height: 20px;
            font-size: 0.75rem;
            font-weight: bold;
            color: white;
            overflow: visible;
            text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.9);
            white-space: nowrap;
        }

        /* Context menu */
        .context-menu {
            position: absolute;
            z-index: 1000;
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            min-width: 180px;
            display: none;
        }

        .context-menu-item {
            padding: 0.5rem 1rem;
            cursor: pointer;
        }

        .context-menu-item:hover {
            background-color: #f8f9fa;
        }

        .context-menu-divider {
            border-top: 1px solid #dee2e6;
            margin: 0.25rem 0;
        }

        /* Drag and drop */
        .drag-over {
            background-color: rgba(13, 110, 253, 0.1);
            border: 2px dashed #086bfc !important;
        }

        /* File tree styles */
        .file-tree {
            list-style: none;
            padding-left: 0;
        }

        .file-tree .folder-item {
            display: flex;
            align-items: center;
            padding: 2px 5px;
            margin: 1px 0;
            border-radius: 3px;
            cursor: pointer;
        }

        .file-tree .folder-item:hover {
            background-color: #f8f9fa;
        }

        .file-tree .folder-item.active {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .file-tree .caret {
            margin-right: 5px;
            transition: transform 0.2s;
        }

        .file-tree .caret.expanded {
            transform: rotate(90deg);
        }

        .file-tree .nested {
            display: none;
            padding-left: 20px;
        }

        .file-tree .nested.active {
            display: block;
        }

        .code-editor {
            color: #67a0f5;
            font-family: "Fira Code", "Courier New", monospace;
            font-size: 0.75rem;
            line-height: 1.5;
            padding: 10px;
            resize: vertical;
            width: 100%;
        }

        .modal-header {
            padding: 10px;
        }

        #deleteItems li {
            font-size: 0.5rem;

        }

        .htmx-request #submit-button {
            cursor: not-allowed;
            opacity: 0.5;
        }


        .htmx-request .spinner-border {
            display: inline-block !important;
        }

        .spinner-container {
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: transparent;
        }

        .spinners {
            border: 2px solid #fff;
            border-top: 2px solid #9733eb;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            background: transparent !important;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .my-indicator {
            display: none;
        }

        .htmx-request .my-indicator {
            display: inline;

        }

        .htmx-request.my-indicator {
            display: inline;
        }
    </style>
</head>

<body>
    <!-- Top Header -->
    <div class="top-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <span class="brand"><img src="logo.png" width="100px" /></span>
        </div>
        <div class="d-flex align-items-center">
            <div class="search-container me-3">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control form-control-sm" id="search-input" placeholder="Search files">
                    <button class="btn btn-primary btn-sm ms-1" id="search-btn">Go</button>
                </div>
            </div>
            <button class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#settingsModal">
                <i class="fas fa-cog"></i> Settings
            </button>
        </div>
    </div>

    <!-- Main Toolbar -->
    <div class="d-flex align-items-center main-toolbar mb-2 py-1 px-3">
        <div>
            <a href="#" class="header-btns" id="new-file-btn"><i class="fas fa-file"></i> File</a>
            <a href="#" class="header-btns" id="new-folder-btn"><i class="fas fa-folder"></i> Folder</a>
            <a href="#" class="header-btns disabled" id="copy-btn"><i class="fas fa-copy"></i> Copy</a>
            <a href="#" class="header-btns disabled" id="move-btn"><i class="fas fa-arrows-alt"></i> Move</a>
            <a href="#" class="header-btns" id="upload-btn"><i class="fas fa-upload"></i> Upload</a>
            <a href="#" class="header-btns disabled" id="download-btn"><i class="fas fa-download"></i> Download</a>
            <a href="#" class="header-btns disabled" id="delete-btn"><i class="fas fa-trash"></i> Delete</a>
            <a href="#" class="header-btns disabled" id="restore-btn"><i class="fas fa-undo"></i> Restore</a>
            <a href="#" class="header-btns disabled" id="rename-btn"><i class="fas fa-tag"></i> Rename</a>
            <a href="#" class="header-btns disabled" id="edit-btn"><i class="fas fa-edit"></i> Edit</a>
            <a href="#" class="header-btns disabled" id="permissions-btn"><i class="fas fa-shield-alt"></i> Permissions</a>
            <a href="#" class="header-btns disabled" id="extract-btn"><i class="fas fa-file-archive"></i> Extract</a>
            <a href="#" class="header-btns disabled" id="compress-btn"><i class="fas fa-compress"></i> Compress</a>
            <input type="file" id="file-upload" multiple style="display: none;">
        </div>
        <!-- Progress Bar -->
        <div class="progress ms-auto me-3 w-25 border border-success" id="upload-progress">
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                role="progressbar"
                style="width: 0%"
                aria-valuenow="0"
                aria-valuemin="0"
                aria-valuemax="100">
                Preparing upload...
            </div>
        </div>
    </div>



    <!-- Main Content Area -->
    <div class="container-fluid px-2">
        <div class="row g-0">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar border border-0">
                <div class="search-container" style="margin-bottom: 10px;">
                    <div class="input-group input-group-sm">
                        <button class="btn btn-sm btn-outline-secondary rounded-0" id="breadcrumb-home-btn"><i class="fas fa-home"></i></button>
                        <input type="text" class="form-control form-control-sm border-secondary rounded-0" id="breadcrumb">
                        <button class="btn btn-secondary btn-sm rounded-0" id="breadcrumb-search-btn">Go</button>
                    </div>
                </div>
                <div class="collapse-all border py-1 text-center" id="collapse-all-btn">Collapse All</div>
                <div class="folder-tree" id="directory-tree">
                    <div class="folder-item active">
                        <i class="fas fa-home"></i> (/home/<?= $username ?>)
                    </div>
                    <ul class="file-tree">
                        <li><i class="fas fa-spinner fa-spin mt-2"></i> Loading...</li>
                    </ul>
                </div>
            </div>


            <!-- Main File Area -->
            <div class="col-md-10 main-content" id="dropzone">
                <!-- Navigation Bar -->
                <div class="navigation-bar border mb-2 py-1">
                    <a href="#" class="nav-links" id="home-btn"><i class="fas fa-home"></i> Home</a>
                    <a href="#" class="nav-links" id="up-btn"><i class="fas fa-level-up-alt"></i> Up One Level</a>
                    <a href="#" class="nav-links" id="reload-btn"><i class="fas fa-sync"></i> Reload</a>
                    <a href="#" class="nav-links" id="select-all-btn"><i class="fas fa-check-square"></i> Select All</a>
                    <a href="#" class="nav-links disabled" id="unselect-all-btn"><i class="fas fa-square"></i> Unselect All</a>
                    <a href="#" class="nav-links" id="view-trash-btn"><i class="fas fa-trash-alt"></i> View Trash</a>
                    <a href="#" class="nav-links" id="sort-btn"><i class="fa-solid fa-arrow-up-wide-short"></i> Sort</a>
                </div>
                <div class="border-start h-100">
                    <table class="table table-sm table-striped border-top border-end file-table mb-4">
                        <thead>
                            <tr>
                                <th class="checkbox-col"><input type="checkbox" class="form-check-input border-dark" id="select-all-checkbox"></th>
                                <th class="icon-col"></th>
                                <th>Name</th>
                                <th>Size</th>
                                <th>Last Modified</th>
                                <th>Type</th>
                                <th>Permissions</th>
                            </tr>
                        </thead>
                        <tbody id="files-list">
                            <tr>
                                <td colspan="7" class="text-center p-2">Loading files...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="text-center py-2">
            &copy; <?= date('Y') ?> <a href="https://thekinsmen.net" class="text-decoration-none" target="_blank">The Kinsmen</a> | <?= $versionInfo ?> | <a href="https://github.com/JosephChuks/php-file-manager-with-code-editor" class="text-decoration-none" target="_blank"><i class="fa-brands fa-github"></i> github</a>
        </div>
    </div>


    <!-- Context Menu -->
    <div class="context-menu" id="context-menu">
        <div class="context-menu-item" id="ctx-open"><i class="fas fa-folder-open me-2"></i> Open</div>
        <div class="context-menu-item" id="ctx-download"><i class="fas fa-download me-2"></i> Download</div>
        <div class="context-menu-item" id="ctx-edit"><i class="fas fa-edit me-2"></i> Edit</div>
        <div class="context-menu-divider"></div>
        <div class="context-menu-item" id="ctx-copy"><i class="fas fa-copy me-2"></i> Copy</div>
        <div class="context-menu-item" id="ctx-cut"><i class="fas fa-cut me-2"></i> Move</div>
        <div class="context-menu-divider"></div>
        <div class="context-menu-item" id="ctx-rename"><i class="fas fa-tag me-2"></i> Rename</div>
        <div class="context-menu-item" id="ctx-permissions"><i class="fas fa-shield-alt me-2"></i> Permissions</div>
        <div class="context-menu-item" id="ctx-compress"><i class="fas fa-compress me-2"></i> Compress</div>
        <div class="context-menu-divider"></div>
        <div class="context-menu-item text-danger" id="ctx-delete"><i class="fas fa-trash me-2"></i> Delete</div>
    </div>

    <!-- Modals -->
    <!-- Extract Modal -->
    <div class="modal fade" id="extractModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form hx-post=""
                    hx-trigger="submit, keyup[enter]"
                    hx-swap="none">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold fs-6">Extract Archive</h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Archive: <span id="extractFileName" class="fw-bold"></span></label>
                        </div>
                        <div class="mb-3">
                            <label for="extractPath" class="form-label">Extract to</label>
                            <input type="text" class="form-control" id="extractPath" name="destination" placeholder="Extraction path">
                            <input type="hidden" name="action" value="extract">
                            <input type="hidden" name="path" id="extractPathName">
                            <input type="hidden" name="file" id="extractFileNameValue">
                            <small class="text-muted">Leave empty to extract to a folder with the same name as the archive</small>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            The archive will be extracted to the specified location. If the directory doesn't exist, it will be created.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary d-flex justify-content-center align-items-center">
                            <span>Extract</span>
                            <div class="spinner-container ms-2 my-indicator">
                                <div class="spinners"></div>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Folder Modal -->
    <div class="modal fade" id="newFolderModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form hx-post=""
                    hx-trigger="submit"
                    hx-swap="none">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold fs-6">Create New Folder</h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="folderName" class="form-label">Folder Name</label>
                            <input type="text" name="name" class="form-control" id="folderName" placeholder="Enter folder name">
                            <input type="hidden" name="path" id="currenPathValue">
                            <input type="hidden" name="action" value="create_dir">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary d-flex justify-content-center align-items-center">
                            <span>Create</span>
                            <div class="spinner-container ms-2 my-indicator">
                                <div class="spinners"></div>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create File Modal -->
    <div class="modal fade" id="newFileModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form hx-post=""
                    hx-trigger="submit"
                    hx-swap="none">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold fs-6">Create New File</h5>

                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="fileName" class="form-label">File Name</label>
                            <input type="text" name="name" class="form-control" id="fileName" placeholder="Enter file name">
                            <input type="hidden" name="path" id="filePath">
                            <input type="hidden" name="action" value="create_file">
                        </div>
                        <div class="mb-3">
                            <label for="fileContent" class="form-label">Content</label>
                            <textarea class="code-editor" name="content" id="fileContent" rows="10"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary d-flex justify-content-center align-items-center">
                            <span>Create</span>
                            <div class="spinner-container ms-2 my-indicator">
                                <div class="spinners"></div>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Rename Modal -->
    <div class="modal fade" id="renameModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form hx-post=""
                    hx-trigger="submit"
                    hx-swap="none">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold fs-6">Rename Item</h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="newName" class="form-label">New Name</label>
                            <input type="text" name="new_name" class="form-control" id="newName">
                            <input type="hidden" name="old_name" id="oldName">
                            <input type="hidden" name="path" id="renamePath">
                            <input type="hidden" name="action" value="rename">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary d-flex justify-content-center align-items-center">
                            <span>Rename</span>
                            <div class="spinner-container ms-2 my-indicator">
                                <div class="spinners"></div>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Settings Folder Modal -->
    <div class="modal fade" id="settingsModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form hx-post=""
                    hx-trigger="submit"
                    hx-swap="none">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold fs-6">Update Settings</h5>

                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="timezone" class="form-label">Timezone</label>
                            <input type="text" class="form-control" name="timezone" value="<?= $config['timezone'] ?>" placeholder="UTC" required>
                            <input type="hidden" name="action" value="settings">
                            <small>PHP timezone only</small>
                        </div>
                        <div class="mb-3">
                            <label for="dateformat" class="form-label">Date Format</label>
                            <input type="text" class="form-control" name="dateformat" value="<?= $config['date_format'] ?>" placeholder="Y-m-d H:i:s" required>
                            <small>PHP date format only</small>
                        </div>
                        <div class="mb-3">
                            <label for="fontSize" class="form-label">Font Size</label>
                            <input type="text" class="form-control" name="fontSize" value="<?= $config['font_size'] ?>" placeholder="16px" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary d-flex justify-content-center align-items-center">
                            <span>Update</span>
                            <div class="spinner-container ms-2 my-indicator">
                                <div class="spinners"></div>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Permissions Modal -->
    <div class="modal fade" id="permissionsModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form hx-post=""
                    hx-trigger="submit"
                    hx-swap="none">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold fs-6">Change Permissions</h5>

                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Item: <span id="permItem"></span></label>
                        </div>
                        <div class="mb-3">
                            <label for="permValue" class="form-label">Octal Permission Value</label>
                            <input type="text" name="mode" class="form-control" id="permValue" placeholder="e.g. 0755">
                            <input type="hidden" name="path" id="permissionPath">
                            <input type="hidden" name="item" id="permItemValue">
                            <input type="hidden" name="action" value="permissions">
                        </div>
                        <div class="row mb-3">
                            <div class="col-4">
                                <div class="border p-2">
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" id="ownerRead" data-value="400">
                                        <label class="form-check-label">Owner Read</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" id="ownerWrite" data-value="200">
                                        <label class="form-check-label">Owner Write</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" id="ownerExec" data-value="100">
                                        <label class="form-check-label">Owner Execute</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border p-2">
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" id="groupRead" data-value="40">
                                        <label class="form-check-label">Group Read</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" id="groupWrite" data-value="20">
                                        <label class="form-check-label">Group Write</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" id="groupExec" data-value="10">
                                        <label class="form-check-label">Group Execute</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border p-2">
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" id="publicRead" data-value="4">
                                        <label class="form-check-label">Public Read</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" id="publicWrite" data-value="2">
                                        <label class="form-check-label">Public Write</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" id="publicExec" data-value="1">
                                        <label class="form-check-label">Public Execute</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary d-flex justify-content-center align-items-center">
                            <span>Change Permission</span>
                            <div class="spinner-container ms-2 my-indicator">
                                <div class="spinners"></div>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Compress Modal -->
    <div class="modal fade" id="compressModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form
                    hx-post=""
                    hx-trigger="submit"
                    hx-swap="none">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold fs-6">Compress Items</h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="compressName" class="form-label">Archive Name</label>
                            <input type="text" class="form-control" name="name" id="compressName" placeholder="archive">
                            <input type="hidden" name="items" id="compressItems">
                            <input type="hidden" name="path" id="compressPath">
                            <input type="hidden" name="action" value="compress">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Compression Type</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="compressZip" value="zip" checked>
                                <label class="form-check-label" for="compressZip">
                                    ZIP (.zip)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="compressTar" value="tar">
                                <label class="form-check-label" for="compressTar">
                                    TAR (.tar)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="compressGzip" value="gzip">
                                <label class="form-check-label" for="compressGzip">
                                    GZip (.gz) - Single file only
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary d-flex justify-content-center align-items-center">
                            <span>Compress</span>
                            <div class="spinner-container ms-2 my-indicator">
                                <div class="spinners"></div>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal with Trash Option -->
    <div class="modal fade" id="deleteModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form
                    hx-post=""
                    hx-trigger="submit"
                    hx-swap="none">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold fs-6">Confirm Delete</h5>

                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete the selected item(s)?</p>
                        <input type="hidden" name="items" id="deleteItems">
                        <input type="hidden" name="path" id="deletePath">
                        <input type="hidden" name="action" id="deleteAction">

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="permanentDeleteCheck">
                            <label class="form-check-label" for="permanentDeleteCheck">
                                Skip the trash and permanently delete the files
                            </label>
                        </div>

                        <div class="alert alert-info" id="deleteInfoAlert">
                            <i class="bi bi-info-circle me-2"></i>
                            Items will be moved to the trash folder and can be restored later.
                        </div>

                        <div class="alert alert-danger" id="permanentDeleteAlert" style="display: none;">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> This action cannot be undone. Items will be permanently deleted.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary d-flex justify-content-center align-items-center">
                            <span id="deleteButtonText">Move to Trash</span>
                            <div class="spinner-container ms-2 my-indicator">
                                <div class="spinners"></div>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Search Results Modal -->
    <div class="modal fade" id="searchModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold fs-6">Search Results</h5>

                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="search-results-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Path</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Last Modified</th>
                                </tr>
                            </thead>
                            <tbody id="search-results">
                                <tr>
                                    <td colspan="5" class="text-center">Searching...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- File Operation Modal (Copy/Move) -->
    <div class="modal fade" id="fileOperationModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">

            <div class="modal-content">
                <form
                    hx-post=""
                    hx-trigger="submit"
                    hx-swap="none">
                    <div class="modal-header">
                        <h5 class="modal-title fs-6" id="fileOpTitle">File Operation</h5>

                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="fileOpType" value="copy">
                        <div class="mb-3">
                            <div id="fileOpItems">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="destinationPath" class="form-label">Destination Path</label>
                            <input type="text" name="destination" class="form-control" id="destinationPath" placeholder="Enter destination path" required>
                            <input type="hidden" name="path" id="copyPath">
                            <input type="hidden" name="items" id="copyItems">
                            <small class="text-muted">Current path: <span id="currentPathDisplay"></span></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary d-flex justify-content-center align-items-center">
                            <span id="deleteButtonText">Execute</span>
                            <div class="spinner-container ms-2 my-indicator">
                                <div class="spinners"></div>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Restore Confirmation Modal - Make sure this exists in your HTML -->
    <div class="modal fade" id="restoreModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form
                    hx-post=""
                    hx-trigger="submit"
                    hx-swap="none">
                    <input type="hidden" name="path" id="restorePath">
                    <input type="hidden" name="items" id="restoreItems">
                    <input type="hidden" name="action" value="restore">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold fs-6">Restore Items</h5>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to restore the following items to their original locations?</p>
                        <ul id="restoreItems" class="list-group mb-3"></ul>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Items will be moved back to their original locations if possible. If an item with the same name exists at the destination, the restored item will be renamed.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary d-flex justify-content-center align-items-center">
                            <span id="deleteButtonText">Restore</span>
                            <div class="spinner-container ms-2 my-indicator">
                                <div class="spinners"></div>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>




    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        var SERVER_ROOT_PATH = "<?php echo $config["root_path"]; ?>";
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            let currentPath = '';
            let selectedItems = [];
            let fileList = [];
            let clipboardItems = [];
            let clipboardAction = '';
            let currentSort = 'name';
            let currentOrder = 'asc';
            let contextTarget = null;
            let clickedBtn = null;
            let hasResponseError = false;

            function loadFileList() {
                const filesList = document.getElementById('files-list');
                filesList.innerHTML = '<tr><td colspan="7" class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Loading files...</td></tr>';
                selectedItems = [];
                updateButtonStates();
                const formData = new FormData();
                formData.append('action', 'list');
                formData.append('path', currentPath);
                formData.append('sort', currentSort);
                formData.append('order', currentOrder);

                fetch(window.location.pathname, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            fileList = data.data;

                            updateBreadcrumb(data.current_path);
                            showFiles(fileList);
                            if (data.current_path !== undefined) {
                                currentPath = data.current_path;
                            }
                        } else {
                            showAlert('Error', data.message);
                        }
                    })
                    .catch(error => {
                        showAlert('Error', 'Failed to load file list');
                    });
            }

            function updateBreadcrumb(path) {
                const breadcrumb = document.getElementById('breadcrumb');
                breadcrumb.value = path;
            }

            function getMimeType(filename) {
                const extension = filename.split('.').pop().toLowerCase();

                const mimeTypes = {
                    // Text files
                    'txt': 'text/plain',
                    'html': 'text/html',
                    'htm': 'text/html',
                    'css': 'text/css',
                    'js': 'application/javascript',
                    'json': 'application/json',
                    'xml': 'application/xml',
                    'csv': 'text/csv',
                    'md': 'text/markdown',

                    // Images
                    'jpg': 'image/jpeg',
                    'jpeg': 'image/jpeg',
                    'png': 'image/png',
                    'gif': 'image/gif',
                    'webp': 'image/webp',
                    'svg': 'image/svg+xml',
                    'ico': 'image/x-icon',
                    'bmp': 'image/bmp',
                    'tiff': 'image/tiff',

                    // Audio/Video
                    'mp3': 'audio/mpeg',
                    'wav': 'audio/wav',
                    'ogg': 'audio/ogg',
                    'mp4': 'video/mp4',
                    'mov': 'video/quicktime',
                    'avi': 'video/x-msvideo',
                    'webm': 'video/webm',

                    // Documents
                    'pdf': 'application/pdf',
                    'doc': 'application/msword',
                    'docx': 'application/docx',
                    'xls': 'application/vnd.ms-excel',
                    'xlsx': 'application/xlsx',
                    'ppt': 'application/vnd.ms-powerpoint',
                    'pptx': 'application/pptx',

                    // Archives
                    'zip': 'application/zip',
                    'tar': 'application/x-tar',
                    'gz': 'application/gzip',
                    'rar': 'application/vnd.rar',
                    '7z': 'application/x-7z-compressed',

                    // Code
                    'php': 'application/x-httpd-php',
                    'py': 'text/x-python',
                    'java': 'text/x-java-source',
                    'c': 'text/x-c',
                    'cpp': 'text/x-c++',
                    'sh': 'application/x-sh',
                };

                return mimeTypes[extension] || 'text/x-generic';
            }

            function showFiles(files) {
                const filesList = document.getElementById('files-list');

                if (files.length === 0) {
                    filesList.innerHTML = '<tr><td colspan="7" class="p-2">This directory is empty.</td></tr>';
                    return;
                }

                let html = '';

                files.forEach(file => {
                    html += `
                    <tr class="file-item" data-name="${file.name}" data-type="${file.type}">
                        <td><input type="checkbox" class="form-check-input item-check"></td>
                        <td>${file.name === "public_html" ? "<i class='fas fa-globe text-primary'></i>" : file.icon}   </td>
                        <td><a href="#" class="file-name">${file.name}</a></td>
                        <td class="file-size">${file.size}</td>
                        <td class="file-date">${file.last_modified}</td>
                        <td>${file.type === 'dir' ? 'httpdunix-directory' : getMimeType(file.name)}</td>
                        <td class="permissions">${file.permissions}</td>
                    </tr>`;
                });

                filesList.innerHTML = html;

                document.querySelectorAll('.file-item').forEach(item => {
                    // Double click to open
                    item.addEventListener('dblclick', function() {
                        const name = this.getAttribute('data-name');
                        const type = this.getAttribute('data-type');

                        if (type === 'dir') {
                            navigateTo(currentPath + '/' + name);
                        } else {
                            if (isEditable(name)) {
                                openFileEditor(name);
                            } else {
                                // Download the file
                                window.location.href = `${window.location.pathname}?action=download&path=${encodeURIComponent(currentPath)}&file=${encodeURIComponent(name)}`;
                            }
                        }
                    });

                    // Single click to select
                    item.addEventListener('click', function(e) {
                        if (e.target.type === 'checkbox') return;

                        const checkbox = this.querySelector('.item-check');
                        checkbox.checked = !checkbox.checked;

                        // Trigger change event
                        const event = new Event('change');
                        checkbox.dispatchEvent(event);
                    });

                    // Context menu
                    item.addEventListener('contextmenu', function(e) {
                        e.preventDefault();

                        const name = this.getAttribute('data-name');
                        const type = this.getAttribute('data-type');

                        // Store the context target
                        contextTarget = {
                            name: name,
                            type: type
                        };

                        // Select this item
                        const checkbox = this.querySelector('.item-check');
                        if (!checkbox.checked) {
                            checkbox.checked = true;
                            const event = new Event('change');
                            checkbox.dispatchEvent(event);
                        }

                        // Show context menu
                        showContextMenu(e.pageX, e.pageY, type);
                    });

                    // Checkbox change
                    const checkbox = item.querySelector('.item-check');
                    checkbox.addEventListener('change', function() {
                        const name = item.getAttribute('data-name');

                        if (this.checked) {
                            item.classList.add('selected');
                            if (!selectedItems.includes(name)) {
                                selectedItems.push(name);
                            }
                        } else {
                            item.classList.remove('selected');
                            const index = selectedItems.indexOf(name);
                            if (index !== -1) {
                                selectedItems.splice(index, 1);
                            }
                        }

                        updateButtonStates();
                    });
                });
            }

            function isEditable(fileName) {
                return true;
            }

            function showContextMenu(x, y, type) {
                const contextMenu = document.getElementById('context-menu');

                contextMenu.style.visibility = 'hidden';
                contextMenu.style.display = 'block';

                const menuWidth = contextMenu.offsetWidth;
                const menuHeight = contextMenu.offsetHeight;
                const viewportWidth = window.innerWidth;
                const viewportHeight = window.innerHeight;

                if (x + menuWidth > viewportWidth) {
                    x = viewportWidth - menuWidth - 10;
                }

                if (y + menuHeight > viewportHeight) {
                    y = y - menuHeight;
                    if (y < 0) {
                        y = 10;
                    }
                }

                contextMenu.style.left = `${x}px`;
                contextMenu.style.top = `${y}px`;
                contextMenu.style.visibility = 'visible';

                document.getElementById('ctx-edit').style.display = type === 'file' ? 'block' : 'none';
                document.getElementById('ctx-open').style.display = type === 'dir' ? 'block' : 'none';


                document.addEventListener('click', function closeMenu(e) {
                    if (!contextMenu.contains(e.target)) {
                        contextMenu.style.display = 'none';
                        document.removeEventListener('click', closeMenu);
                    }
                });
            }

            function updateButtonStates() {
                const count = selectedItems.length;
                const inTrash = currentPath === "/.trash";

                const isArchive = count === 1 && (() => {
                    const fileItem = fileList.find(item => item.name === selectedItems[0]);
                    if (!fileItem || fileItem.type !== "file") return false;
                    const ext = fileItem.extension?.toLowerCase() || "";
                    return ["zip", "tar", "gz", "gzip", "bz2", "bzip2", "rar", "7z"].includes(ext);
                })();

                const isFile = count === 1 && fileList.find(item => item.name === selectedItems[0])?.type === "file";

                const buttonStates = {
                    'permissions-btn': count === 1 && !inTrash,
                    'edit-btn': count === 1 && !inTrash,
                    'rename-btn': count === 1 && !inTrash,
                    'copy-btn': count > 0 && !inTrash,
                    'move-btn': count > 0 && !inTrash,
                    'delete-btn': count > 0 && !inTrash,
                    'compress-btn': count > 0 && !inTrash,
                    'download-btn': isFile && !inTrash,
                    'extract-btn': isArchive && !inTrash,
                    'restore-btn': count > 0 && inTrash,
                    'unselect-all-btn': count > 0
                };

                Object.entries(buttonStates).forEach(([btnId, enabled]) => {
                    const btn = document.getElementById(btnId);
                    if (btn) {
                        btn.classList.toggle('disabled', !enabled);
                    }
                });
            }

            function loadDirectoryTree() {
                const directoryTree = document.querySelector('#directory-tree .file-tree');
                directoryTree.innerHTML = '<li><i class="fas fa-spinner fa-spin"></i> Loading...</li>';

                const formData = new FormData();
                formData.append('action', 'tree');

                fetch(window.location.pathname, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            directoryTree.innerHTML = buildTreeHTML(data.data);

                            addTreeEventListeners();
                        } else {
                            directoryTree.innerHTML = '<li>Failed to load directory tree</li>';
                        }
                    })
                    .catch(error => {
                        directoryTree.innerHTML = '<li>Failed to load directory tree</li>';
                    });
            }


            function buildTreeHTML(tree) {
                let html = '';

                tree.forEach(item => {
                    if (item.type === 'dir') {
                        let itemPath = item.path;
                        if (itemPath.startsWith('//')) {
                            itemPath = itemPath.substring(1);
                        }

                        html += `
                        <li>
                            <span class="folder-item" data-path="${itemPath}">
                                <i class="fas fa-caret-right caret" data-expanded="false"></i>
                                <i class="fas fa-folder folder-icon"></i>
                                ${item.name}
                            </span>`;

                        if (item.children && item.children.length > 0) {
                            html += `<ul class="file-tree nested">`;
                            html += buildTreeHTML(item.children);
                            html += `</ul>`;
                        }

                        html += `</li>`;
                    }
                });

                return html;
            }


            function addTreeEventListeners() {
                document.querySelectorAll('.caret').forEach(caret => {
                    caret.addEventListener('click', function(e) {
                        e.stopPropagation();

                        const nested = this.parentElement.parentElement.querySelector('.nested');
                        const isExpanded = this.getAttribute('data-expanded') === 'true';

                        if (nested) {
                            if (isExpanded) {
                                nested.classList.remove('active');
                                this.classList.remove('expanded');
                                this.setAttribute('data-expanded', 'false');
                            } else {
                                nested.classList.add('active');
                                this.classList.add('expanded');
                                this.setAttribute('data-expanded', 'true');
                            }
                        }
                    });
                });


                document.querySelectorAll('.folder-item').forEach(item => {
                    item.addEventListener('click', function(e) {

                        if (e.target.classList.contains('caret')) {
                            return;
                        }

                        const path = this.getAttribute('data-path');
                        if (path !== null) {
                            navigateTo(path);
                        }
                    });
                });
            }


            function navigateTo(path) {
                if (path.startsWith('//')) {
                    path = path.substring(1);
                }

                path = path.replace(/\/+/g, '/');
                currentPath = path;

                loadFileList();
            }

            function showAlert(title, message) {
                CoolAlert.show({
                    toast: true,
                    icon: title === 'Error' ? 'error' : 'success',
                    title: title,
                    text: message,
                });

                document.querySelectorAll('.action-btns').forEach(btn => {
                    btn.disabled = false;
                    btn.innerHTML = btn.textContent;

                    const siblingButtons = btn.closest('.modal-footer').querySelectorAll('button');
                    siblingButtons.forEach(sibling => {
                        if (sibling !== btn) {
                            sibling.disabled = false;
                        }
                    });

                });

                document.querySelectorAll('.modal.show').forEach(modalEl => {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) {
                        modal.hide();
                    }
                });


            }

            function initPermissionsModal() {
                const permValue = document.getElementById('permValue');
                const permChecks = document.querySelectorAll('.perm-check');

                permChecks.forEach(check => {
                    check.addEventListener('change', function() {
                        let value = 0;
                        permChecks.forEach(c => {
                            if (c.checked) {
                                value += parseInt(c.getAttribute('data-value'));
                            }
                        });
                        permValue.value = '0' + value.toString(8).padStart(3, '0');
                    });
                });

                permValue.addEventListener('input', function() {
                    const match = this.value.match(/^0([0-7]{3})$/);
                    if (match) {
                        const octal = match[1];
                        const decimal = parseInt(octal, 8);
                        permChecks.forEach(check => {
                            const checkValue = parseInt(check.getAttribute('data-value'));
                            check.checked = (decimal & checkValue) === checkValue;
                        });
                    }
                });
            }


            function initDragAndDrop() {
                const dropzone = document.getElementById('dropzone');

                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropzone.addEventListener(eventName, function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                    }, false);
                });

                ['dragenter', 'dragover'].forEach(eventName => {
                    dropzone.addEventListener(eventName, function() {
                        this.classList.add('drag-over');
                    }, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    dropzone.addEventListener(eventName, function() {
                        this.classList.remove('drag-over');
                    }, false);
                });

                dropzone.addEventListener('drop', function(e) {
                    const files = e.dataTransfer.files;
                    uploadFiles(files);
                }, false);
            }

            function init() {
                currentPath = '';
                initPermissionsModal();
                initDragAndDrop();
                loadDirectoryTree();
                loadFileList();
            }

            let pathHistory = [];
            let historyIndex = -1;

            function navigateToPath(path, pushToHistory = true) {
                currentPath = path;
                loadFileList();
                if (pushToHistory) {
                    pathHistory = pathHistory.slice(0, historyIndex + 1);
                    pathHistory.push(path);
                    historyIndex++;
                }
            }


            document.getElementById('home-btn').addEventListener('click', e => {
                e.preventDefault();
                navigateToPath('', true);
            });

            document.getElementById('breadcrumb-home-btn').addEventListener('click', e => {
                e.preventDefault();
                navigateToPath('', true);
            });

            document.getElementById('breadcrumb-search-btn').addEventListener('click', e => {
                e.preventDefault();
                const breadcrumbvalue = document.getElementById('breadcrumb').value;
                if (breadcrumbvalue.trim() == '') return;
                navigateToPath(breadcrumbvalue, true);
            });

            document.getElementById('up-btn').addEventListener('click', e => {
                e.preventDefault();
                if (!currentPath) return;
                const upPath = currentPath.split('/').slice(0, -1).join('/');
                navigateToPath(upPath, true);
            });

            document.getElementById('reload-btn').addEventListener('click', e => {
                e.preventDefault();
                loadFileList();
            });


            document.getElementById('view-trash-btn').addEventListener('click', e => {
                e.preventDefault();
                navigateToPath('/.trash', true);
            });


            document.getElementById('sort-btn').addEventListener('click', e => {
                e.preventDefault();
                currentOrder = (currentOrder === 'asc') ? 'desc' : 'asc';
                loadFileList();
            });

            document.getElementById('select-all-checkbox').addEventListener('change', function() {
                document.querySelectorAll('.item-check').forEach(check => {
                    check.checked = this.checked;
                    const event = new Event('change');
                    check.dispatchEvent(event);
                });
            });

            document.getElementById('select-all-btn').addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('select-all-checkbox').checked = true;
                document.getElementById('select-all-checkbox').dispatchEvent(new Event('change'));
            });

            document.getElementById('unselect-all-btn').addEventListener('click', function(e) {
                e.preventDefault();
                if (!this.classList.contains('disabled')) {
                    document.getElementById('select-all-checkbox').checked = false;
                    document.getElementById('select-all-checkbox').dispatchEvent(new Event('change'));
                }
            });

            document.getElementById('reload-btn').addEventListener('click', function(e) {
                e.preventDefault();
                loadFileList();
            });

            document.getElementById('new-file-btn').addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('fileName').value = '';
                document.getElementById('fileContent').value = '';
                document.getElementById('filePath').value = currentPath;
                const newFileModal = new bootstrap.Modal(document.getElementById('newFileModal'));
                newFileModal.show();
            });

            document.getElementById('new-folder-btn').addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('folderName').value = '';
                document.getElementById('currenPathValue').value = currentPath;
                const newFolderModal = new bootstrap.Modal(document.getElementById('newFolderModal'));
                newFolderModal.show();
            });

            document.getElementById("extract-btn").addEventListener("click", function(e) {
                e.preventDefault();

                const fileItem = fileList.find(
                    (item) => item.name === selectedItems[0]
                );
                if (fileItem && fileItem.type === "file") {
                    const fileName = fileItem.name;

                    document.getElementById('extractFileName').textContent = fileName;
                    document.getElementById('extractFileNameValue').value = fileName;
                    document.getElementById('extractPathName').value = currentPath;
                    const fileNameWithoutExt = fileName.replace(/\.[^/.]+$/, "");
                    document.getElementById('extractPath').value = currentPath + '/' + fileNameWithoutExt;
                    const extractModal = new bootstrap.Modal(document.getElementById('extractModal'));
                    extractModal.show();
                } else {
                    showAlert("Error", "Please select a file to extract");
                }

            });

            document.getElementById('rename-btn').addEventListener('click', function(e) {
                e.preventDefault();
                if (!this.classList.contains('disabled') && selectedItems.length === 1) {
                    document.getElementById('oldName').value = selectedItems[0];
                    document.getElementById('newName').value = selectedItems[0];
                    document.getElementById('renamePath').value = currentPath;
                    const renameModal = new bootstrap.Modal(document.getElementById('renameModal'));
                    renameModal.show();
                }
            });

            document.getElementById('ctx-rename').addEventListener('click', function() {
                if (contextTarget) {
                    document.getElementById('oldName').value = contextTarget.name;
                    document.getElementById('newName').value = contextTarget.name;
                    document.getElementById('renamePath').value = currentPath;
                    const renameModal = new bootstrap.Modal(document.getElementById('renameModal'));
                    renameModal.show();
                }
                document.getElementById('context-menu').style.display = 'none';
            });

            document.getElementById('permissions-btn').addEventListener('click', function(e) {
                e.preventDefault();
                if (!this.classList.contains('disabled') && selectedItems.length === 1) {
                    document.getElementById('permItem').textContent = selectedItems[0];
                    document.getElementById('permItemValue').value = selectedItems[0];

                    const item = fileList.find(item => item.name === selectedItems[0]);
                    if (item) {
                        const permString = item.permissions;
                        let octal = '0755';

                        if (permString.length >= 10) {
                            let owner = 0,
                                group = 0,
                                world = 0;

                            if (permString[1] === 'r') owner += 4;
                            if (permString[2] === 'w') owner += 2;
                            if (permString[3] === 'x' || permString[3] === 's') owner += 1;

                            if (permString[4] === 'r') group += 4;
                            if (permString[5] === 'w') group += 2;
                            if (permString[6] === 'x' || permString[6] === 's') group += 1;

                            if (permString[7] === 'r') world += 4;
                            if (permString[8] === 'w') world += 2;
                            if (permString[9] === 'x' || permString[9] === 't') world += 1;

                            octal = '0' + owner.toString() + group.toString() + world.toString();
                        }

                        document.getElementById('permValue').value = octal;

                        const decimal = parseInt(octal.substring(1), 8);
                        document.querySelectorAll('.perm-check').forEach(check => {
                            const checkValue = parseInt(check.getAttribute('data-value'));
                            check.checked = (decimal & checkValue) === checkValue;
                        });
                    }

                    const permissionsModal = new bootstrap.Modal(document.getElementById('permissionsModal'));
                    permissionsModal.show();
                }
            });
            document.getElementById('ctx-permissions').addEventListener('click', function() {
                if (contextTarget) {
                    selectedItems = [contextTarget.name];
                    document.getElementById('permissions-btn').click();
                }
                document.getElementById('context-menu').style.display = 'none';
            });

            document.getElementById('compress-btn').addEventListener('click', function(e) {
                e.preventDefault();
                if (!this.classList.contains('disabled') && selectedItems.length > 0) {
                    document.getElementById('compressName').value = selectedItems.length === 1 ? selectedItems[0] : 'archive_' + Math.floor(Date.now() / 1000);
                    document.getElementById('compressPath').value = currentPath;
                    document.getElementById('compressItems').value = selectedItems;
                    const compressModal = new bootstrap.Modal(document.getElementById('compressModal'));
                    compressModal.show();
                }
            });
            document.getElementById('ctx-compress').addEventListener('click', function() {
                if (contextTarget) {
                    selectedItems = [contextTarget.name];
                    document.getElementById('compress-btn').click();
                }
                document.getElementById('context-menu').style.display = 'none';
            });

            document.getElementById('delete-btn').addEventListener('click', function(e) {
                e.preventDefault();
                if (selectedItems.length === 0) return;

                if (!this.classList.contains('disabled')) {
                    document.getElementById('deletePath').value = currentPath;
                    document.getElementById('deleteItems').value = selectedItems;

                    document.getElementById("permanentDeleteCheck").addEventListener('click', function(e) {
                        const isPermanentDelete = this.checked;

                        document.getElementById('deleteAction').value = isPermanentDelete ? "delete" : "trash";

                        if (document.getElementById("deleteInfoAlert")) {
                            document.getElementById("deleteInfoAlert").style.display = isPermanentDelete ?
                                "none" :
                                "block";
                        }

                        if (document.getElementById("permanentDeleteAlert")) {
                            document.getElementById("permanentDeleteAlert").style.display =
                                isPermanentDelete ? "block" : "none";
                        }

                        if (document.getElementById("deleteButtonText")) {
                            document.getElementById("deleteButtonText").textContent = isPermanentDelete ?
                                "Delete Permanently" :
                                "Move to Trash";
                        }
                    });



                    const deleteModal = new bootstrap.Modal(
                        document.getElementById("deleteModal")
                    );
                    deleteModal.show();


                }
            });
            document.getElementById('ctx-delete').addEventListener('click', function() {
                if (contextTarget) {
                    selectedItems = [contextTarget.name];
                    document.getElementById('delete-btn').click();
                }
                document.getElementById('context-menu').style.display = 'none';
            });

            function uploadFiles(files) {
                if (files.length === 0) return;

                const formData = new FormData();
                formData.append('path', currentPath);

                for (let i = 0; i < files.length; i++) {
                    formData.append('files[]', files[i]);
                }

                // Show progress bar
                const progressBar = document.getElementById('upload-progress');
                const progressBarInner = progressBar.querySelector('.progress-bar');

                progressBar.style.display = 'block';
                progressBarInner.style.width = '0%';
                progressBarInner.setAttribute('aria-valuenow', 0);
                progressBarInner.textContent = 'Preparing upload...';

                const xhr = new XMLHttpRequest();
                xhr.open('POST', window.location.pathname, true);

                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = Math.round((e.loaded / e.total) * 100);
                        progressBarInner.style.width = percentComplete + '%';
                        progressBarInner.setAttribute('aria-valuenow', percentComplete);
                        progressBarInner.textContent = percentComplete + '% uploaded';

                        if (percentComplete >= 100) {
                            progressBarInner.classList.remove('progress-bar-animated');
                            progressBarInner.textContent = 'Processing...';
                        }
                    }
                });

                xhr.addEventListener('load', function() {
                    setTimeout(() => {
                        progressBar.style.display = 'none';
                        progressBarInner.classList.add('progress-bar-animated');
                    }, 500);

                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.status === 'success' || response.status === 'partial') {
                                loadFileList();
                                showAlert('Success', response.message);
                            } else {
                                showAlert('Error', response.message || 'Unknown error occurred during upload');
                            }
                        } catch (error) {
                            showAlert('Error', 'Failed to parse server response: ' + error.message);
                        }
                    } else {
                        showAlert('Error', 'HTTP Error: ' + xhr.status);
                    }
                });

                xhr.addEventListener('error', function(e) {
                    progressBar.style.display = 'none';
                    showAlert('Error', 'Network error occurred while uploading files');
                });

                xhr.send(formData);
            }

            function searchFiles() {
                const query = document.getElementById('search-input').value.trim();

                if (!query) {
                    showAlert('Error', 'Please enter a search query');
                    return;
                }

                const searchResults = document.getElementById('search-results');
                searchResults.innerHTML = '<tr><td colspan="5" class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Searching...</td></tr>';

                const formData = new FormData();
                formData.append('action', 'search');
                formData.append('path', currentPath);
                formData.append('query', query);

                fetch(window.location.pathname, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const results = data.data;

                            if (results.length === 0) {
                                searchResults.innerHTML = '<tr><td colspan="5" class="text-center">No results found</td></tr>';
                            } else {
                                let html = '';

                                results.forEach(result => {
                                    html += `
                                    <tr class="search-result-item" data-path="${result.path}">
                                        <td><i class="${result.icon}"></i> ${result.name}</td>
                                        <td>${result.path}</td>
                                        <td>${result.type === 'dir' ? 'Folder' : 'File'}</td>
                                        <td>${result.size || ''}</td>
                                        <td>${result.last_modified}</td>
                                    </tr>`;
                                });

                                searchResults.innerHTML = html;

                                // Add event listeners to search results
                                document.querySelectorAll('.search-result-item').forEach(item => {
                                    item.addEventListener('dblclick', function() {
                                        const path = this.getAttribute('data-path');
                                        const dirPath = path.substring(0, path.lastIndexOf('/'));
                                        navigateTo(dirPath);
                                        bootstrap.Modal.getInstance(document.getElementById('searchModal')).hide();
                                    });
                                });
                            }
                        } else {
                            searchResults.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error: ' + data.message + '</td></tr>';
                        }
                    })
                    .catch(error => {
                        searchResults.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error: Failed to perform search</td></tr>';
                    });

                const searchModal = new bootstrap.Modal(document.getElementById('searchModal'));
                searchModal.show();
            }

            function openFileEditor(fileName) {
                const fullPath = SERVER_ROOT_PATH + (currentPath.startsWith('/') ? currentPath : '/' + currentPath);
                const completePath = fullPath + (fullPath.endsWith('/') ? '' : '/') + fileName;
                window.open('codeEditor.php?filename=' + encodeURIComponent(completePath), '_blank');
            }

            function downloadFiles() {
                if (selectedItems.length === 1) {
                    const encodedPath = encodeURIComponent(currentPath);
                    const encodedFile = encodeURIComponent(selectedItems[0]);
                    window.location.href = `${window.location.pathname}?action=download&path=${encodedPath}&file=${encodedFile}`;
                }

                return;
            }

            document.getElementById('upload-btn').addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('file-upload').click();
            });

            document.getElementById('file-upload').addEventListener('change', function() {
                uploadFiles(this.files);
                this.value = '';
            });

            document.getElementById('download-btn').addEventListener('click', function(e) {
                e.preventDefault();
                if (!this.classList.contains('disabled')) {
                    downloadFiles();
                }
            });

            document.getElementById('search-btn').addEventListener('click', function(e) {
                e.preventDefault();
                searchFiles();
            });

            document.getElementById('search-input').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchFiles();
                }
            });

            document.getElementById('edit-btn').addEventListener('click', function(e) {
                e.preventDefault();
                if (!this.classList.contains('disabled') && selectedItems.length === 1) {
                    const fileName = selectedItems[0];
                    openFileEditor(fileName);
                }
            });

            document.getElementById('ctx-edit').addEventListener('click', function() {
                if (contextTarget && contextTarget.type === 'file') {
                    openFileEditor(contextTarget.name);
                }
                document.getElementById('context-menu').style.display = 'none';
            });

            document.getElementById('collapse-all-btn').addEventListener('click', function() {
                document.querySelectorAll('.caret.expanded').forEach(caret => {
                    caret.click();
                });
            });

            document.getElementById('ctx-open').addEventListener('click', function() {
                if (contextTarget && contextTarget.type === 'dir') {
                    navigateTo(currentPath + '/' + contextTarget.name);
                }
                document.getElementById('context-menu').style.display = 'none';
            });

            document.getElementById('ctx-download').addEventListener('click', function() {
                if (contextTarget) {
                    const encodedPath = encodeURIComponent(currentPath);
                    const encodedFile = encodeURIComponent(contextTarget.name);
                    window.location.href = `${window.location.pathname}?action=download&path=${encodedPath}&file=${encodedFile}`;
                }
                document.getElementById('context-menu').style.display = 'none';
            });


            function performFileOperation(operation) {
                if (selectedItems.length === 0) return;

                document.getElementById('fileOpTitle').textContent = operation === 'copy' ? 'Copy Items' : 'Move Items';
                document.getElementById('fileOpType').value = operation;
                document.getElementById('destinationPath').value = currentPath;
                document.getElementById('copyPath').value = currentPath;
                document.getElementById('copyItems').value = selectedItems;

                const itemsList = document.getElementById('fileOpItems');
                itemsList.innerHTML = `Selected Items: ${selectedItems.length}`;

                document.getElementById('currentPathDisplay').textContent = currentPath;

                const fileOpModal = new bootstrap.Modal(document.getElementById('fileOperationModal'));
                fileOpModal.show();
            }

            document.getElementById('copy-btn').addEventListener('click', function(e) {
                e.preventDefault();
                if (!this.classList.contains('disabled')) {
                    performFileOperation('copy');
                }
            });

            document.getElementById('move-btn').addEventListener('click', function(e) {
                e.preventDefault();
                if (!this.classList.contains('disabled')) {
                    performFileOperation('move');
                }
            });


            document.getElementById('ctx-copy').addEventListener('click', function() {
                if (contextTarget) {
                    selectedItems = [contextTarget.name];
                    performFileOperation('copy');
                }
                document.getElementById('context-menu').style.display = 'none';
            });

            document.getElementById('ctx-cut').addEventListener('click', function() {
                if (contextTarget) {
                    selectedItems = [contextTarget.name];
                    performFileOperation('move');
                }
                document.getElementById('context-menu').style.display = 'none';
            });


            document.getElementById('restore-btn')?.addEventListener('click', function(e) {
                e.preventDefault();
                if (selectedItems.length === 0) {
                    showAlert('Error', 'Please select items to restore');
                    return;
                }
                restoreFromTrash();
            });


            function restoreFromTrash() {
                if (selectedItems.length === 0) return;

                if (!currentPath.includes('/.trash')) {
                    showAlert('Error', 'Restore function is only available inside the trash folder');
                    return;
                }

                const restoreList = document.getElementById('restoreItems');
                restoreList.innerHTML = `Selected Items: ${selectedItems.length}`;

                document.getElementById('restorePath').value = currentPath;
                document.getElementById('restoreItems').value = selectedItems;


                const restoreModal = new bootstrap.Modal(document.getElementById('restoreModal'));
                restoreModal.show();
            }


            document.addEventListener("click", function(evt) {
                const btn = evt.target.closest(
                    "button[hx-get], button[hx-post], button[hx-put], button[hx-delete]"
                );
                if (btn) {
                    clickedBtn = btn;
                    btn.disabled = true;
                    btn.style.opacity = 0.6;
                }
            });

            document.addEventListener("submit", function(evt) {
                const submitButtons = evt.target.querySelectorAll("button[type='submit']");
                submitButtons.forEach((btn) => {
                    if (btn.offsetParent !== null) {
                        btn.disabled = true;
                        btn.style.opacity = 0.4;
                    }
                    btn.closest('.modal-footer').querySelector('button').disabled = true;
                });


            });

            document.addEventListener("htmx:afterRequest", function(evt) {
                if (hasResponseError) {
                    hasResponseError = false;
                    return;
                }

                const submitButtons = document.querySelectorAll("button[type='submit']");
                submitButtons.forEach((btn) => {
                    if (btn.offsetParent !== null) {
                        btn.disabled = false;
                        btn.style.opacity = 1;
                    }
                    btn.closest('.modal-footer').querySelector('button').disabled = false;
                });

                const response = evt.detail.xhr.response;
                let data;

                if (!response || response.trim() === "") {
                    return;
                }

                try {
                    data = JSON.parse(response);


                    const openModalEl = document.querySelectorAll(".modal.fade.show");
                    if (openModalEl.length > 0) {
                        openModalEl.forEach(el => {
                            const modal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
                            modal.hide();
                        });
                    }
                    if (data.status === "success") {
                        loadFileList();
                        loadDirectoryTree();
                        showAlert("Success", data.message);
                    } else {
                        showAlert("Error", data.message);
                    }
                } catch (e) {
                    showAlert("Error", e.message);
                }
            });


            document.body.addEventListener("htmx:responseError", (e) => {
                hasResponseError = true;

                const submitButtons = document.querySelectorAll("button[type='submit']");
                submitButtons.forEach((btn) => {
                    if (btn.offsetParent !== null) {
                        btn.disabled = false;
                        btn.style.opacity = 1;
                    }
                    btn.closest('.modal-footer').querySelector('button').disabled = false;
                });

                const response = e.detail.xhr.response;
                const data = JSON.parse(response);
                document.body.dispatchEvent(new Event("updateContent"));
                return CoolAlert.show({
                    icon: "error",
                    title: data.title || "Oops...",
                    text: data.text || data.message || "Something went wrong!",
                    toast: true,
                });
            });


            init();
        });
    </script>

</html>

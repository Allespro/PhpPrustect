<?php
const ENC_PASSWD = "SECRET_PASSWORD";
const MAIN_FOLDER = __DIR__ . "/demo/website";
const OBF_FOLDER = __DIR__ . "/demo/protected_website";

$FILES_OBF = [
    "config/config.php",
];
$DO_NOT_COPY = [
    "trash_dir",
];

function removeDir(string $dir): void
{
    $it = new RecursiveDirectoryIterator(
        $dir,
        RecursiveDirectoryIterator::SKIP_DOTS
    );
    $files = new RecursiveIteratorIterator(
        $it,
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }
    rmdir($dir);
}
function myxor($data, $password)
{
    $dataBytes = array_map("ord", str_split($data));
    $passwordBytes = str_split($password);
    $passwordLength = count($passwordBytes);

    $result = [];

    foreach ($dataBytes as $i => $byte) {
        $keyByte = $passwordBytes[$i % $passwordLength];
        $result[] = $byte ^ ord($keyByte);
    }

    return implode(array_map("chr", $result));
}
function generateFastHash(string $input): string
{
    return hash("xxh3", $input);
}
function encryptFile(string $data, string $fileName, string $password): string
{
    $fileHash = generateFastHash($fileName);
    echo $fileHash . " " . $fileName . "\n";
    $saltedPassword = myxor($password, $fileHash);
    $resultString = myxor($data, $saltedPassword);
    return bin2hex($resultString);
}

function processFile($filePath)
{
    $content = file_get_contents($filePath);
    $re = "/\<\?php/m";
    preg_match($re, $content, $matches);
    if (!empty($matches[0])) {
        $content = preg_replace($re, "", $content);
    }

    $fileNameEx = explode("/", $filePath);
    $encryptedContent = encryptFile(
        $content,
        end($fileNameEx),
        ENC_PASSWD
    );
    $res = [
        "<?php prustect(__FILE__);",
        "return 0;",
        "#" . $encryptedContent,
    ];
    echo "Encrypted: " . $filePath . PHP_EOL;
    return join(PHP_EOL, $res);
}

function recursiveCopyAndProcess(
    $sourceDir,
    $targetDir,
    $filesToProcess,
    $notCopy
) {
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $items = scandir($sourceDir);
    foreach ($items as $item) {
        if ($item === "." || $item === "..") {
            continue;
        }

        $sourcePath = $sourceDir . DIRECTORY_SEPARATOR . $item;
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $item;
        if (in_array($sourcePath, $notCopy)) {
            continue;
        }
        if (is_dir($sourcePath)) {
            recursiveCopyAndProcess(
                $sourcePath,
                $targetPath,
                $filesToProcess,
                $notCopy
            );
        } elseif (is_file($sourcePath)) {
            if (in_array($sourcePath, $filesToProcess)) {
                $modifiedContent = processFile($sourcePath);
                file_put_contents($targetPath, $modifiedContent);
            } else {
                copy($sourcePath, $targetPath);
            }
        }
    }
}

for ($i = 0; $i < count($FILES_OBF); $i++) {
    $FILES_OBF[$i] = MAIN_FOLDER . DIRECTORY_SEPARATOR . $FILES_OBF[$i];
}

for ($i = 0; $i < count($DO_NOT_COPY); $i++) {
    $DO_NOT_COPY[$i] = MAIN_FOLDER . DIRECTORY_SEPARATOR . $DO_NOT_COPY[$i];
}

if (is_dir(OBF_FOLDER)) {
    removeDir(OBF_FOLDER);
}

recursiveCopyAndProcess(MAIN_FOLDER, OBF_FOLDER, $FILES_OBF, $DO_NOT_COPY);

<?php

set_error_handler(
    static function ($errno, $errstr, $file = '', $line = null) {
        $lines = [];
        $errstr = trim((string) $errstr);
        if ($errstr !== '') {
            $lines[] = $errstr;
        }
        $lines[] = "Error code: {$errno}";
        if ($file) {
            $lines[] = "File: {$file}";
            if ($line) {
                $lines[] = "Line: {$line}";
            }
        }
        throw new RuntimeException(implode("\n", $lines));
    },
    -1
);

function checkPatchCommand(): void
{
    exec('patch -v 2>&1', $output, $rc);
    if ($rc !== 0) {
        throw new RuntimeException('Failed to find the patch command!');
    }
    if (!preg_match('/^(GNU )?patch \d/', $output[0])) {
        throw new RuntimeException("This script only works with GNU patch.\nThe currently available patch command has this version:\n" . trim(implode("\n", $output)));
    }
}

function readPatchesFromJson(): array
{
    echo "# Collecting patches\n";
    echo '- reading composer.json file... ';
    $file = PROJECT_DIR_ROOT . '/composer.json';
    $contents = file_get_contents($file);
    if ($contents !== false) {
        echo "done.\n";
        echo '- decoding json... ';
        $data = json_decode($contents, true);
        if (is_array($data)) {
            echo "done.\n";
            echo '- extracting patch list... ';
            $patches = isset($data['extra']['patches']) ? $data['extra']['patches'] : null;
            if (is_array($patches) && $patches !== []) {
                echo "done.\n";
                return $patches;
            }
        }
    }
    throw new RuntimeException('Failed to extract patches from composer.json');
}

function createTemporaryDirectory(): string
{
    if (!is_dir(PROJECT_DIR_TMP)) {
        mkdir(PROJECT_DIR_TMP);
    }
    $tempDir = tempnam(PROJECT_DIR_TMP, 'test');
    unlink($tempDir);
    if (!mkdir($tempDir)) {
        throw new RuntimeException('Failed to create a temporary directory');
    }
    return $tempDir;
}

function createComposerJson(string $dir, string $packageName, string $packageVersion): void
{
    $composerConfig = [
        'require' => [
            $packageName => $packageVersion,
        ],
        'config' => [
            'allow-plugins' => true,
        ],
    ];
    file_put_contents($dir . '/composer.json', json_encode($composerConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function runComposerUpdate(string $dir): void
{
    $oldDir = getcwd();
    if (!chdir($dir)) {
        throw new RuntimeException("Failed to enter directory {$dir}");
    }
    try {
        exec('composer update --ignore-platform-reqs --no-ansi --no-progress --no-dev --working-dir=' . escapeshellarg($dir) . ' 2>&1', $output, $rc);
        if ($rc !== 0) {
            throw new RuntimeException('composer update failed: ' . trim(implode("\n", $output)));
        }
    } catch (Exception $x) {
        throw $x;
    } catch (Throwable $x) {
        throw $x;
    } finally {
        chdir($oldDir);
    } 
}

function applyPatch(string $dir, string $patchFile)
{
    if (!is_file($patchFile)) {
        throw new RuntimeException("Failed to find the patch file {$patchFile}");
    }
    $cmd = implode(' ', [
        'patch',
        // Strip the smallest prefix containing 1 leading slash from each file name found in the patch file
        '-p1',
        // Back up mismatches only if otherwise requested
        '--no-backup-if-mismatch',
        // When a patch does not apply, patch usually checks if the patch looks like it has been applied already by trying to reverse-apply the first hunk.
        // Let's prevent that.
        '-N',
        // Change the working directory (aka --directory)
        '-d', escapeshellarg($dir),
        // Read patch from PATCHFILE instead of stdin (aka --input)
        '-i', escapeshellarg($patchFile),
        // Output rejects to FILE (aka --reject-file)
        '-r', escapeshellarg($dir . DIRECTORY_SEPARATOR . 'patch.rej'),
    ]);
    exec("{$cmd} 2>&1", $output, $rc);
    if ($rc !== 0) {
        throw new RuntimeException(trim(implode("\n", $output)));
    }
}

function testPatches(string $packageName, string $packageVersion, array $patchesForPackage)
{
    echo "# Testing patches for {$packageName} v{$packageVersion}\n";
    echo '- creating temporary directory... ';
    $tempDir = createTemporaryDirectory();
    try {
        echo "done.\n";
        echo '- creating temporary composer.json file... ';
        createComposerJson($tempDir, $packageName, $packageVersion);
        echo "done.\n";
        echo '- installing composer packages... ';
        runComposerUpdate($tempDir);
        echo "done.\n";
        foreach ($patchesForPackage as $patchName => $patchFile) {
            echo "- applying patch '{$patchName}'... ";
            applyPatch(
                $tempDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $packageName),
                PROJECT_DIR_PATCHES . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $patchFile)
            );
            echo "done.\n";
        }
    } catch (Exception $x) {
        throw $x;
    } catch (Throwable $x) {
        throw $x;
    } finally {
        if (DIRECTORY_SEPARATOR === '\\') {
            //exec('rmdir /s /q ' . escapeshellarg($tempDir) . ' 2>&1');
        } else {
            exec('rm -rf ' . escapeshellarg($tempDir) . ' 2>&1');
        }
    }
}

function main(): int
{
    checkPatchCommand();
    $patches = readPatchesFromJson();
    foreach($patches as $packageSpec => $patchesForPackage) {
        if ($patchesForPackage === []) {
            throw new RuntimeException("No patches defined for package {$packageSpec}");
        }
        $chunks = explode(':', $packageSpec);
        if (count($chunks) !== 2 || !preg_match('/^\d+(\.\d+)*$/', $chunks[1])) {
            throw new RuntimeException("Package patches must be provided in the form <package>:<version>, but we received {$packageSpec}");
        }
        list($packageName, $packageVersion) = $chunks;
        testPatches($packageName, $packageVersion, $patchesForPackage);
    }
    echo "\nAll patches have been applied correctly.\n";
    return 0;
}

define('PROJECT_DIR_ROOT', str_replace('/', DIRECTORY_SEPARATOR, realpath(__DIR__ . '/../..')));
define('PROJECT_DIR_TMP', PROJECT_DIR_ROOT . DIRECTORY_SEPARATOR . 'tmp');
define('PROJECT_DIR_PATCHES', PROJECT_DIR_ROOT);

try {
    exit(main());
} catch (RuntimeException $x) {
    echo $x->getMessage() . "\n";
    exit(1);
}

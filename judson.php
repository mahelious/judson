<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'librarian.php';

try {
    // --- 1. Handle command-line arguments
    $cli_args = getopt('i:o:h');

    if (array_key_exists('h', $cli_args)) {
        print_help();
    }

    if (empty($cli_args['i'])) {
        throw new Exception("Error: input directory '${cli_args['i']}' could not be opened");
    }
    $source_directory = realpath($cli_args['i']);

    // if not defined by user default to /library under the source directory
    $destination_directory = !empty($cli_args['o']) ? realpath($cli_args['o']) : $source_directory . DIRECTORY_SEPARATOR . 'library';

    // --- 2. Invoke a librarian to sort the files
    $Judson = new Librarian($source_directory, $destination_directory);
    $files_to_sort = $Judson->getSourceFiles();

    $file_count = count($files_to_sort);
    $sorted_files = 0;

    foreach ($files_to_sort as $file)
    {
        try {
            $Judson->sort($file);
            $sorted_files++;
        } catch (Exception $e) {
            echo $e->getMessage(), '; file skipped', PHP_EOL;
            continue;
        }
    }

    // --- 3. Report on activity
    $unsorted_files = $file_count - $sorted_files;
    echo "$sorted_files files sorted, $unsorted_files files remain", PHP_EOL;

    exit(0);
} catch (Exception $e) {
    echo $e->getMessage(), PHP_EOL;
    print_help();
}

/**
 *
 * @return void
 */
function print_help(): void
{
    echo PHP_EOL, "Judson - A helpful music sorting utility", PHP_EOL,
        str_repeat('=', 80), PHP_EOL, PHP_EOL,
        'Options:', PHP_EOL, str_repeat('-', 8), PHP_EOL,
        " -i\tinput directory of unsorted files; required", PHP_EOL,
        " -o\toutput directory where sorted files are saved", PHP_EOL,
        "\tif not set files will be saved to 'library/' under the input directory", PHP_EOL,
        "\tjudson will attempt to create output directory if not exists", PHP_EOL,
        " -h\tprint this help message", PHP_EOL, PHP_EOL,
        "ex. judson -i \"/media/My Unsorted Music/\" -o \"/media/sorted_music/\"", PHP_EOL, PHP_EOL;
    exit(0);
}

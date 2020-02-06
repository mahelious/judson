<?php

require_once __DIR__ . '/../vendor/getID3-1.9.19/getid3/getid3.php';

/**
 * Librarian class - Looks for files tagged with an Acoustid Id and attempts to standardize file path & name
 */
class Librarian
{
    protected $ID3;

    private $source;
    private $destination;

    /**
     * Initialize a new Librarian
     *
     * @param string $source
     * @param string $destination
     */
    public function __construct(string $source, string $destination)
    {
        $this->ID3= new getID3();

        $this->source = self::verifyDirectory($source);

        $this->destination = self::verifyDirectory($destination, true);
    }

    /**
     *
     * @param string $dir   the directory to search for files to move
     * @return array        list of files to move
     * @throws Exception
     */
    public function getSourceFiles(string $dir = ''): array
    {
        if (empty($dir)) {
            $dir = $this->source;
        }

        $files = [];

        if (!is_dir($dir)) {
            throw new Exception("Could not open '$dir'");
        }

        if (!is_readable($dir)) {
            throw new Exception("Could not read '$dir'");
        }

        // get list of entities under $dir
        $contents = scandir($dir);

        foreach ($contents as $content) {
            $entity = realpath($dir . DIRECTORY_SEPARATOR . $content);
            // skip the destination, hidden, and unreadable files
            if (!is_readable($entity) || strpos($content, '.') === 0 || $entity == $this->destination) {
                continue;
            }

            if (is_dir($entity)) {
                $files = array_merge($files, $this->getSourceFiles($entity));
            } else
            if (is_file($entity)) {
                $files[] = $entity;
            } else {
                echo "analyzing $entity produced a weird result, not touching this", PHP_EOL;
            }
        }

        return $files;
    }

    /**
     *
     * @param string $file
     * @return string
     * @throws Exception
     */
    public function sort(string $file): string
    {
        $file_info = $this->ID3->analyze($file);

        // if the Acousti Id is not present assume track data is not valid and skip processing
        if (empty($file_info['id3v2']['comments']['text']['Acoustid Id'])) {
            throw new Exception("Could not identify Acousti Id for '$file'");
        }

        // collect the meta data used to generate new filepath
        $artist = self::getFileComments(['band', 'artist'], $file_info);
        $album = self::getFileComments(['album'], $file_info);
        $track_number = self::getFileComments(['track_number'], $file_info);
        $title = self::getFileComments(['title'], $file_info);
        // prefer the current file extension to the id3vX extension; TODO - pull extension from file magic or id3vX ??
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        // save as the format "./ArtistName - AlbumName/TrackNumber - TrackName.ext"
        return $this->moveFile($file, "$track_number - $title.$extension", "$artist - $album");
    }

    /**
     *
     * @param string $source
     * @param string $dname
     * @param string $dpath
     * @return string
     * @throws Exception
     */
    protected function moveFile(string $source, string $dname, string $dpath): string
    {
        $destination = self::verifyDirectory($this->destination . DIRECTORY_SEPARATOR . $dpath, true)
                       . DIRECTORY_SEPARATOR . $dname;

        if (is_file($destination)) {
            throw new Exception("$destination already exists and would be overwritten");
        }

        if (!copy($source, $destination)) {
            $err = error_get_last();
            throw new Exception("cp $source failed, {$err['message']} ({$err['type']})");
        }

        if (!unlink($source)) {
            throw new Exception("cannot remove $source, please cleanup manually");
        }

        return $destination;
    }

    /**
     *
     * @param string $dir
     * @param bool $create
     * @return string
     * @throws Exception
     */
    protected static function verifyDirectory(string $dir, bool $create = false): string
    {
        if (!is_dir($dir)) {
            if (!$create) {
                throw new Exception("'$dir' not found");
            }
            if (!mkdir($dir, 0777, true)) {
                throw new Exception("Cannot create $dir, please check permissions");
            }
        }

        if (!is_readable($dir)) {
            throw new Exception("Cannot read $dir, please check permissions");
        }

        if (!is_writable($dir)) {
            throw new Exception("Cannot write to $dir, please check permissions");
        }

        return $dir;
    }

    /**
     *
     * @param array $keys
     * @param array $info
     * @return string
     * @throws Exception
     */
    protected static function getFileComments(array $keys, array $info): string
    {
        foreach ($keys as $key) {
            foreach (['id3v2', 'id3v1'] as $tag) {
                if (!empty($info[$tag]['comments'][$key]) && count($info[$tag]['comments'][$key]) > 0) {
                    return $info[$tag]['comments'][$key][0];
                }
            }
        }

        throw new Exception("could not identify $key");
    }
}

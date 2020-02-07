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

    const TAG_PRIMARY               = 'acoustid';
    const COMMENT_TAG_ALBUMARTIST   = 'albumartist';
    const COMMENT_TAG_ALBUMARTIST2  = 'band';
    const COMMENT_TAG_ALBUMARTIST3  = 'artist';
    const COMMENT_TAG_ALBUM         = 'album';
    const COMMENT_TAG_TRACK         = 'track_number';
    const COMMENT_TAG_TITLE         = 'title';
    const COMMENT_TAG_CATALOG       = 'catalognumber';

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
        try {
            // getID3::analyze throws Errors, yay!
            $file_info = $this->ID3->analyze($file);
        } catch (Error $e) {
            throw new ErrorException("Error sorting '$file': " . $e->getMessage());
        }

        // normalize comments from all tag types
        getid3_lib::CopyTagsToComments($file_info);

        // if the primary tag is not found assume the tagging is absent or irregular
        if (empty($file_info['comments']) || !$this->hasPrimaryTag($file_info['comments'])) {
            throw new Exception("$file missing information tags, please rescan");
        }

        // collect the meta data used to generate new filepath
        $artist = self::fileSanitize(self::getFileComments([
            self::COMMENT_TAG_ALBUMARTIST,
            self::COMMENT_TAG_ALBUMARTIST2,
            self::COMMENT_TAG_ALBUMARTIST3,
        ], $file_info['comments']));
        $album  = self::fileSanitize(self::getFileComments([self::COMMENT_TAG_ALBUM], $file_info['comments']));
        $catalog = self::fileSanitize(self::getFileComments([self::COMMENT_TAG_CATALOG], $file_info['comments']));
        $track  = self::fileSanitize(self::getFileComments([self::COMMENT_TAG_TRACK], $file_info['comments']));
        $title  = self::fileSanitize(self::getFileComments([self::COMMENT_TAG_TITLE], $file_info['comments']));
        // prefer the current file extension over file magic or tagging; may reconsider this decision
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        // artist, album, track, and title are required to match
        $required_fields = ['artist', 'album', 'track', 'title'];
        foreach ($required_fields as $fieldname) {
            if (empty($$fieldname)) {
                throw new Exception("could not identify $fieldname");
            }
        }

        // save as the format "./ArtistName - AlbumName/TrackNumber - TrackName.ext"
        $foldername = "$artist - $album" . (!empty($catalog) ? " ($catalog)" : '');
        $filename = "$track - $title.$extension";
        return $this->moveFile($file, $filename, $foldername);
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
            throw new ErrorException("Error: {$err['message']}");
        }

        if (!unlink($source)) {
            throw new Exception("cannot remove $source, please cleanup manually");
        }

        return $destination;
    }

    /**
     * Determine whether the Primary tag is present in the file tags
     *
     * Primary tag is a keystone tag indicating that tags have been applied normally.
     *
     * @param array $comments
     * @return bool
     */
    protected function hasPrimaryTag(array $comments): bool
    {
        foreach ($comments as $k => $v) {
            if (is_array($v) && $this->hasPrimaryTag($comments[$k])) {
                return true;
            }
            if (!empty($v) && strpos(strtolower($k), self::TAG_PRIMARY) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove slashes '/' & '\' that may interfere with filenames
     *
     * @param string $file_part
     * @return string
     */
    public static function fileSanitize(string $file_part): string
    {
        // drop multiple whitespace that tends to occur when slicing slashes
        return mb_ereg_replace('\s+', ' ', str_replace('\\', '', str_replace('/', '', $file_part)));
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
     * @param array $comments
     * @return string
     * @throws Exception
     */
    protected static function getFileComments(array $keys, array $comments): string
    {
        foreach ($keys as $key) {
            if (!empty($comments[$key]) && count($comments[$key]) > 0) {
                return $comments[$key][0];
            }
        }

        return false;
    }
}

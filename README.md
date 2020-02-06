# Judson

A friendly cli librarian that standardizes music files based on id3 tags.
Will only sort files that have been previously tagged with an Acoustid ID.
Written in PHP.

MusicBrainz Picard is a cross-platform easy to use tagger, https://picard.musicbrainz.org/

Requirements:
- PHP 7.2

Install:
- download and unzip where you like
- make judson/bin/judson executable
```
$ chmod +x ./judson/bin/judson
```
- create a symbolic link in your path to judson/bin/judson
```
$ ln -s ./judson/bin/judson /usr/local/bin
```

Instructions:
- Judson requires an input directory and will recursively parse files and subdirectores
```
$ judson -i ~/Music/My Unsorted Albums
```
- by default Judson will create a new folder, library/, under the input directory to save sorted files;
  alternatively a custom output directory may be specified
```
$ judson -i ~/Music/My Unsorted Albums` -o ~/Music/My Sorted Albums`
```

Acknowledgements
- https://github.com/JamesHeinrich/getID3
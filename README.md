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

Disclaimer
The software is provided to you "as-is" and without warranty of any kind, express, implied or otherwise, including without limitation, any warranty of fitness for a particular purpose. In no event shall the author or authors be liable to you or anyone else for any direct, special, incidental, indirect or consequential damages of any kind, or any damages whatsoever, including without limitation, loss of profit, loss of use, savings or revenue, or the claims of third parties, whether or not author or authors has been advised of the possibility of such loss, however caused and on any theory of liability, arising out of or in connection with the possession, use or performance of this software.
Please backup your files before using Judson!

Acknowledgements
- https://github.com/JamesHeinrich/getID3

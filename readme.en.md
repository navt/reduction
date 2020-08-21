# Reduction

Reduction is designed to optimize the size of images on a website. Often, users upload too large images to the server :) <br>
The logic of the application is as follows: first, directories are inspected for the presence of image files (jpeg, png, gif) larger than a certain size, then the found files can be overwritten with the optimal width and height (the proportions remain correct). Files are searched recursively. <br>
You can select images not only by the file size, but also by the size of the "long" side of the image. The settings are placed in the file `data / config.json`. <br>
To navigate how much the application is running in time and consumes memory, the Marker class is written. The results are written to the log. <br> <br>.

Let's look at the file `data / config.json`, in it: <br>
`" folderPath "` - path to the images directory, relative to the `reduction` directory <br>
`" mode "` - mode for selecting images either `" ImageSide "`, or `" FileSize "` <br>
`" maxFileSize "` - in the `" FileSize "` mode, files larger than the specified size in bytes will be selected <br>
`" maxImageSide "` - in the `" ImageSide "` mode, files with a long side larger than the value in pixels specified here will be selected <br>
`" maxWidth "` - width of new images, if images are horizontal in pixels <br>
`" maxHeight "` - height of new images, if images are vertical in pixels <br>
`" ableTypes "` - array of file extensions, optionally there should be 3 values, select one or two for "dot" work <br> <br>

Thus, the code for a typical application might look like this: <br>

```php
$ log = new Logger ("data / app.log");
$ marker = new Marker ($ log);

// create a Reduction instance, get a list of files, print to the log
$ reduct = new Reduction ($ log, "data / config.json");
$ reduct-> getList (); // creates a list of filtered files
$ reduct-> printList ();
$ marker-> addMark (); // add timestamp

// reduce the size of the files in the list, destroy the Reduction instance
$ reduct-> reductAll (); // overwrite files
$ reduct = null;
$ marker-> addMark ();

// check what happened
$ reduct = new Reduction ($ log, "data / config.json");
$ reduct-> getList ();
$ reduct-> printList ();
$ marker-> addMark ();

$ marker-> display ();
```

See also `app.php` file <br>

## Usage
Place the contents of this repository on the server in the `reduction` directory, edit` data / config.json` and `app.php` in accordance with your current task. <br>
In the console go to the `reduction` directory. Run the script <br>
`$ php -f app.php` <br>
Using the console is preferable because with a large enough volume of images, the application will take time to complete the task. <br>
Take care of the security of your data: make a dump of the directories where you plan to carry out work, run the script on the test data on the local computer, see if the result is satisfactory to you.

### Restrictions
The script does not work with gifs with multiple layers.
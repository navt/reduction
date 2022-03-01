# Reduction

Utility Reduction is designed to optimize the size of images on a website. Often, users upload too large images to the server :) <br>
The logic of the application is as follows: first, directories are inspected for the presence of image files (jpeg, png, gif) larger than a certain size, then the found files can be overwritten with the optimal width and height (the proportions remain correct). Files are searched recursively. <br>
You can select images not only by the file size, but also by the size of the "long" side of the image. The settings are placed in the file `data/config.json`. <br>
To navigate how much the application is running in time and consumes memory, the Marker class is written. The results are written to the log. <br> <br>

Let's look at the file `data/config.json`, in it: <br>
`"folderPath"` - relative path to target images directory from `app.php`<br>
`"mode"` - mode for selecting images either `"ImageSide"`, or `"FileSize"` <br>
`"maxFileSize"` - in the `"FileSize"` mode, files larger than the specified size in bytes will be selected <br>
`"maxImageSide"` - in the `"ImageSide"` mode, files with a long side larger than the value in pixels specified here will be selected <br>
`"maxWidth"` - width of new images, if images are horizontal in pixels<br>
`"maxHeight"` - height of new images, if images are vertical in pixels<br>
`"ableTypes"` - array of file extensions, optionally there should be 3 values, select one or two for "dot" work <br>
`"quality"` - the `quality` parameter when recording jpeg and png images using the imagejpeg(), imagepng() functions<br><br>



## Usage
Consider the case where you place the `vendor` directory in the root directory of your site.
```bash
# go to the root directory of site
$ cd root-of-site
# require package navt/reduction
$ composer require navt/reduction
# make sure that there is no app.php file and no data directory in the root directory.
# copy 
$ cp vendor/navt/reduction/app.php ./
$ cp -r vendor/navt/reduction/data ./
```
Edit` data/config.json` and `app.php` in accordance with your current task.<br>
Run
```bash
$ php -f app.php
```
Initially, the `app.php` file contains the code to inspect the target directory. If you decide that it is time to reduce the size of the selected files, then the code in `app.php` should be changed to:

```php
<?php

chdir(__DIR__);
require_once __DIR__.'/vendor/autoload.php';

use navt\Reduction\Logger\Logger;
use navt\Reduction\Marker;
use navt\Reduction\Reduction;

$log = new Logger("data/app.log");
$marker = new Marker($log);

// create a Reduction instance, get a list of files, print to the log
$reduct = new Reduction($log, "data/config.json");
$reduct->getList(); // creates a list of filtered files
$reduct->printList();
$marker->addMark(); // add timestamp

// reduce the size of the files in the list, destroy the Reduction instance
$reduct->reductAll(); // overwrite files
$reduct = null;
$marker->addMark();

// check what happened
$reduct = new Reduction($log, "data/config.json");
$reduct->getList();
$reduct->printList();
$marker->addMark();

$marker->display();
```

### Peculiars
Using the console is preferable because with a large enough volume of images, the application will take time to complete the task. Conduct an evaluation run with a small number of files to understand how fast the utility works.<br>
Take care of the integrity of your data: make a dump of the directories where you plan to carry out work, run the script on the test data on the local computer, see if the result is satisfactory to you.<br>
To work with jpeg, png and gif images without animation, the PHP module `gd` will be used.<br>
To work with gif-images that have several layers (animation), the PHP module `imagick` must be installed on your server. If there is no such module, then animated gifs will not be affected.<br>
If you're going to scale down gifs that have multiple layers, consider running the gif-only utility separately.
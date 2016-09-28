# PHP Image Processor

This script can be placed into an image folder and then be used as a proxy for 
all image requests, providing the ability to resize images on the fly based on 
the request parameters. 

This script uses the `eventviva/php-image-resize` library.

## Installation

Drop the `.htaccess` and `wbimghandler.php` files into a publically accessible
images folder.

Using composer install the `eventviva/php-image-resize` dependency.  If your 
environment currently is not using composer vendor packages, you can 
alternatively install the library manually by downloading the src from the GIT
repository.

Edit the `wbimghandler.php` file, reviewing the `$cfg` array and the include
line expecting a sibling `vendor` folder.  If you've installed the resize 
library manually you will need to point the require line to the 
`ImageResize.php` directly.

## Usage

The script will review web calls.  Any request the provides a width, height, or
scale parameter will be processed.  Any other request is ignored by the script
and the image is loaded normally by the web server.

Requests are made the same as if calling the image directly, except you are 
adding a few flags to direct the script what processing is desired.

### Examples

Scale the image to 50% original size.
`http://website.com/images/image.png?s=50`

Resize the image to fit 240x120, then crop to the dimensions after resizing.
`http://website.com/images/image.png?w=240&h=120&crop`

Resize the image to fit 240x120.
`http://website.com/images/image.png?w=240&h=120`

Resize the image to a max width of 240
`http://website.com/images/image.png?w=240`

Resize the image to a max height of 120
`http://website.com/images/image.png?h=120`

Resize the image to a max height of 120
`http://website.com/images/image.png?h=120`

## Caching

The system by default will attempt to cache all rendered files into a `.cache` folder for later use.  The cached file will be refreshed if the timestamp of the source file changes.
